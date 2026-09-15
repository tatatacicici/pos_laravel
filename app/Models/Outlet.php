<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Outlet extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'phone',
        'address',
        'city',
        'tax_percentage',
        'service_charge_percentage',
        'receipt_footer',
        'is_active',
    ];

    protected $casts = [
        'tax_percentage' => 'decimal:2',
        'service_charge_percentage' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function cashierShifts(): HasMany
    {
        return $this->hasMany(CashierShift::class);
    }
}
