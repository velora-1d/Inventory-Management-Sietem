<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderTask extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_ASSIGNED = 'assigned';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_ON_HOLD = 'on_hold';
    public const STATUS_REVIEW = 'review';
    public const STATUS_REVISION = 'revision';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'order_id',
        'workflow_step_id',
        'title',
        'code',
        'status',
        'assigned_to',
        'department',
        'priority',
        'due_at',
        'started_at',
        'completed_at',
        'notes',
    ];

    protected $casts = [
        'order_id' => 'integer',
        'workflow_step_id' => 'integer',
        'assigned_to' => 'integer',
        'due_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function workflowStep(): BelongsTo
    {
        return $this->belongsTo(WorkflowStep::class, 'workflow_step_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(TaskAssignment::class, 'order_task_id')->orderByDesc('created_at');
    }

    public function dependencies(): BelongsToMany
    {
        return $this->belongsToMany(
            self::class,
            'task_dependencies',
            'order_task_id',
            'depends_on_task_id'
        )->withTimestamps();
    }

    public function histories(): HasMany
    {
        return $this->hasMany(TaskHistory::class, 'order_task_id')->orderByDesc('created_at');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TaskComment::class, 'order_task_id')->orderByDesc('created_at');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TaskAttachment::class, 'order_task_id')->orderByDesc('created_at');
    }

    public function slaStatus(): string
    {
        if ($this->status === self::STATUS_COMPLETED) {
            return 'completed';
        }

        if ($this->due_at && now()->gt($this->due_at)) {
            return 'overdue';
        }

        if ($this->due_at && now()->diffInMinutes($this->due_at, false) <= 10) {
            return 'warning';
        }

        return 'on_track';
    }

    public function isBlocked(): bool
    {
        return $this->dependencies()
            ->where('order_tasks.status', '!=', self::STATUS_COMPLETED)
            ->exists();
    }
}
