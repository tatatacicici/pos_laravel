<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'payment_method',
        'payment_status',
        'amount',
        'cash_received',
        'change_amount',
        'transaction_id',
        'snap_token',
        'snap_redirect_url',
        'payment_type',
        'va_number',
        'bank',
        'payload',
        'paid_at',
    ];

    protected $casts = [
        'payment_method' => PaymentMethod::class,
        'payment_status' => PaymentStatus::class,
        'amount' => 'decimal:2',
        'cash_received' => 'decimal:2',
        'change_amount' => 'decimal:2',
        'payload' => 'array',
        'paid_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
