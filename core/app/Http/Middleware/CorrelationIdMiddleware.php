<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Domain\Core\EventContext;
use Illuminate\Support\Str;

class CorrelationIdMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // 1. Get from header if microservice upstream sent it, else generate new
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());
        
        // 2. Set globally for this lifecycle
        EventContext::setCorrelationId($correlationId);

        // 3. Optional: Set Actor info if authenticated
        $userId = null;
        $merchantId = null;
        if (auth()->check()) {
            EventContext::setActor(get_class(auth()->user()), (string) auth()->id());
            $userId = auth()->id();
            if (auth()->user()->merchant_id) {
                $merchantId = auth()->user()->merchant_id;
            }
        }

        // Global Logging Context (Serialized to Queues)
        \Illuminate\Support\Facades\Context::add('correlation_id', $correlationId);
        \Illuminate\Support\Facades\Context::add('request_id', $request->header('X-Request-ID', (string) Str::uuid()));
        \Illuminate\Support\Facades\Context::add('route', $request->route() ? $request->route()->getName() : 'unknown');
        \Illuminate\Support\Facades\Context::add('method', $request->method());
        \Illuminate\Support\Facades\Context::add('ip', $request->ip());
        \Illuminate\Support\Facades\Context::add('authenticated_user_id', $userId);
        \Illuminate\Support\Facades\Context::add('merchant_id', $merchantId);

        $response = $next($request);

        // 4. Return correlation ID to client for tracing support
        $response->headers->set('X-Correlation-ID', $correlationId);

        return $response;
    }
}
