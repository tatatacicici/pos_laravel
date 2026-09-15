<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashierShift extends Model
{
    use HasFactory;

    protected $fillable = [
        'outlet_id',
        'user_id',
        'starting_cash',
        'expected_cash',
        'actual_cash',
        'difference',
        'status',
        'opened_at',
        'closed_at',
        'notes',
    ];

    protected $casts = [
        'starting_cash' => 'decimal:2',
        'expected_cash' => 'decimal:2',
        'actual_cash' => 'decimal:2',
        'difference' => 'decimal:2',
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
