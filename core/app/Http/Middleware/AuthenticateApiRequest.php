<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\Auth\ApiAuthenticationService;
use App\Domain\Auth\ApiRequestContext;
use Illuminate\Support\Str;
use App\Factories\ApiResponse;

class AuthenticateApiRequest
{
    public function __construct(
        private readonly ApiAuthenticationService $authService
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $requestId = 'req_' . Str::random(14);
        $request->attributes->set('request_id', $requestId);

        $authorization = $request->header('Authorization');

        if (empty($authorization)) {
            return ApiResponse::missingApiKey();
        }

        $merchantContext = $this->authService->authenticate($authorization, $requestId);

        if (!$merchantContext) {
            return ApiResponse::unauthorized();
        }

        $apiRequestContext = new ApiRequestContext(
            requestId: $requestId,
            merchant: $merchantContext,
            origin: $request->header('Origin'),
            userAgent: $request->userAgent(),
            ipAddress: $request->ip(),
            idempotencyKey: $request->header('Idempotency-Key'),
            apiVersion: 'v1',
            timestamp: now()->toIso8601String()
        );

        $request->attributes->set('api_request_context', $apiRequestContext);
        $request->attributes->set('merchant_context', $merchantContext);

        $response = $next($request);
        
        $response->headers->set('X-OriginPay-Request-Id', $requestId);

        $this->logRequest($request, $response, $apiRequestContext, $merchantContext);

        return $response;
    }

    private function logRequest(Request $request, Response $response, ApiRequestContext $apiContext, ?\App\Domain\Auth\MerchantContext $merchantContext): void
    {
        $requestStart = defined('LARAVEL_START') ? LARAVEL_START : microtime(true);
        $durationMs = round((microtime(true) - $requestStart) * 1000, 2);
        
        $country = $request->header('CF-IPCountry') ?: $request->header('CloudFront-Viewer-Country');

        \App\Models\ApiRequestLog::create([
            'request_id' => $apiContext->requestId,
            'merchant_id' => $merchantContext?->merchantId,
            'api_key_id' => $merchantContext?->credentialId,
            'api_version' => $merchantContext?->apiVersion ?? 'v1',
            'endpoint' => $request->path(),
            'method' => $request->method(),
            'status_code' => $response->getStatusCode(),
            'ip_address' => $apiContext->ipAddress,
            'user_agent' => $apiContext->userAgent,
            'country' => $country,
            'duration_ms' => $durationMs,
            'request_size' => strlen($request->getContent()),
            'response_size' => strlen($response->getContent()),
            'error_type' => null, // Extracted from body if needed
            'error_code' => null, // Extracted from body if needed
            'created_at' => now(),
        ]);
    }
}
