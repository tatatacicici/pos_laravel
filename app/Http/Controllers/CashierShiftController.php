<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\CashierShift;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CashierShiftController extends Controller
{
    /**
     * Open a new shift for the authenticated cashier.
     */
    public function open(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'starting_cash' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $user = Auth::user();

        // Check if there is already an open shift for this user
        $existingShift = CashierShift::where('user_id', $user->id)
            ->where('status', 'open')
            ->first();

        if ($existingShift) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda masih memiliki shift aktif yang belum ditutup.',
                    'data' => $existingShift,
                ], 422);
            }
            return back()->withErrors(['shift' => 'Anda masih memiliki shift aktif yang belum ditutup.']);
        }

        $outletId = $user->outlet_id ?? \App\Models\Outlet::first()?->id;

        $shift = CashierShift::create([
            'outlet_id' => $outletId,
            'user_id' => $user->id,
            'starting_cash' => (float) $validated['starting_cash'],
            'expected_cash' => (float) $validated['starting_cash'],
            'status' => 'open',
            'opened_at' => now(),
            'notes' => $validated['notes'] ?? 'Shift dibuka oleh ' . $user->name,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Shift kasir berhasil dibuka! Selamat bertugas.',
                'data' => $shift,
            ], 201);
        }

        return redirect()->route('pos.index')
            ->with('status', 'Shift kasir berhasil dibuka!');
    }

    /**
     * Close the cashier shift.
     */
    public function close(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'actual_cash' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $user = Auth::user();

        $shift = CashierShift::where('user_id', $user->id)
            ->where('status', 'open')
            ->latest()
            ->first();

        if (! $shift) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak ditemukan shift aktif untuk kasir ini.',
                ], 404);
            }
            return back()->withErrors(['shift' => 'Tidak ada shift aktif untuk ditutup.']);
        }

        // Calculate expected cash: starting cash + total CASH payments in this shift
        $cashSales = Order::where('cashier_shift_id', $shift->id)
            ->where('payment_status', 'paid')
            ->where('payment_method', 'cash')
            ->sum('total_amount');

        $expectedCash = (float) $shift->starting_cash + (float) $cashSales;
        $actualCash = (float) $validated['actual_cash'];
        $difference = $actualCash - $expectedCash;

        $shift->update([
            'expected_cash' => $expectedCash,
            'actual_cash' => $actualCash,
            'difference' => $difference,
            'status' => 'closed',
            'closed_at' => now(),
            'notes' => $validated['notes'] ?? $shift->notes,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Shift berhasil ditutup.',
                'data' => $shift,
            ]);
        }

        return redirect()->route('pos.index')
            ->with('status', 'Shift berhasil ditutup. Total saldo kas: Rp ' . number_format($actualCash, 0, ',', '.'));
    }

    /**
     * Get active shift details for current user.
     */
    public function current(): JsonResponse
    {
        $user = Auth::user();

        $shift = CashierShift::where('user_id', $user->id)
            ->where('status', 'open')
            ->latest()
            ->first();

        if (! $shift) {
            return response()->json([
                'success' => true,
                'has_active_shift' => false,
                'data' => null,
            ]);
        }

        $ordersCount = Order::where('cashier_shift_id', $shift->id)->count();
        $totalSales = Order::where('cashier_shift_id', $shift->id)
            ->where('payment_status', 'paid')
            ->sum('total_amount');

        return response()->json([
            'success' => true,
            'has_active_shift' => true,
            'data' => [
                'id' => $shift->id,
                'starting_cash' => (float) $shift->starting_cash,
                'opened_at' => $shift->opened_at->format('d M Y H:i'),
                'orders_count' => $ordersCount,
                'total_sales' => (float) $totalSales,
            ],
        ]);
    }
}
