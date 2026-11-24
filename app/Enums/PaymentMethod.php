<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case CASH = 'cash';
    case TRANSFER = 'transfer';
    case QRIS = 'qris';
    case EWALLET = 'ewallet';
    case VIRTUAL_ACCOUNT = 'virtual_account';

    public function label(): string
    {
        return match ($this) {
            self::CASH => 'Cash',
            self::TRANSFER => 'Bank Transfer',
            self::QRIS => 'QRIS',
            self::EWALLET => 'E-Wallet',
            self::VIRTUAL_ACCOUNT => 'Virtual Account',
        };
    }
}
