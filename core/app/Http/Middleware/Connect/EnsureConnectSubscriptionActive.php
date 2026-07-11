<?php
namespace App\Http\Middleware\Connect;

use Closure;
use App\Services\Connect\ConnectAccessContext;
use Illuminate\Support\Facades\Auth;

class EnsureConnectSubscriptionActive
{
    public function handle($request, Closure $next)
    {
        $merchantId = Auth::id();
        $context = ConnectAccessContext::getInstance($merchantId);
        
        if (!$context->isActive()) {
            if ($request->expectsJson() || $request->wantsJson()) {
                return response()->json(['error' => 'Payment Required.'], 402);
            }
            return redirect()->route('merchant.connect.upsell');
        }

        return $next($request);
    }
}
