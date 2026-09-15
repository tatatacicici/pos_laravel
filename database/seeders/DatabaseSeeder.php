<?php

namespace Database\Seeders;

use App\Enums\ProductType;
use App\Models\CashierShift;
use App\Models\Category;
use App\Models\Outlet;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create Demo Outlet
        $outlet = Outlet::create([
            'name' => 'Kopi & Roti Nusantara (Cabang Pusat)',
            'code' => 'OUT-JKT-01',
            'phone' => '021-88997766',
            'address' => 'Jl. Sudirman No. 45, Jakarta Pusat',
            'city' => 'Jakarta',
            'tax_percentage' => 11.00, // 11% PPN
            'service_charge_percentage' => 5.00, // 5% Service charge
            'receipt_footer' => 'Instagram: @kopirotianusantara | Free Wi-Fi: KOPI-ENAK',
            'is_active' => true,
        ]);

        // 2. Create Users (Admin & Cashier)
        $admin = User::create([
            'name' => 'Administrator POS',
            'email' => 'admin@pos.test',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'outlet_id' => $outlet->id,
            'phone' => '08111222333',
            'is_active' => true,
        ]);

        $cashier = User::create([
            'name' => 'Budi Kasir',
            'email' => 'kasir@pos.test',
            'password' => Hash::make('password'),
            'role' => 'cashier',
            'outlet_id' => $outlet->id,
            'phone' => '08222333444',
            'is_active' => true,
        ]);

        // 3. Create Cashier Shift
        CashierShift::create([
            'outlet_id' => $outlet->id,
            'user_id' => $cashier->id,
            'starting_cash' => 200000.00,
            'expected_cash' => 200000.00,
            'status' => 'open',
            'opened_at' => now(),
            'notes' => 'Shift Pagi - Saldo Awal Rp 200.000',
        ]);

        // 4. Create Categories
        $catBeverage = Category::create([
            'outlet_id' => $outlet->id,
            'name' => 'Coffee & Beverages',
            'slug' => 'coffee-beverages',
            'description' => 'Minuman kopi, teh, dan aneka racikan segar',
            'icon' => 'coffee',
        ]);

        $catFood = Category::create([
            'outlet_id' => $outlet->id,
            'name' => 'Food & Bakery',
            'slug' => 'food-bakery',
            'description' => 'Makanan berat, pastry, dan roti panggang',
            'icon' => 'croissant',
        ]);

        $catRetail = Category::create([
            'outlet_id' => $outlet->id,
            'name' => 'Retail & Merch',
            'slug' => 'retail-merch',
            'description' => 'Barang kemasan, biji kopi, dan merchandise',
            'icon' => 'shopping-bag',
        ]);

        // 5. Create Products & Variants
        // Product 1: Kopi Susu Gula Aren with variants
        $kopiSusu = Product::create([
            'outlet_id' => $outlet->id,
            'category_id' => $catBeverage->id,
            'name' => 'Kopi Susu Gula Aren',
            'slug' => 'kopi-susu-gula-aren',
            'sku' => 'BEV-001',
            'barcode' => '8990001',
            'description' => 'Espresso dengan susu segar dan gula aren premium',
            'type' => ProductType::PHYSICAL,
            'base_price' => 18000.00,
            'cost_price' => 8000.00,
            'track_stock' => true,
            'current_stock' => 100,
            'alert_low_stock' => 10,
            'unit' => 'cup',
        ]);

        ProductVariant::create([
            'product_id' => $kopiSusu->id,
            'name' => 'Regular Cup (Cold)',
            'sku' => 'BEV-001-REG',
            'additional_price' => 0.00,
            'stock' => 60,
        ]);

        ProductVariant::create([
            'product_id' => $kopiSusu->id,
            'name' => 'Large 1 Liter (Bottle)',
            'sku' => 'BEV-001-1L',
            'additional_price' => 52000.00, // Total = 70.000
            'stock' => 40,
        ]);

        // Product 2: Croissant Butter
        Product::create([
            'outlet_id' => $outlet->id,
            'category_id' => $catFood->id,
            'name' => 'French Butter Croissant',
            'slug' => 'french-butter-croissant',
            'sku' => 'BAK-001',
            'barcode' => '8990002',
            'description' => 'Croissant renyah berlapis dengan mentega Prancis asli',
            'type' => ProductType::PHYSICAL,
            'base_price' => 22000.00,
            'cost_price' => 10000.00,
            'track_stock' => true,
            'current_stock' => 35,
            'alert_low_stock' => 5,
            'unit' => 'pcs',
        ]);

        // Product 3: Nasi Goreng Kampung
        Product::create([
            'outlet_id' => $outlet->id,
            'category_id' => $catFood->id,
            'name' => 'Nasi Goreng Spesial Telur',
            'slug' => 'nasi-goreng-spesial-telur',
            'sku' => 'FOD-001',
            'barcode' => '8990003',
            'description' => 'Nasi goreng bumbu rempah dengan telur mata sapi dan kerupuk',
            'type' => ProductType::PHYSICAL,
            'base_price' => 32000.00,
            'cost_price' => 14000.00,
            'track_stock' => true,
            'current_stock' => 50,
            'alert_low_stock' => 5,
            'unit' => 'porsi',
        ]);

        // Product 4: Biji Kopi Arabica 250gr (Retail)
        Product::create([
            'outlet_id' => $outlet->id,
            'category_id' => $catRetail->id,
            'name' => 'Biji Kopi Gayo Arabica 250g',
            'slug' => 'biji-kopi-gayo-arabica-250g',
            'sku' => 'RTL-001',
            'barcode' => '8991234567890',
            'description' => 'Single origin specialty coffee beans from Aceh Gayo',
            'type' => ProductType::PHYSICAL,
            'base_price' => 85000.00,
            'cost_price' => 50000.00,
            'track_stock' => true,
            'current_stock' => 20,
            'alert_low_stock' => 3,
            'unit' => 'pack',
        ]);
    }
}
