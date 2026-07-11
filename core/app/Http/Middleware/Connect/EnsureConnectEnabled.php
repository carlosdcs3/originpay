<?php
namespace App\Http\Middleware\Connect;

use Closure;
use Illuminate\Support\Facades\Auth;
use App\Services\Connect\ConnectAccessContext;

class EnsureConnectEnabled
{
    public function handle($request, Closure $next)
    {
        $context = ConnectAccessContext::getInstance(Auth::id());
        if (!$context->isEnabled()) {
            abort(403, 'Origin Connect module is currently disabled.');
        }
        return $next($request);
    }
}
