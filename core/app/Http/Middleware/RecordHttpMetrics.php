<?php

namespace App\Http\Middleware;

use App\Support\Observability\Metrics\LocalMetricsCollector;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RecordHttpMetrics
{
    public function __construct(private readonly LocalMetricsCollector $metrics) {}

    public function handle(Request $request, Closure $next): Response
    {
        $startedAt = hrtime(true);

        try {
            return $response = $next($request);
        } finally {
            $status = isset($response) ? $response->getStatusCode() : 500;
            $routeName = $request->route()?->getName() ?? 'unnamed';
            $this->metrics->recordRequest($routeName, $request->method(), $status, (hrtime(true) - $startedAt) / 1_000_000);
        }
    }
}
