<?php

declare(strict_types=1);

namespace App\Enums;

enum PaymentMethod: string
{
    case CASH = 'cash';
    case MIDTRANS_SNAP = 'midtrans_snap';
    case QRIS = 'qris';
    case BANK_TRANSFER = 'bank_transfer';
    case CREDIT_CARD = 'credit_card';

    public function isOnlineGateway(): bool
    {
        return in_array($this, [self::MIDTRANS_SNAP, self::QRIS, self::BANK_TRANSFER, self::CREDIT_CARD], true);
    }
}
