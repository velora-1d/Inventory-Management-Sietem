<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number',
        'public_tracking_token',
        'user_id',
        'customer_id',
        'customer_address_id',
        'status',
        'payment_status',
        'fulfillment_status',
        'shipment_status',
        'delivery_type',
        'subtotal',
        'discount_total',
        'tax_total',
        'shipping_cost',
        'total',
        'notes',
        'confirmed_at',
        'completed_at',
        'cancelled_at',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'tax_total' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'total' => 'decimal:2',
        'confirmed_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function address(): BelongsTo
    {
        return $this->belongsTo(CustomerAddress::class, 'customer_address_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(OrderPayment::class);
    }

    public function latestPayment(): HasOne
    {
        return $this->hasOne(OrderPayment::class)->latestOfMany();
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->orderBy('id');
    }

    public function tasks(): ?HasMany
    {
        if (! class_exists(\App\Models\OrderTask::class)) {
            return null;
        }

        return $this->hasMany(\App\Models\OrderTask::class);
    }

    public function shipment(): ?HasOne
    {
        if (! class_exists(\App\Models\Shipment::class)) {
            return null;
        }

        return $this->hasOne(\App\Models\Shipment::class);
    }

    public function trackingEvents(): ?HasMany
    {
        if (! class_exists(\App\Models\OrderTrackingEvent::class)) {
            return null;
        }

        return $this->hasMany(\App\Models\OrderTrackingEvent::class)->orderBy('id');
    }

    public function isCancellable(): bool
    {
        return ! in_array($this->status, [
            'processing',
            'ready_to_ship',
            'shipped',
            'delivered',
            'completed',
            'cancelled',
        ], true);
    }

    public function statusBadgeClasses(): string
    {
        return match ($this->status) {
            'pending_payment' => 'bg-amber-50 text-[#D97706] border-amber-200',
            'confirmed', 'processing', 'ready_to_ship' => 'bg-blue-50 text-[#2563EB] border-blue-200',
            'shipped' => 'bg-teal-50 text-[#0F766E] border-teal-200',
            'delivered', 'completed' => 'bg-emerald-50 text-[#10B981] border-emerald-200',
            'cancelled' => 'bg-red-50 text-[#DC2626] border-red-200',
            default => 'bg-slate-100 text-[#142B4A] border-[#DCE3EA]',
        };
    }

    public function paymentBadgeClasses(): string
    {
        return match ($this->payment_status) {
            'paid' => 'bg-emerald-50 text-[#10B981] border-emerald-200',
            'waiting_verification' => 'bg-blue-50 text-[#2563EB] border-blue-200',
            'failed' => 'bg-red-50 text-[#DC2626] border-red-200',
            default => 'bg-amber-50 text-[#D97706] border-amber-200',
        };
    }
}
