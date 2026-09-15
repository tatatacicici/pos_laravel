<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    /**
     * Show cashier / user login screen.
     */
    public function showLoginForm(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('pos.index');
        }

        // Pass demo users for one-click convenience in development/evaluation
        $demoUsers = User::where('is_active', true)->select('id', 'name', 'email', 'role')->take(5)->get();

        return view('auth.login', compact('demoUsers'));
    }

    /**
     * Process authentication.
     */
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if ($user && ! $user->is_active) {
            throw ValidationException::withMessages([
                'email' => ['Akun Anda berstatus non-aktif. Silakan hubungi admin.'],
            ]);
        }

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => ['Email atau kata sandi yang Anda masukkan salah.'],
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('pos.index'))
            ->with('status', "Selamat bertugas, {$user->name}!");
    }

    /**
     * Logout cashier / switch account.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->with('status', 'Berhasil keluar. Silakan login untuk memulai shift kasir baru.');
    }
}
