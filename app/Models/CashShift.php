<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashShift extends Model
{
    use HasFactory;

    protected $fillable = [
        'cash_register_id',
        'user_id',
        'opened_at',
        'closed_at',
        'opening_balance',
        'expected_closing_balance',
        'actual_closing_balance',
        'variance',
        'status',
        'notes',
    ];

    protected $casts = [
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
        'opening_balance' => 'decimal:2',
        'expected_closing_balance' => 'decimal:2',
        'actual_closing_balance' => 'decimal:2',
        'variance' => 'decimal:2',
    ];

    public function register(): BelongsTo
    {
        return $this->belongsTo(CashRegister::class, 'cash_register_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
