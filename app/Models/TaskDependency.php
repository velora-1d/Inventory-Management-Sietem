<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskDependency extends Model
{
    protected $fillable = [
        'order_task_id',
        'depends_on_task_id',
    ];

    protected $casts = [
        'order_task_id' => 'integer',
        'depends_on_task_id' => 'integer',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(OrderTask::class, 'order_task_id');
    }

    public function dependsOnTask(): BelongsTo
    {
        return $this->belongsTo(OrderTask::class, 'depends_on_task_id');
    }
}
