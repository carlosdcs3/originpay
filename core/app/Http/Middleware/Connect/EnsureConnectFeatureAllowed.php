<?php
namespace App\Http\Middleware\Connect;

use Closure;
use App\Services\Connect\ConnectAccessContext;
use Illuminate\Support\Facades\Auth;

class EnsureConnectFeatureAllowed
{
    public function handle($request, Closure $next, $feature)
    {
        $context = ConnectAccessContext::getInstance(Auth::id());
        
        if (!$context->hasFeature($feature)) {
            abort(403, "Feature {$feature} is disabled.");
        }
        return $next($request);
    }
}
