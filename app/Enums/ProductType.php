<?php

declare(strict_types=1);

namespace App\Enums;

enum ProductType: string
{
    case PHYSICAL = 'physical';
    case SERVICE = 'service';
    case COMPOSITE = 'composite';
}
