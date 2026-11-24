<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Refund extends Model
{
    use HasFactory;

    public const STATUS_REQUESTED = 'requested';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_PROCESSED = 'processed';
    public const STATUS_REJECTED = 'rejected';

    protected $table = 'refunds';

    protected $fillable = [
        'refund_number',
        'order_id',
        'sale_id',
        'amount',
        'method',
        'status',
        'reason',
        'reference',
        'requested_by',
        'processed_by',
        'processed_at',
    ];

    protected $casts = [
        'order_id' => 'integer',
        'sale_id' => 'integer',
        'amount' => 'decimal:2',
        'requested_by' => 'integer',
        'processed_by' => 'integer',
        'processed_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class, 'sale_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function statusBadgeClasses(): string
    {
        return match ($this->status) {
            self::STATUS_APPROVED => 'bg-blue-50 text-[#2563EB] border-blue-200',
            self::STATUS_PROCESSED => 'bg-emerald-50 text-[#10B981] border-emerald-200',
            self::STATUS_REJECTED => 'bg-red-50 text-[#DC2626] border-red-200',
            default => 'bg-amber-50 text-[#D97706] border-amber-200',
        };
    }
}
