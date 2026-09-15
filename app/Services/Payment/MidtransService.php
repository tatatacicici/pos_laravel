<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Midtrans\Config;
use Midtrans\Snap;

class MidtransService
{
    protected string $serverKey;
    protected string $clientKey;
    protected bool $isProduction;

    public function __construct()
    {
        $this->serverKey = (string) config('midtrans.server_key');
        $this->clientKey = (string) config('midtrans.client_key');
        $this->isProduction = (bool) config('midtrans.is_production', false);

        // Configure Midtrans SDK
        Config::$serverKey = $this->serverKey;
        Config::$clientKey = $this->clientKey;
        Config::$isProduction = $this->isProduction;
        Config::$isSanitized = (bool) config('midtrans.is_sanitized', true);
        Config::$is3ds = (bool) config('midtrans.is_3ds', true);
    }

    /**
     * Create Midtrans Snap transaction for an Order.
     *
     * @throws Exception
     */
    public function createSnapTransaction(Order $order): Payment
    {
        $order->loadMissing('items.product');

        $itemDetails = [];
        foreach ($order->items as $item) {
            $itemDetails[] = [
                'id' => (string) ($item->product_variant_id ? "VAR-{$item->product_variant_id}" : "PROD-{$item->product_id}"),
                'price' => (int) round((float) $item->unit_price),
                'quantity' => (int) $item->quantity,
                'name' => mb_substr($item->variant_name ? "{$item->product_name} ({$item->variant_name})" : $item->product_name, 0, 50),
            ];
        }

        // Add tax as item if applicable
        if ((float) $order->tax_amount > 0) {
            $itemDetails[] = [
                'id' => 'TAX',
                'price' => (int) round((float) $order->tax_amount),
                'quantity' => 1,
                'name' => 'PPN / Tax',
            ];
        }

        // Add service charge if applicable
        if ((float) $order->service_charge > 0) {
            $itemDetails[] = [
                'id' => 'SERVICE_CHARGE',
                'price' => (int) round((float) $order->service_charge),
                'quantity' => 1,
                'name' => 'Service Charge',
            ];
        }

        // Subtract discount if applicable
        if ((float) $order->discount_amount > 0) {
            $itemDetails[] = [
                'id' => 'DISCOUNT',
                'price' => -(int) round((float) $order->discount_amount),
                'quantity' => 1,
                'name' => 'Discount',
            ];
        }

        $params = [
            'transaction_details' => [
                'order_id' => $order->invoice_number,
                'gross_amount' => (int) round((float) $order->total_amount),
            ],
            'item_details' => $itemDetails,
            'customer_details' => [
                'first_name' => $order->customer_name ?: 'Customer',
                'email' => $order->customer_email ?: 'customer@example.com',
                'phone' => $order->customer_phone ?: '08123456789',
            ],
        ];

        // Call Midtrans Snap API
        $snapResponse = Snap::createTransaction($params);

        // Record payment in database
        return Payment::updateOrCreate(
            ['order_id' => $order->id],
            [
                'payment_method' => PaymentMethod::MIDTRANS_SNAP,
                'payment_status' => PaymentStatus::PENDING,
                'amount' => $order->total_amount,
                'snap_token' => $snapResponse->token,
                'snap_redirect_url' => $snapResponse->redirect_url,
                'payload' => (array) $snapResponse,
            ]
        );
    }

    /**
     * Timing-safe verification of Midtrans Webhook signature.
     * Best practice from "Securing Laravel" by Stephen Rees-Carter.
     */
    public function verifyWebhookSignature(string $orderId, string $statusCode, string $grossAmount, string $receivedSignature): bool
    {
        $computedSignature = hash('sha512', $orderId . $statusCode . $grossAmount . $this->serverKey);

        return hash_equals($computedSignature, $receivedSignature);
    }

    /**
     * Handle incoming webhook notification payload from Midtrans.
     *
     * @throws Exception
     */
    public function handleWebhookNotification(array $payload): Payment
    {
        $orderId = (string) ($payload['order_id'] ?? '');
        $statusCode = (string) ($payload['status_code'] ?? '');
        $grossAmount = (string) ($payload['gross_amount'] ?? '');
        $signatureKey = (string) ($payload['signature_key'] ?? '');
        $transactionStatus = (string) ($payload['transaction_status'] ?? '');
        $fraudStatus = (string) ($payload['fraud_status'] ?? '');
        $paymentType = (string) ($payload['payment_type'] ?? '');
        $transactionId = (string) ($payload['transaction_id'] ?? '');

        // Security check: Validate signature
        if (!$this->verifyWebhookSignature($orderId, $statusCode, $grossAmount, $signatureKey)) {
            Log::warning('Midtrans Webhook: Invalid signature detected', ['order_id' => $orderId]);
            throw new Exception('Invalid signature key');
        }

        $order = Order::where('invoice_number', $orderId)->first();
        if (!$order) {
            Log::error('Midtrans Webhook: Order not found', ['invoice_number' => $orderId]);
            throw new Exception("Order not found: {$orderId}");
        }

        $payment = Payment::firstOrNew(['order_id' => $order->id]);
        $payment->transaction_id = $transactionId;
        $payment->payment_type = $paymentType;
        $payment->payload = $payload;

        // Parse status according to Midtrans documentation
        $paymentStatus = PaymentStatus::PENDING;
        $orderStatus = OrderStatus::PENDING;

        if ($transactionStatus === 'capture') {
            if ($fraudStatus === 'accept') {
                $paymentStatus = PaymentStatus::CAPTURE;
                $orderStatus = OrderStatus::COMPLETED;
                $payment->paid_at = now();
            } else {
                $paymentStatus = PaymentStatus::DENY;
                $orderStatus = OrderStatus::CANCELLED;
            }
        } elseif ($transactionStatus === 'settlement') {
            $paymentStatus = PaymentStatus::SETTLEMENT;
            $orderStatus = OrderStatus::COMPLETED;
            $payment->paid_at = now();
        } elseif (in_array($transactionStatus, ['cancel', 'deny'], true)) {
            $paymentStatus = PaymentStatus::CANCEL;
            $orderStatus = OrderStatus::CANCELLED;
        } elseif ($transactionStatus === 'expire') {
            $paymentStatus = PaymentStatus::EXPIRE;
            $orderStatus = OrderStatus::CANCELLED;
        } elseif ($transactionStatus === 'pending') {
            $paymentStatus = PaymentStatus::PENDING;
            $orderStatus = OrderStatus::PENDING;
        }

        DB::transaction(function () use ($payment, $order, $paymentStatus, $orderStatus) {
            $payment->payment_status = $paymentStatus;
            $payment->save();

            $order->payment_status = $paymentStatus;
            $order->order_status = $orderStatus;
            if ($orderStatus === OrderStatus::COMPLETED && !$order->completed_at) {
                $order->completed_at = now();
            }
            $order->save();
        });

        Log::info("Midtrans Webhook: Successfully processed order {$orderId} with status {$paymentStatus->value}");

        return $payment;
    }
}
