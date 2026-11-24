<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderTask;
use App\Models\OrderWorkflow;
use App\Models\StockReservation;
use App\Models\TaskAssignment;
use App\Models\TaskAttachment;
use App\Models\TaskDependency;
use App\Models\TaskHistory;
use App\Models\User;
use Database\Seeders\WorkflowSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

class WorkManagementService
{
    public function __construct(
        private readonly InventoryLedgerService $inventoryLedger,
        private readonly AuditService $auditService,
    ) {
    }

    /**
     * Generate sequential workflow tasks for an order idempotently.
     */
    public function generateTasksForOrder(Order $order, ?OrderWorkflow $workflow = null): Collection
    {
        $existing = OrderTask::query()
            ->where('order_id', $order->id)
            ->orderBy('id')
            ->get();

        if ($existing->isNotEmpty()) {
            return $existing;
        }

        if (! $workflow) {
            $workflow = OrderWorkflow::query()
                ->where('is_default', true)
                ->where('is_active', true)
                ->first()
                ?? OrderWorkflow::query()->where('is_active', true)->first();

            if (! $workflow) {
                (new WorkflowSeeder())->run();

                $workflow = OrderWorkflow::query()
                    ->where('is_default', true)
                    ->where('is_active', true)
                    ->first()
                    ?? OrderWorkflow::query()->where('is_active', true)->firstOrFail();
            }
        }

        return DB::transaction(function () use ($order, $workflow): Collection {
            $steps = $workflow->steps()->orderBy('step_order')->get();
            $stepTaskMap = [];
            $createdTasks = collect();
            $actorId = Auth::id();

            foreach ($steps as $step) {
                $assignee = $this->autoAssignUserForRole($step->default_role);
                $status = $assignee ? OrderTask::STATUS_ASSIGNED : OrderTask::STATUS_PENDING;

                $task = OrderTask::create([
                    'order_id' => $order->id,
                    'workflow_step_id' => $step->id,
                    'title' => $step->name,
                    'code' => $step->code,
                    'status' => $status,
                    'assigned_to' => $assignee?->id,
                    'department' => $step->department,
                    'priority' => 'normal',
                    'due_at' => now()->addMinutes((int) $step->sla_minutes),
                ]);

                $stepTaskMap[$step->id] = $task;

                if ($step->depends_on_step_id && isset($stepTaskMap[$step->depends_on_step_id])) {
                    TaskDependency::create([
                        'order_task_id' => $task->id,
                        'depends_on_task_id' => $stepTaskMap[$step->depends_on_step_id]->id,
                    ]);
                }

                if ($assignee) {
                    TaskAssignment::create([
                        'order_task_id' => $task->id,
                        'from_user_id' => null,
                        'to_user_id' => $assignee->id,
                        'assigned_by' => $actorId,
                        'reason' => "Auto-assigned for role {$step->default_role}",
                        'created_at' => now(),
                    ]);
                }

                TaskHistory::create([
                    'order_task_id' => $task->id,
                    'actor_id' => $actorId,
                    'from_status' => null,
                    'to_status' => $status,
                    'note' => "Task generated from workflow {$workflow->name}",
                    'created_at' => now(),
                ]);

                $createdTasks->push($task);
            }

            return $createdTasks;
        });
    }

    /**
     * Pick the user with the given role who currently holds the lowest number of active tasks.
     */
    public function autoAssignUserForRole(string $role): ?User
    {
        if (! Role::query()->where('name', $role)->where('guard_name', 'web')->exists()) {
            return null;
        }

        $candidates = User::role($role)->get();
        if ($candidates->isEmpty()) {
            return null;
        }

        $activeCounts = OrderTask::query()
            ->whereIn('assigned_to', $candidates->pluck('id'))
            ->whereIn('status', [
                OrderTask::STATUS_PENDING,
                OrderTask::STATUS_ASSIGNED,
                OrderTask::STATUS_IN_PROGRESS,
                OrderTask::STATUS_REVISION,
            ])
            ->selectRaw('assigned_to, COUNT(*) as active_count')
            ->groupBy('assigned_to')
            ->pluck('active_count', 'assigned_to');

        return $candidates
            ->sortBy(fn (User $user): array => [(int) ($activeCounts[$user->id] ?? 0), $user->id])
            ->first();
    }

    /**
     * Start a task after verifying all upstream dependencies are completed.
     */
    public function startTask(OrderTask $task, User $actor): OrderTask
    {
        if ($task->isBlocked()) {
            throw new \DomainException('Task sebelumnya (dependency) belum selesai.');
        }

        return DB::transaction(function () use ($task, $actor): OrderTask {
            $locked = OrderTask::query()->with('workflowStep')->whereKey($task->id)->lockForUpdate()->firstOrFail();

            if ($locked->isBlocked()) {
                throw new \DomainException('Task sebelumnya (dependency) belum selesai.');
            }

            $fromStatus = $locked->status;
            $locked->status = OrderTask::STATUS_IN_PROGRESS;
            $locked->started_at = $locked->started_at ?? now();
            $locked->save();

            TaskHistory::create([
                'order_task_id' => $locked->id,
                'actor_id' => $actor->id,
                'from_status' => $fromStatus,
                'to_status' => OrderTask::STATUS_IN_PROGRESS,
                'note' => 'Task started',
                'created_at' => now(),
            ]);

            if (class_exists(Order::class)) {
                $order = Order::query()->whereKey($locked->order_id)->first();
                if ($order) {
                    $order->status = 'processing';
                    $order->fulfillment_status = $locked->code ?: 'picking';
                    $order->save();
                }
            }

            $this->recordPublicMilestoneSafely($locked, $actor);

            return $locked->fresh(['workflowStep', 'assignee', 'dependencies']);
        });
    }

    /**
     * Complete a task, update order fulfillment status, consume stock reservations when ready, and record milestones.
     */
    public function completeTask(OrderTask $task, User $actor, ?string $note = null): OrderTask
    {
        if ($task->isBlocked()) {
            throw new \DomainException('Task sebelumnya (dependency) belum selesai.');
        }

        return DB::transaction(function () use ($task, $actor, $note): OrderTask {
            $locked = OrderTask::query()->with('workflowStep')->whereKey($task->id)->lockForUpdate()->firstOrFail();

            if ($locked->isBlocked()) {
                throw new \DomainException('Task sebelumnya (dependency) belum selesai.');
            }

            $fromStatus = $locked->status;
            $cleanNote = $note !== null ? trim($note) : null;

            $locked->status = OrderTask::STATUS_COMPLETED;
            $locked->completed_at = now();
            if ($cleanNote !== null && $cleanNote !== '') {
                $locked->notes = $locked->notes
                    ? ($locked->notes . "\n" . $cleanNote)
                    : $cleanNote;
            }
            $locked->save();

            TaskHistory::create([
                'order_task_id' => $locked->id,
                'actor_id' => $actor->id,
                'from_status' => $fromStatus,
                'to_status' => OrderTask::STATUS_COMPLETED,
                'note' => $cleanNote ?: 'Task completed',
                'created_at' => now(),
            ]);

            $this->auditService->log(
                action: 'task.completed',
                entity: $locked,
                before: ['status' => $fromStatus],
                after: ['status' => OrderTask::STATUS_COMPLETED],
                reason: $cleanNote,
                actorId: $actor->id,
            );

            $hasIncompleteBeforeReadyToShip = OrderTask::query()
                ->where('order_id', $locked->order_id)
                ->where('id', '!=', $locked->id)
                ->where(function ($q): void {
                    $q->whereNull('code')->orWhere('code', '!=', 'ready_to_ship');
                })
                ->where('status', '!=', OrderTask::STATUS_COMPLETED)
                ->exists();

            $allTasksCompleted = ! OrderTask::query()
                ->where('order_id', $locked->order_id)
                ->where('status', '!=', OrderTask::STATUS_COMPLETED)
                ->exists();

            if ($locked->code === 'packing' || ! $hasIncompleteBeforeReadyToShip || $allTasksCompleted) {
                if (class_exists(Order::class)) {
                    $order = Order::query()->whereKey($locked->order_id)->first();
                    if ($order && ! in_array($order->status, ['shipped', 'delivered', 'completed', 'cancelled'], true)) {
                        $order->fulfillment_status = 'ready_to_ship';
                        $order->status = 'ready_to_ship';
                        $order->save();
                    }
                }

                $activeReservations = StockReservation::query()
                    ->where('order_id', $locked->order_id)
                    ->where('status', StockReservation::STATUS_ACTIVE)
                    ->get();

                foreach ($activeReservations as $reservation) {
                    $this->inventoryLedger->consumeReservation(
                        reservation: $reservation,
                        reason: "Order #{$locked->order_id} fulfilled at step {$locked->code}",
                        actorId: $actor->id,
                    );
                }
            }

            $this->recordPublicMilestoneSafely($locked, $actor);

            return $locked->fresh(['workflowStep', 'assignee', 'dependencies']);
        });
    }

    /**
     * Return a task for revision with mandatory reason and optional proof attachment.
     */
    public function requestRevision(
        OrderTask $task,
        User $qcUser,
        string $reason,
        ?UploadedFile $attachment = null
    ): OrderTask {
        $trimmedReason = trim($reason);
        if ($trimmedReason === '') {
            throw new \InvalidArgumentException('Alasan revisi wajib diisi.');
        }

        return DB::transaction(function () use ($task, $qcUser, $trimmedReason, $attachment): OrderTask {
            $locked = OrderTask::query()->whereKey($task->id)->lockForUpdate()->firstOrFail();
            $fromStatus = $locked->status;

            $locked->status = OrderTask::STATUS_REVISION;
            $locked->notes = $locked->notes
                ? ($locked->notes . "\n[Revision] " . $trimmedReason)
                : ("[Revision] " . $trimmedReason);
            $locked->save();

            TaskHistory::create([
                'order_task_id' => $locked->id,
                'actor_id' => $qcUser->id,
                'from_status' => $fromStatus,
                'to_status' => OrderTask::STATUS_REVISION,
                'note' => $trimmedReason,
                'created_at' => now(),
            ]);

            $this->auditService->log(
                action: 'task.revision_requested',
                entity: $locked,
                before: ['status' => $fromStatus],
                after: ['status' => OrderTask::STATUS_REVISION],
                reason: $trimmedReason,
                actorId: $qcUser->id,
            );

            if ($attachment !== null) {
                $path = Storage::disk('local')->putFile('task-attachments', $attachment);

                TaskAttachment::create([
                    'order_task_id' => $locked->id,
                    'uploaded_by' => $qcUser->id,
                    'disk' => 'local',
                    'path' => $path,
                    'original_name' => $attachment->getClientOriginalName(),
                    'mime_type' => $attachment->getClientMimeType(),
                    'size_bytes' => $attachment->getSize() ?: 0,
                ]);
            }

            return $locked->fresh(['workflowStep', 'assignee', 'attachments']);
        });
    }

    /**
     * Reassign a task to another user with mandatory audit reason.
     */
    public function reassignTask(
        OrderTask $task,
        User $newAssignee,
        User $manager,
        string $reason
    ): OrderTask {
        $trimmedReason = trim($reason);
        if ($trimmedReason === '') {
            throw new \InvalidArgumentException('Alasan reassignment wajib diisi.');
        }

        return DB::transaction(function () use ($task, $newAssignee, $manager, $trimmedReason): OrderTask {
            $locked = OrderTask::query()->whereKey($task->id)->lockForUpdate()->firstOrFail();
            $fromUserId = $locked->assigned_to;
            $fromStatus = $locked->status;

            $locked->assigned_to = $newAssignee->id;
            if ($locked->status === OrderTask::STATUS_PENDING) {
                $locked->status = OrderTask::STATUS_ASSIGNED;
            }
            $locked->save();

            TaskAssignment::create([
                'order_task_id' => $locked->id,
                'from_user_id' => $fromUserId,
                'to_user_id' => $newAssignee->id,
                'assigned_by' => $manager->id,
                'reason' => $trimmedReason,
                'created_at' => now(),
            ]);

            TaskHistory::create([
                'order_task_id' => $locked->id,
                'actor_id' => $manager->id,
                'from_status' => $fromStatus,
                'to_status' => $locked->status,
                'note' => "Reassigned to {$newAssignee->name}: {$trimmedReason}",
                'created_at' => now(),
            ]);

            $this->auditService->log(
                action: 'task.reassigned',
                entity: $locked,
                before: [
                    'assigned_to' => $fromUserId,
                    'status' => $fromStatus,
                ],
                after: [
                    'assigned_to' => $newAssignee->id,
                    'status' => $locked->status,
                ],
                reason: $trimmedReason,
                actorId: $manager->id,
            );

            return $locked->fresh(['workflowStep', 'assignee', 'assignments']);
        });
    }

    /**
     * Safely delegate public milestone recording to ShippingTrackingService when available.
     */
    private function recordPublicMilestoneSafely(OrderTask $task, User $actor): void
    {
        $step = $task->workflowStep;
        if (! $step || ! $step->is_public_milestone) {
            return;
        }

        if (! class_exists(\App\Services\ShippingTrackingService::class) || ! class_exists(Order::class)) {
            return;
        }

        $order = Order::query()->whereKey($task->order_id)->first();
        if (! $order) {
            return;
        }

        $milestoneCode = (string) ($task->code ?: $step->code);
        $title = (string) ($step->public_milestone_title ?: $task->title);

        try {
            $trackingService = app(\App\Services\ShippingTrackingService::class);
            if (method_exists($trackingService, 'recordMilestone')) {
                $trackingService->recordMilestone(
                    order: $order,
                    milestoneCode: $milestoneCode,
                    title: $title,
                    description: "Tahap {$title} diperbarui.",
                    isPublic: true,
                );
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
