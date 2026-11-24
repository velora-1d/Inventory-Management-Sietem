<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockBalance extends Model
{
    protected $fillable = [
        'warehouse_id',
        'product_id',
        'on_hand',
        'reserved',
        'incoming',
    ];

    protected $casts = [
        'on_hand' => 'integer',
        'reserved' => 'integer',
        'incoming' => 'integer',
    ];

    protected $appends = ['available'];

    public function getAvailableAttribute(): int
    {
        return max(0, (int) $this->on_hand - (int) $this->reserved);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
