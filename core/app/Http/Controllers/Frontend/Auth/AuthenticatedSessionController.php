<?php

namespace App\Http\Controllers\Frontend\Auth;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\TransactionPasswordService;

class AuthenticatedSessionController
{
    public function destroy(Request $request): RedirectResponse
    {
        app(TransactionPasswordService::class)->forgetRecentVerification();
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
