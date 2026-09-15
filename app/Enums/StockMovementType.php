<?php

declare(strict_types=1);

namespace App\Enums;

enum StockMovementType: string
{
    case INITIAL = 'initial';
    case PURCHASE = 'purchase';
    case SALE = 'sale';
    case ADJUSTMENT = 'adjustment';
    case RETURN = 'return';
}
