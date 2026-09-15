<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Webhooks;

use App\Http\Controllers\Controller;
use App\Services\Payment\MidtransService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MidtransWebhookController extends Controller
{
    public function __construct(
        protected MidtransService $midtransService
    ) {}

    /**
     * Handle Midtrans HTTP notification callback.
     */
    public function handle(Request $request): JsonResponse
    {
        $payload = $request->all();

        Log::info('Midtrans Webhook notification received', [
            'order_id' => $payload['order_id'] ?? null,
            'transaction_status' => $payload['transaction_status'] ?? null,
        ]);

        try {
            $payment = $this->midtransService->handleWebhookNotification($payload);

            return response()->json([
                'success' => true,
                'message' => 'Notification processed successfully',
                'data' => [
                    'order_id' => $payment->order_id,
                    'payment_status' => $payment->payment_status->value,
                ],
            ]);
        } catch (Exception $e) {
            Log::error('Midtrans Webhook error: ' . $e->getMessage(), ['payload' => $payload]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
