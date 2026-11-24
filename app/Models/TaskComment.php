<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskComment extends Model
{
    protected $fillable = [
        'order_task_id',
        'user_id',
        'body',
    ];

    protected $casts = [
        'order_task_id' => 'integer',
        'user_id' => 'integer',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(OrderTask::class, 'order_task_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
