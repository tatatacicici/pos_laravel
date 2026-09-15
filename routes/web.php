<?php

use App\Models\Category;
use App\Models\Outlet;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $outlet = Outlet::first();
    $cashier = User::where('role', 'cashier')->first() ?? User::first();
    $categories = Category::where('is_active', true)->withCount('products')->get();
    $products = Product::where('is_active', true)->with(['category', 'variants'])->get();

    return view('pos', compact('outlet', 'cashier', 'categories', 'products'));
});
