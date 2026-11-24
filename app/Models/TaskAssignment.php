<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskAssignment extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'order_task_id',
        'from_user_id',
        'to_user_id',
        'assigned_by',
        'reason',
        'created_at',
    ];

    protected $casts = [
        'order_task_id' => 'integer',
        'from_user_id' => 'integer',
        'to_user_id' => 'integer',
        'assigned_by' => 'integer',
        'created_at' => 'datetime',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(OrderTask::class, 'order_task_id');
    }

    public function fromUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    public function toUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
