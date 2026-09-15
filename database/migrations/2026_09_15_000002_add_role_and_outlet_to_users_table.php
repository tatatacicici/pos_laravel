<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('cashier')->after('password'); // admin, manager, cashier
            $table->foreignId('outlet_id')->nullable()->after('role')->constrained('outlets')->nullOnDelete();
            $table->string('phone')->nullable()->after('email');
            $table->boolean('is_active')->default(true)->after('outlet_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['outlet_id']);
            $table->dropColumn(['role', 'outlet_id', 'phone', 'is_active']);
        });
    }
};
