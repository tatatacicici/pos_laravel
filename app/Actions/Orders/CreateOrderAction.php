<?php

declare(strict_types=1);

namespace App\Actions\Orders;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\StockMovementType;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Outlet;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Services\Payment\MidtransService;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateOrderAction
{
    public function __construct(
        protected MidtransService $midtransService
    ) {}

    /**
     * Create new PoS Order with stock validation & atomicity.
     *
     * @param array $data
     * @return Order
     * @throws Exception
     */
    public function execute(array $data): Order
    {
        return DB::transaction(function () use ($data) {
            $outlet = Outlet::findOrFail($data['outlet_id']);

            // 1. Calculate items subtotal and check stock
            $subtotal = 0.00;
            $itemsData = [];

            foreach ($data['items'] as $item) {
                $product = Product::lockForUpdate()->findOrFail($item['product_id']);
                $variant = null;
                $unitPrice = (float) $product->base_price;

                if (!empty($item['product_variant_id'])) {
                    $variant = ProductVariant::lockForUpdate()->findOrFail($item['product_variant_id']);
                    $unitPrice += (float) $variant->additional_price;
                }

                $quantity = (int) $item['quantity'];

                // Check stock if tracked
                if ($product->track_stock) {
                    $stockAvailable = $variant ? $variant->stock : $product->current_stock;
                    if ($stockAvailable < $quantity) {
                        throw new Exception("Stok untuk produk {$product->name} tidak mencukupi (tersedia: {$stockAvailable})");
                    }
                }

                $itemDiscount = (float) ($item['discount_amount'] ?? 0.00);
                $itemSubtotal = $unitPrice * $quantity;
                $itemTotal = max(0.00, $itemSubtotal - $itemDiscount);

                $subtotal += $itemTotal;

                $itemsData[] = [
                    'product' => $product,
                    'variant' => $variant,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'cost_price' => (float) $product->cost_price,
                    'subtotal' => $itemSubtotal,
                    'discount_amount' => $itemDiscount,
                    'total_amount' => $itemTotal,
                    'notes' => $item['notes'] ?? null,
                ];
            }

            // 2. Calculate tax and service charge
            $discountAmount = (float) ($data['discount_amount'] ?? 0.00);
            $taxPercentage = (float) ($data['tax_percentage'] ?? $outlet->tax_percentage);
            $serviceChargePercentage = (float) ($data['service_charge_percentage'] ?? $outlet->service_charge_percentage);

            $taxAmount = ($subtotal - $discountAmount) * ($taxPercentage / 100);
            $serviceCharge = ($subtotal - $discountAmount) * ($serviceChargePercentage / 100);
            $totalAmount = max(0.00, ($subtotal - $discountAmount) + $taxAmount + $serviceCharge);

            // 3. Generate unique invoice number
            $datePrefix = date('Ymd');
            $randomSuffix = strtoupper(Str::random(4));
            $invoiceNumber = "INV-{$datePrefix}-{$randomSuffix}";

            $paymentMethod = !empty($data['payment_method'])
                ? PaymentMethod::from($data['payment_method'])
                : PaymentMethod::CASH;

            $cashReceived = (float) ($data['cash_received'] ?? 0.00);
            $isCashPaid = ($paymentMethod === PaymentMethod::CASH && $cashReceived >= $totalAmount);

            // 4. Create Order
            $order = Order::create([
                'invoice_number' => $invoiceNumber,
                'outlet_id' => $outlet->id,
                'user_id' => $data['user_id'] ?? null,
                'cashier_shift_id' => $data['cashier_shift_id'] ?? null,
                'customer_name' => $data['customer_name'] ?? 'Walk-in Customer',
                'customer_phone' => $data['customer_phone'] ?? null,
                'customer_email' => $data['customer_email'] ?? null,
                'order_type' => $data['order_type'] ?? 'retail',
                'table_number' => $data['table_number'] ?? null,
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'tax_amount' => $taxAmount,
                'service_charge' => $serviceCharge,
                'total_amount' => $totalAmount,
                'order_status' => $isCashPaid ? OrderStatus::COMPLETED : OrderStatus::PENDING,
                'payment_status' => $isCashPaid ? PaymentStatus::SETTLEMENT : PaymentStatus::PENDING,
                'payment_method' => $paymentMethod,
                'notes' => $data['notes'] ?? null,
                'completed_at' => $isCashPaid ? now() : null,
            ]);

            // 5. Create OrderItems & Adjust Stock
            foreach ($itemsData as $row) {
                /** @var Product $prod */
                $prod = $row['product'];
                /** @var ProductVariant|null $var */
                $var = $row['variant'];
                $qty = $row['quantity'];

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $prod->id,
                    'product_variant_id' => $var?->id,
                    'product_name' => $prod->name,
                    'variant_name' => $var?->name,
                    'unit_price' => $row['unit_price'],
                    'cost_price' => $row['cost_price'],
                    'quantity' => $qty,
                    'subtotal' => $row['subtotal'],
                    'discount_amount' => $row['discount_amount'],
                    'total_amount' => $row['total_amount'],
                    'notes' => $row['notes'],
                ]);

                // Stock deduction
                if ($prod->track_stock) {
                    $beforeStock = $prod->current_stock;
                    $afterStock = $beforeStock - $qty;

                    $prod->update(['current_stock' => $afterStock]);

                    if ($var) {
                        $var->decrement('stock', $qty);
                    }

                    StockMovement::create([
                        'outlet_id' => $outlet->id,
                        'product_id' => $prod->id,
                        'product_variant_id' => $var?->id,
                        'user_id' => $data['user_id'] ?? null,
                        'type' => StockMovementType::SALE,
                        'quantity' => -$qty,
                        'before_stock' => $beforeStock,
                        'after_stock' => $afterStock,
                        'reference_type' => Order::class,
                        'reference_id' => $order->id,
                        'notes' => "Penjualan Order #{$invoiceNumber}",
                    ]);
                }
            }

            // 6. Record Payment
            if ($paymentMethod === PaymentMethod::CASH) {
                $changeAmount = max(0.00, $cashReceived - $totalAmount);
                Payment::create([
                    'order_id' => $order->id,
                    'payment_method' => PaymentMethod::CASH,
                    'payment_status' => $isCashPaid ? PaymentStatus::SETTLEMENT : PaymentStatus::PENDING,
                    'amount' => $totalAmount,
                    'cash_received' => $cashReceived,
                    'change_amount' => $changeAmount,
                    'paid_at' => $isCashPaid ? now() : null,
                ]);
            } elseif ($paymentMethod->isOnlineGateway()) {
                // If Midtrans Snap is selected, request Snap Token
                $this->midtransService->createSnapTransaction($order);
            }

            return $order->load(['items', 'outlet', 'latestPayment']);
        });
    }
}
