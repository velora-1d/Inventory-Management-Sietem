<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockReservation extends Model
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_CONSUMED = 'consumed';
    public const STATUS_RELEASED = 'released';
    public const STATUS_EXPIRED = 'expired';

    protected $fillable = [
        'warehouse_id',
        'product_id',
        'order_id',
        'reference_type',
        'reference_id',
        'quantity',
        'status',
        'expires_at',
        'created_by',
    ];

    protected $casts = [
        'order_id' => 'integer',
        'reference_id' => 'integer',
        'quantity' => 'integer',
        'expires_at' => 'datetime',
    ];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
