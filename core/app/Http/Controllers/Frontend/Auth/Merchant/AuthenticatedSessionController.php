<?php

namespace App\Http\Controllers\Frontend\Auth\Merchant;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\TransactionPasswordService;
use App\Services\WalletService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('frontend.auth.merchant.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $user = Auth::user();
        if ($user->role !== UserRole::MERCHANT || $user->status !== \App\Enums\UserStatus::ACTIVE) {
            Auth::logout();
            throw ValidationException::withMessages([
                'login' => __('Credenciais inválidas.'),
            ]);
        }

        $request->session()->regenerate();
        app(TransactionPasswordService::class)->forgetRecentVerification();

        app(WalletService::class)->createDefaultWalletForUser($user);

        return redirect()->intended(route('user.dashboard'));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        app(TransactionPasswordService::class)->forgetRecentVerification();
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
