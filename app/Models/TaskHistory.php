<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskHistory extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'order_task_id',
        'actor_id',
        'from_status',
        'to_status',
        'note',
        'created_at',
    ];

    protected $casts = [
        'order_task_id' => 'integer',
        'actor_id' => 'integer',
        'created_at' => 'datetime',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(OrderTask::class, 'order_task_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
