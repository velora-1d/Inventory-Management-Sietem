<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccountsPayable extends Model
{
    use HasFactory;

    public const STATUS_UNPAID = 'unpaid';
    public const STATUS_PARTIAL = 'partial';
    public const STATUS_PAID = 'paid';

    protected $table = 'accounts_payable';

    protected $fillable = [
        'supplier_id',
        'supplier_invoice_id',
        'purchase_order_id',
        'invoice_number',
        'total_amount',
        'paid_amount',
        'balance_due',
        'due_date',
        'status',
        'notes',
    ];

    protected $casts = [
        'supplier_id' => 'integer',
        'supplier_invoice_id' => 'integer',
        'purchase_order_id' => 'integer',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'balance_due' => 'decimal:2',
        'due_date' => 'date',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(AccountsPayablePayment::class, 'accounts_payable_id')->orderByDesc('payment_date')->orderByDesc('id');
    }

    public function statusBadgeClasses(): string
    {
        return match ($this->status) {
            self::STATUS_PAID => 'bg-emerald-50 text-[#10B981] border-emerald-200',
            self::STATUS_PARTIAL => 'bg-amber-50 text-[#D97706] border-amber-200',
            default => 'bg-red-50 text-[#DC2626] border-red-200',
        };
    }
}
