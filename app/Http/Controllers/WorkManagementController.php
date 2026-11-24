<?php

namespace App\Http\Controllers;

use App\Models\OrderTask;
use App\Models\OrderWorkflow;
use App\Models\TaskComment;
use App\Models\User;
use App\Models\WorkflowStep;
use App\Services\AuditService;
use App\Services\WorkManagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class WorkManagementController extends Controller
{
    public function board(Request $request): View
    {
        $search = trim((string) $request->input('search', ''));
        $department = trim((string) $request->input('department', ''));
        $priority = trim((string) $request->input('priority', ''));

        $query = OrderTask::query()
            ->with(['order', 'workflowStep', 'assignee', 'dependencies', 'comments.user', 'attachments']);

        if ($department !== '') {
            $query->where('department', $department);
        }

        if ($priority !== '') {
            $query->where('priority', $priority);
        }

        if ($search !== '') {
            $pattern = '%' . mb_strtolower($search) . '%';
            $query->where(function ($q) use ($pattern): void {
                $q->whereRaw('LOWER(title) LIKE ?', [$pattern])
                    ->orWhereRaw('LOWER(COALESCE(code, \'\')) LIKE ?', [$pattern])
                    ->orWhereRaw('LOWER(department) LIKE ?', [$pattern]);
            });
        }

        $tasks = $query->orderBy('due_at')->orderBy('id')->get();

        $columns = [
            'pending_assigned' => $tasks->whereIn('status', [
                OrderTask::STATUS_PENDING,
                OrderTask::STATUS_ASSIGNED,
                OrderTask::STATUS_ON_HOLD,
            ])->values(),
            'in_progress' => $tasks->whereIn('status', [
                OrderTask::STATUS_IN_PROGRESS,
                OrderTask::STATUS_REVIEW,
            ])->values(),
            'revision' => $tasks->where('status', OrderTask::STATUS_REVISION)->values(),
            'completed' => $tasks->where('status', OrderTask::STATUS_COMPLETED)->values(),
        ];

        $departments = OrderTask::query()
            ->select('department')
            ->distinct()
            ->orderBy('department')
            ->pluck('department');

        $users = User::query()->orderBy('name')->get(['id', 'name', 'email']);

        $summary = [
            'total' => $tasks->count(),
            'pending_assigned' => $columns['pending_assigned']->count(),
            'in_progress' => $columns['in_progress']->count(),
            'revision' => $columns['revision']->count(),
            'completed' => $columns['completed']->count(),
            'overdue' => $tasks->filter(fn (OrderTask $task): bool => $task->slaStatus() === 'overdue')->count(),
        ];

        return view('work.board', compact(
            'columns',
            'departments',
            'users',
            'summary',
            'search',
            'department',
            'priority'
        ));
    }

    public function myTasks(Request $request): View
    {
        $status = trim((string) $request->input('status', ''));

        $query = OrderTask::query()
            ->with(['order', 'workflowStep', 'assignee', 'dependencies', 'comments.user', 'attachments', 'histories.actor'])
            ->where('assigned_to', Auth::id());

        if ($status !== '') {
            $query->where('status', $status);
        }

        $tasks = $query
            ->orderByRaw("CASE WHEN status = 'completed' THEN 1 ELSE 0 END")
            ->orderBy('due_at')
            ->orderBy('id')
            ->paginate(15)
            ->withQueryString();

        $summary = [
            'total' => OrderTask::query()->where('assigned_to', Auth::id())->count(),
            'active' => OrderTask::query()->where('assigned_to', Auth::id())->whereIn('status', [
                OrderTask::STATUS_PENDING,
                OrderTask::STATUS_ASSIGNED,
                OrderTask::STATUS_IN_PROGRESS,
            ])->count(),
            'revision' => OrderTask::query()->where('assigned_to', Auth::id())->where('status', OrderTask::STATUS_REVISION)->count(),
            'completed' => OrderTask::query()->where('assigned_to', Auth::id())->where('status', OrderTask::STATUS_COMPLETED)->count(),
        ];

        return view('work.my-tasks', compact('tasks', 'status', 'summary'));
    }

    public function allTasks(Request $request): View
    {
        $search = trim((string) $request->input('search', ''));
        $status = trim((string) $request->input('status', ''));
        $department = trim((string) $request->input('department', ''));
        $priority = trim((string) $request->input('priority', ''));
        $assignedTo = $request->filled('assigned_to') ? (int) $request->input('assigned_to') : null;

        $query = OrderTask::query()
            ->with(['order', 'workflowStep', 'assignee', 'dependencies', 'comments.user', 'attachments']);

        if ($search !== '') {
            $pattern = '%' . mb_strtolower($search) . '%';
            $query->where(function ($q) use ($pattern): void {
                $q->whereRaw('LOWER(title) LIKE ?', [$pattern])
                    ->orWhereRaw('LOWER(COALESCE(code, \'\')) LIKE ?', [$pattern])
                    ->orWhereRaw('LOWER(department) LIKE ?', [$pattern]);
            });
        }

        if ($status !== '') {
            $query->where('status', $status);
        }

        if ($department !== '') {
            $query->where('department', $department);
        }

        if ($priority !== '') {
            $query->where('priority', $priority);
        }

        if ($assignedTo !== null) {
            $query->where('assigned_to', $assignedTo);
        }

        $tasks = $query
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $users = User::query()->orderBy('name')->get(['id', 'name', 'email']);
        $departments = OrderTask::query()->select('department')->distinct()->orderBy('department')->pluck('department');

        $statuses = [
            OrderTask::STATUS_PENDING,
            OrderTask::STATUS_ASSIGNED,
            OrderTask::STATUS_IN_PROGRESS,
            OrderTask::STATUS_ON_HOLD,
            OrderTask::STATUS_REVIEW,
            OrderTask::STATUS_REVISION,
            OrderTask::STATUS_COMPLETED,
            OrderTask::STATUS_CANCELLED,
        ];

        return view('work.tasks', compact(
            'tasks',
            'users',
            'departments',
            'statuses',
            'search',
            'status',
            'department',
            'priority',
            'assignedTo'
        ));
    }

    public function start(OrderTask $task, WorkManagementService $service): RedirectResponse
    {
        $user = Auth::user();
        if (!$user || ($task->assigned_to !== $user->id && !$user->can('work.manage'))) {
            abort(403);
        }

        try {
            $service->startTask($task, $user);

            return back()->with('success', "Task \"{$task->title}\" berhasil dimulai.");
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function complete(Request $request, OrderTask $task, WorkManagementService $service): RedirectResponse
    {
        $user = Auth::user();
        if (!$user || ($task->assigned_to !== $user->id && !$user->can('work.manage'))) {
            abort(403);
        }

        $validated = $request->validate([
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $service->completeTask($task, $user, $validated['note'] ?? null);

            return back()->with('success', "Task \"{$task->title}\" berhasil diselesaikan.");
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function revision(Request $request, OrderTask $task, WorkManagementService $service): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:3', 'max:2000'],
            'attachment' => ['nullable', 'file', 'max:5120', 'mimes:jpg,jpeg,png,webp,pdf,txt,doc,docx'],
        ]);

        try {
            $service->requestRevision(
                $task,
                Auth::user(),
                $validated['reason'],
                $request->file('attachment')
            );

            return back()->with('success', "Task \"{$task->title}\" dikembalikan untuk revisi.");
        } catch (\InvalidArgumentException|\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function reassign(Request $request, OrderTask $task, WorkManagementService $service): RedirectResponse
    {
        $validated = $request->validate([
            'assigned_to' => ['required', 'integer', 'exists:users,id'],
            'reason' => ['required', 'string', 'min:3', 'max:500'],
        ]);

        $newAssignee = User::query()->findOrFail($validated['assigned_to']);

        try {
            $service->reassignTask($task, $newAssignee, Auth::user(), $validated['reason']);

            return back()->with('success', "Task \"{$task->title}\" dialihkan ke {$newAssignee->name}.");
        } catch (\InvalidArgumentException|\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function comment(Request $request, OrderTask $task): RedirectResponse
    {
        $validated = $request->validate([
            'body' => ['required', 'string', 'min:1', 'max:2000'],
        ]);

        TaskComment::create([
            'order_task_id' => $task->id,
            'user_id' => Auth::id(),
            'body' => trim($validated['body']),
        ]);

        return back()->with('success', 'Komentar berhasil ditambahkan.');
    }

    public function workflows(): View
    {
        $workflows = OrderWorkflow::query()
            ->with(['steps.dependsOn'])
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();

        $roles = Role::query()->orderBy('name')->pluck('name');
        if ($roles->isEmpty()) {
            $roles = collect(['warehouse', 'qc_packing', 'shipping', 'admin', 'manager']);
        }

        return view('work.workflows', compact('workflows', 'roles'));
    }

    public function storeWorkflowStep(Request $request, AuditService $auditService): RedirectResponse
    {
        $validated = $request->validate([
            'order_workflow_id' => ['required', 'integer', 'exists:order_workflows,id'],
            'step_order' => ['required', 'integer', 'min:1'],
            'name' => ['required', 'string', 'max:150'],
            'code' => ['required', 'string', 'max:80'],
            'department' => ['required', 'string', 'max:80'],
            'default_role' => ['required', 'string', 'max:80'],
            'sla_minutes' => ['required', 'integer', 'min:1', 'max:10080'],
            'depends_on_step_id' => ['nullable', 'integer', 'exists:workflow_steps,id'],
            'is_public_milestone' => ['nullable', 'boolean'],
            'public_milestone_title' => ['nullable', 'string', 'max:150'],
        ]);

        $step = WorkflowStep::create([
            'order_workflow_id' => $validated['order_workflow_id'],
            'step_order' => $validated['step_order'],
            'name' => $validated['name'],
            'code' => $validated['code'],
            'department' => $validated['department'],
            'default_role' => $validated['default_role'],
            'sla_minutes' => $validated['sla_minutes'],
            'depends_on_step_id' => $validated['depends_on_step_id'] ?? null,
            'is_public_milestone' => (bool) ($validated['is_public_milestone'] ?? false),
            'public_milestone_title' => $validated['public_milestone_title'] ?? null,
        ]);

        $auditService->log(
            action: 'workflow_step.created',
            entity: $step,
            before: null,
            after: $step->toArray(),
            reason: 'Workflow step created via Work Management UI',
            actorId: Auth::id(),
        );

        return back()->with('success', "Step \"{$step->name}\" berhasil ditambahkan.");
    }
}
