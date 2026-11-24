<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkflowStep extends Model
{
    protected $fillable = [
        'order_workflow_id',
        'step_order',
        'name',
        'code',
        'department',
        'default_role',
        'sla_minutes',
        'depends_on_step_id',
        'is_public_milestone',
        'public_milestone_title',
    ];

    protected $casts = [
        'order_workflow_id' => 'integer',
        'step_order' => 'integer',
        'sla_minutes' => 'integer',
        'depends_on_step_id' => 'integer',
        'is_public_milestone' => 'boolean',
    ];

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(OrderWorkflow::class, 'order_workflow_id');
    }

    public function dependsOn(): BelongsTo
    {
        return $this->belongsTo(self::class, 'depends_on_step_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(OrderTask::class, 'workflow_step_id');
    }
}
