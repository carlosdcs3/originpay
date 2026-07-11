<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\DisasterRecovery\EmergencyCircuitBreaker;

class EmergencyReadOnlyMiddleware
{
    protected EmergencyCircuitBreaker $breaker;

    public function __construct(EmergencyCircuitBreaker $breaker)
    {
        $this->breaker = $breaker;
    }

    public function handle(Request $request, Closure $next)
    {
        // Se Read Only Mode estiver ativo, bloqueia todos os métodos que não sejam GET/HEAD
        // Exceto rotas vitais ou de leitura.
        if ($this->breaker->isSwitchActive('kill_switch:read_only_mode')) {
            if (!$request->isMethod('GET') && !$request->isMethod('HEAD')) {
                // Allows auth routes (login, logout) even in read only
                if (!$request->is('login') && !$request->is('logout') && !$request->is('admin/login')) {
                    if ($request->wantsJson() || $request->is('api/*')) {
                        return response()->json(['error' => 'Platform is currently in Emergency Read-Only Mode. Transactions are blocked.'], 503);
                    }
                    return redirect()->back()->with('error', 'Platform is currently in Emergency Read-Only Mode. Operations are paused.');
                }
            }
        }

        return $next($request);
    }
}
