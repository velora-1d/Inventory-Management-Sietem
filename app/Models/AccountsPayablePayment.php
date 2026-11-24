<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountsPayablePayment extends Model
{
    use HasFactory;

    protected $table = 'accounts_payable_payments';

    protected $fillable = [
        'accounts_payable_id',
        'amount',
        'payment_method',
        'payment_date',
        'reference',
        'finance_transaction_id',
        'paid_by',
        'notes',
    ];

    protected $casts = [
        'accounts_payable_id' => 'integer',
        'amount' => 'decimal:2',
        'payment_date' => 'date',
        'finance_transaction_id' => 'integer',
        'paid_by' => 'integer',
    ];

    public function accountsPayable(): BelongsTo
    {
        return $this->belongsTo(AccountsPayable::class, 'accounts_payable_id');
    }

    public function payer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    public function financeTransaction(): BelongsTo
    {
        return $this->belongsTo(FinanceTransaction::class, 'finance_transaction_id');
    }
}
