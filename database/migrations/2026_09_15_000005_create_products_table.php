<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('outlet_id')->nullable()->constrained('outlets')->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('sku')->nullable()->unique();
            $table->string('barcode')->nullable()->index();
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->string('type')->default('physical'); // physical, service, composite
            $table->decimal('base_price', 12, 2);
            $table->decimal('cost_price', 12, 2)->default(0.00);
            $table->boolean('track_stock')->default(true);
            $table->integer('current_stock')->default(0);
            $table->integer('alert_low_stock')->default(5);
            $table->string('unit')->default('pcs'); // pcs, kg, cup, portion, box
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
