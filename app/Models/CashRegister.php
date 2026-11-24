<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashRegister extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'location',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function shifts(): HasMany
    {
        return $this->hasMany(CashShift::class, 'cash_register_id');
    }
}
