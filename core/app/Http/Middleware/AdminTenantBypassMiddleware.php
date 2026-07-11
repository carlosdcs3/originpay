<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\Security\TenantBypass;

class AdminTenantBypassMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        return TenantBypass::run(function () use ($request, $next) {
            return $next($request);
        });
    }
}
