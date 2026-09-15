<?php

use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ReceiptController;
use App\Http\Controllers\Api\Webhooks\MidtransWebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('v1')->group(function () {
    // Categories & Products
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/products/{product}', [ProductController::class, 'show']);

    // Orders & Transactions
    Route::get('/orders', [OrderController::class, 'index']);
    Route::post('/orders', [OrderController::class, 'store'])->middleware('throttle:60,1');
    Route::get('/orders/{order}', [OrderController::class, 'show']);
    Route::post('/orders/{order}/snap-token', [OrderController::class, 'createSnapToken'])->middleware('throttle:30,1');

    // PDF Receipt Printing
    Route::get('/orders/{order}/receipt-pdf', [ReceiptController::class, 'thermal']);
    Route::get('/orders/{order}/invoice-pdf', [ReceiptController::class, 'invoice']);

    // Payment Webhooks
    Route::post('/webhooks/midtrans', [MidtransWebhookController::class, 'handle'])
        ->middleware('throttle:120,1');
});
