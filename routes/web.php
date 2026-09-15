<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CashierShiftController;
use App\Models\CashierShift;
use App\Models\Category;
use App\Models\Outlet;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Authentication Routes
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->name('login.post');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Protected POS Routes (Must Login First)
Route::middleware('auth')->group(function () {
    Route::get('/', function () {
        $user = Auth::user();
        $outlet = $user->outlet ?? Outlet::first();

        // Get current cashier's active shift
        $activeShift = CashierShift::where('user_id', $user->id)
            ->where('status', 'open')
            ->latest()
            ->first();

        $categories = Category::where('is_active', true)->withCount('products')->get();
        $products = Product::where('is_active', true)->with(['category', 'variants'])->get();

        return view('pos', [
            'outlet' => $outlet,
            'cashier' => $user,
            'activeShift' => $activeShift,
            'categories' => $categories,
            'products' => $products,
        ]);
    })->name('pos.index');

    // Shift Management
    Route::post('/shift/open', [CashierShiftController::class, 'open'])->name('shift.open');
    Route::post('/shift/close', [CashierShiftController::class, 'close'])->name('shift.close');
    Route::get('/shift/current', [CashierShiftController::class, 'current'])->name('shift.current');
});
