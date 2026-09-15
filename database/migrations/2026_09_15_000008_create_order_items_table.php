<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products');
            $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->string('product_name');
            $table->string('variant_name')->nullable();
            $table->decimal('unit_price', 12, 2);
            $table->decimal('cost_price', 12, 2)->default(0.00);
            $table->integer('quantity');
            $table->decimal('subtotal', 14, 2);
            $table->decimal('discount_amount', 14, 2)->default(0.00);
            $table->decimal('total_amount', 14, 2);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
