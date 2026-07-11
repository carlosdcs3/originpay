<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

class PerformanceLoggingMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Add start time to request attributes
        $request->attributes->set('start_time', microtime(true));

        return $next($request);
    }

    /**
     * Handle tasks after the response has been sent to the browser.
     */
    public function terminate(Request $request, Response $response): void
    {
        $startTime = $request->attributes->get('start_time');
        if ($startTime) {
            $duration = round((microtime(true) - $startTime) * 1000); // ms

            $route = $request->route() ? $request->route()->getName() : $request->path();
            
            // Determine SLA limit based on category
            $limit = 500; // default 500ms
            
            if (str_contains((string) $route, 'login') || str_contains((string) $route, 'logout')) {
                $limit = 300;
            } elseif (str_contains((string) $route, 'payment.') || str_contains((string) $route, 'checkout')) {
                $limit = 500;
            } elseif (str_contains((string) $route, 'webhook')) {
                $limit = 1000;
            } elseif (str_contains((string) $route, 'report') || str_contains((string) $route, 'dashboard')) {
                $limit = 3000;
            }

            if ($duration > $limit) {
                Log::channel('performance')->warning('Slow operation detected', [
                    'op_type'  => 'http_request',
                    'duration' => $duration . 'ms',
                    'route'    => $route,
                    'limit'    => $limit . 'ms',
                ]);
            }
        }
    }
}
