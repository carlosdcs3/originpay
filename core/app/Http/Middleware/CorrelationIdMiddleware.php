<?php

namespace App\Http\Middleware;

use App\Domain\Core\EventContext;
use App\Support\Observability\StructuredLogContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Str;

class CorrelationIdMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $startedAt = microtime(true);
        $correlationId = $this->resolveCorrelationId($request->header('X-Correlation-ID'));

        EventContext::setCorrelationId($correlationId);
        Context::add('correlation_id', $correlationId);
        Context::add('request_id', $request->header('X-Request-ID') ?: $correlationId);

        $this->addActorContext();
        $this->addApiKeyContext($request);

        Context::add('request_method', $request->method());
        Context::add('request_path', '/'.ltrim($request->path(), '/'));
        Context::add('ip', $request->ip());

        $response = $next($request);

        $durationMs = round((microtime(true) - $startedAt) * 1000, 2);
        $statusCode = method_exists($response, 'getStatusCode') ? $response->getStatusCode() : null;

        foreach (app(StructuredLogContext::class)->fromRequest($request, $statusCode, $durationMs) as $key => $value) {
            Context::add($key, $value);
        }

        $response->headers->set('X-Correlation-ID', $correlationId);

        return $response;
    }

    private function addActorContext(): void
    {
        if (! auth()->check()) {
            return;
        }

        $user = auth()->user();

        EventContext::setActor(get_class($user), (string) auth()->id());
        Context::add('user_id', auth()->id());

        if (isset($user->tenant_id)) {
            Context::add('tenant_id', $user->tenant_id);
        }

        if (isset($user->merchant_id)) {
            Context::add('merchant_id', $user->merchant_id);
        }
    }

    private function addApiKeyContext(Request $request): void
    {
        $apiKey = $request->attributes->get('api_key') ?? $request->attributes->get('apiKey');

        if (is_object($apiKey) && isset($apiKey->id)) {
            Context::add('api_key_id', $apiKey->id);
        }
    }

    private function resolveCorrelationId(?string $correlationId): string
    {
        if (is_string($correlationId) && strlen($correlationId) <= 64 && Str::isUuid($correlationId)) {
            return $correlationId;
        }

        return (string) Str::uuid();
    }
}
