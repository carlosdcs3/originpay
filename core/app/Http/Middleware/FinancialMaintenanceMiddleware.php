<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\DisasterRecovery\EmergencyCircuitBreaker;

class FinancialMaintenanceMiddleware
{
    protected EmergencyCircuitBreaker $breaker;

    public function __construct(EmergencyCircuitBreaker $breaker)
    {
        $this->breaker = $breaker;
    }

    public function handle(Request $request, Closure $next)
    {
        // Se a Manutenção Financeira estiver ativa, bloqueia POSTs financeiros
        // Webhooks NÃO entram aqui se aplicarmos isso nas rotas web/api (excluindo /webhook)
        if ($this->breaker->isSwitchActive('kill_switch:financial_maintenance')) {
            $blockedRoutes = ['deposit', 'withdraw', 'refund', 'transfer'];
            foreach ($blockedRoutes as $route) {
                if ($request->is("*{$route}*") && !$request->isMethod('GET')) {
                    if ($request->wantsJson()) {
                        return response()->json(['error' => 'Financial Maintenance Mode is ON. New transactions are temporarily disabled.'], 503);
                    }
                    return redirect()->back()->with('error', 'Financial Maintenance Mode is ON. New transactions are temporarily disabled.');
                }
            }
        }

        return $next($request);
    }
}
