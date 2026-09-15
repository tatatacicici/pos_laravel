<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class CreateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'outlet_id' => ['required', 'exists:outlets,id'],
            'cashier_shift_id' => ['nullable', 'exists:cashier_shifts,id'],
            'customer_name' => ['nullable', 'string', 'max:100'],
            'customer_phone' => ['nullable', 'string', 'max:20'],
            'customer_email' => ['nullable', 'email', 'max:100'],
            'order_type' => ['nullable', 'string', 'in:retail,dine_in,take_away,delivery'],
            'table_number' => ['nullable', 'string', 'max:20'],
            'payment_method' => ['required', new Enum(PaymentMethod::class)],
            'cash_received' => ['nullable', 'numeric', 'min:0'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.product_variant_id' => ['nullable', 'exists:product_variants,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.discount_amount' => ['nullable', 'numeric', 'min:0'],
            'items.*.notes' => ['nullable', 'string', 'max:255'],
        ];
    }
}
