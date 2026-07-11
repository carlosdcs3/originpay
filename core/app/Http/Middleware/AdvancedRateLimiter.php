<?php

namespace App\Http\Middleware;

use App\Enums\ApiErrorCode;
use App\Enums\ApiErrorType;
use App\Factories\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use App\Models\ApiQuota;
use Illuminate\Support\Facades\Cache;

class AdvancedRateLimiter
{
    public function handle(Request $request, Closure $next)
    {
        $userId = $request->attributes->get('api_user_id') ?? $request->input('api_user_id');
        $apiKeyId = $request->attributes->get('api_key_id') ?? $request->input('api_key_id');
        $requestId = $request->attributes->get('request_id');

        if (!$userId || !$apiKeyId) {
            // Se chegou aqui sem userId/keyId, o AuthenticateApiKey não bloqueou (talvez sandbox/local sem auth)
            // Em produção real, o auth roda antes. Assumimos conservador.
            return $next($request);
        }

        // Recupera ou cria cota com cache
        $quota = Cache::remember("api_quota_{$userId}", 3600, function () use ($userId) {
            return ApiQuota::firstOrCreate(
                ['user_id' => $userId],
                ['rate_limit_general' => 300, 'rate_limit_financial' => 30, 'quota_daily' => 1000, 'quota_monthly' => 30000]
            );
        });

        $ip = $request->ip();
        $isFinancial = in_array($request->path(), ['api/v1/payments', 'api/v1/payouts', 'api/v1/refunds']) && $request->isMethod('POST');
        
        $rateLimitPerMinute = $isFinancial ? $quota->rate_limit_financial : $quota->rate_limit_general;

        // 1. Limite por IP (Segurança Geral - Anti-DDoS L7) - Padrão 300 req/min global por IP
        $ipKey = "rate_limit:ip:{$ip}";
        if (RateLimiter::tooManyAttempts($ipKey, 300)) {
            return $this->rateLimitResponse($requestId, 'IP');
        }
        RateLimiter::hit($ipKey, 60);

        // 2. Limite por API Key (Rate Limit)
        $apiKeyRateLimitKey = "rate_limit:key:{$apiKeyId}:" . ($isFinancial ? 'financial' : 'general');
        if (RateLimiter::tooManyAttempts($apiKeyRateLimitKey, $rateLimitPerMinute)) {
            return $this->rateLimitResponse($requestId, 'API Key');
        }
        RateLimiter::hit($apiKeyRateLimitKey, 60);

        // 3. Quota Diária
        if ($quota->quota_daily > 0) {
            $dailyKey = "quota:daily:{$userId}:" . now()->format('Y-m-d');
            $dailyCount = (int) Cache::get($dailyKey, 0);
            if ($dailyCount >= $quota->quota_daily) {
                return $this->quotaExceededResponse($requestId, 'Diária');
            }
            Cache::increment($dailyKey);
            // set TTL se não existir
            if ($dailyCount === 0) Cache::put($dailyKey, 1, now()->endOfDay());
        }

        // 4. Quota Mensal
        if ($quota->quota_monthly > 0) {
            $monthlyKey = "quota:monthly:{$userId}:" . now()->format('Y-m');
            $monthlyCount = (int) Cache::get($monthlyKey, 0);
            if ($monthlyCount >= $quota->quota_monthly) {
                return $this->quotaExceededResponse($requestId, 'Mensal');
            }
            Cache::increment($monthlyKey);
            // set TTL se não existir
            if ($monthlyCount === 0) Cache::put($monthlyKey, 1, now()->endOfMonth());
        }

        $response = $next($request);

        // Injetar headers de rate limit na resposta
        $response->headers->set('X-RateLimit-Limit', $rateLimitPerMinute);
        $response->headers->set('X-RateLimit-Remaining', RateLimiter::remaining($apiKeyRateLimitKey, $rateLimitPerMinute));

        return $response;
    }

    private function rateLimitResponse($requestId, $type)
    {
        return ApiResponse::error(
            ApiErrorType::RATE_LIMIT_ERROR,
            ApiErrorCode::INVALID_PARAMETERS,
            'Too many requests.',
            429
        );
    }

    private function quotaExceededResponse($requestId, $type)
    {
        return ApiResponse::error(
            ApiErrorType::RATE_LIMIT_ERROR,
            ApiErrorCode::INVALID_PARAMETERS,
            'Too many requests.',
            429
        );
    }
}
