<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->string('payment_method'); // PaymentMethod enum
            $table->string('payment_status')->default('pending'); // PaymentStatus enum
            $table->decimal('amount', 14, 2);
            $table->decimal('cash_received', 14, 2)->nullable();
            $table->decimal('change_amount', 14, 2)->nullable();
            
            // Midtrans specific fields
            $table->string('transaction_id')->nullable()->index(); // Midtrans transaction ID
            $table->string('snap_token')->nullable();
            $table->string('snap_redirect_url')->nullable();
            $table->string('payment_type')->nullable(); // qris, gopay, bank_transfer, credit_card
            $table->string('va_number')->nullable();
            $table->string('bank')->nullable();
            $table->json('payload')->nullable(); // Raw payload from gateway for audit
            
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
