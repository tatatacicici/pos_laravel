<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Orders\CreateOrderAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateOrderRequest;
use App\Models\Order;
use App\Services\Payment\MidtransService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Order::with(['outlet', 'cashier', 'items.product', 'latestPayment'])
            ->latest();

        if ($request->has('outlet_id')) {
            $query->where('outlet_id', $request->query('outlet_id'));
        }

        if ($request->has('order_status')) {
            $query->where('order_status', $request->query('order_status'));
        }

        if ($request->has('payment_status')) {
            $query->where('payment_status', $request->query('payment_status'));
        }

        $orders = $query->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $orders,
        ]);
    }

    public function store(CreateOrderRequest $request, CreateOrderAction $action): JsonResponse
    {
        $validated = $request->validated();
        $validated['user_id'] = $request->user()?->id ?? null;

        try {
            $order = $action->execute($validated);

            return response()->json([
                'success' => true,
                'message' => 'Order created successfully',
                'data' => $order,
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function show(Order $order): JsonResponse
    {
        $order->load(['outlet', 'cashier', 'items.product', 'items.variant', 'payments']);

        return response()->json([
            'success' => true,
            'data' => $order,
        ]);
    }

    public function createSnapToken(Order $order, MidtransService $service): JsonResponse
    {
        try {
            $payment = $service->createSnapTransaction($order);

            return response()->json([
                'success' => true,
                'data' => [
                    'order_id' => $order->id,
                    'invoice_number' => $order->invoice_number,
                    'snap_token' => $payment->snap_token,
                    'snap_redirect_url' => $payment->snap_redirect_url,
                ],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
