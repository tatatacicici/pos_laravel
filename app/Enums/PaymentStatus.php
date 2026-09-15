<?php

declare(strict_types=1);

namespace App\Enums;

enum PaymentStatus: string
{
    case PENDING = 'pending';
    case SETTLEMENT = 'settlement';
    case CAPTURE = 'capture';
    case DENY = 'deny';
    case EXPIRE = 'expire';
    case CANCEL = 'cancel';
    case REFUND = 'refund';

    public function isSuccessful(): bool
    {
        return in_array($this, [self::SETTLEMENT, self::CAPTURE], true);
    }

    public function isFailed(): bool
    {
        return in_array($this, [self::DENY, self::EXPIRE, self::CANCEL], true);
    }
}
