<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderApproval extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'purchase_order_id',
        'actor_id',
        'decision',
        'revision_number',
        'notes',
        'created_at',
    ];

    protected $casts = [
        'revision_number' => 'integer',
        'created_at' => 'datetime',
    ];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
