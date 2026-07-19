<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case CASH = 'cash';
    case TRANSFER = 'transfer';
    case QRIS = 'qris';

    public function label(): string
    {
        return match ($this) {
            self::CASH => 'Cash',
            self::TRANSFER => 'Bank Transfer',
            self::QRIS => 'QRIS',
        };
    }
}
