<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    public $timestamps = false;

    public const TYPE_IN = 'IN';
    public const TYPE_OUT = 'OUT';
    public const TYPE_RESERVE = 'RESERVE';
    public const TYPE_RELEASE = 'RELEASE';
    public const TYPE_ADJUSTMENT = 'ADJUSTMENT';
    public const TYPE_RETURN_OUT = 'RETURN_OUT';
    public const TYPE_RETURN_IN = 'RETURN_IN';

    protected $fillable = [
        'warehouse_id',
        'product_id',
        'type',
        'qty',
        'balance_before',
        'balance_after',
        'reference_type',
        'reference_id',
        'reason',
        'created_by',
        'created_at',
    ];

    protected $casts = [
        'qty' => 'integer',
        'balance_before' => 'integer',
        'balance_after' => 'integer',
        'reference_id' => 'integer',
        'created_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::updating(function (): void {
            throw new \LogicException('Stock movements are immutable.');
        });

        static::deleting(function (): void {
            throw new \LogicException('Stock movements are immutable.');
        });
    }

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
