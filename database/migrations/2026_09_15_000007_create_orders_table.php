<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();
            $table->foreignId('outlet_id')->constrained('outlets')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // cashier
            $table->foreignId('cashier_shift_id')->nullable()->constrained('cashier_shifts')->nullOnDelete();
            $table->string('customer_name')->default('Walk-in Customer');
            $table->string('customer_phone')->nullable();
            $table->string('customer_email')->nullable();
            $table->string('order_type')->default('retail'); // retail, dine_in, take_away, delivery
            $table->string('table_number')->nullable();
            $table->decimal('subtotal', 14, 2);
            $table->decimal('discount_amount', 14, 2)->default(0.00);
            $table->decimal('tax_amount', 14, 2)->default(0.00);
            $table->decimal('service_charge', 14, 2)->default(0.00);
            $table->decimal('total_amount', 14, 2);
            $table->string('order_status')->default('pending'); // OrderStatus enum
            $table->string('payment_status')->default('pending'); // PaymentStatus enum
            $table->string('payment_method')->nullable(); // PaymentMethod enum
            $table->text('notes')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
