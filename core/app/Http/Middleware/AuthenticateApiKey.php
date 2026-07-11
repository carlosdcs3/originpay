<?php

namespace App\Http\Middleware;

use App\Models\Merchant;
use Closure;
use Illuminate\Http\Request;
use App\Models\ApiKey;
use App\Factories\ApiResponse;
use App\Services\Auth\ApiAuthenticationService;
use Illuminate\Support\Str;

class AuthenticateApiKey
{
    public function __construct(
        private readonly ApiAuthenticationService $apiAuthenticationService
    ) {
    }

    public function handle(Request $request, Closure $next)
    {
        // Accept from Bearer token or x-api-key header
        $key = $request->bearerToken();
        if (!$key) {
            $key = $request->header('x-api-key');
        }

        if (!$key) {
            return ApiResponse::missingApiKey();
        }

        $hash = hash('sha256', $key);
        
        $apiKey = ApiKey::where('key_hash', $hash)
            ->where('status', true)
            ->first();

        if (!$apiKey) {
            $authorization = $request->header('Authorization');
            $requestId = $request->attributes->get('request_id') ?? ('req_' . Str::random(14));

            $merchantContext = $authorization
                ? $this->apiAuthenticationService->authenticate($authorization, $requestId)
                : null;

            if (! $merchantContext) {
                return ApiResponse::unauthorized();
            }

            $merchant = Merchant::find($merchantContext->merchantId);

            if (! $merchant) {
                return ApiResponse::unauthorized();
            }

            $request->merge([
                'api_user_id' => $merchant->user_id,
                'api_environment' => $merchantContext->environment,
                'api_key_id' => $merchantContext->credentialId,
                'api_merchant_id' => $merchant->id,
            ]);

            return $next($request);
        }

        if ($apiKey->expires_at && $apiKey->expires_at->isPast()) {
            return ApiResponse::unauthorized();
        }

        $apiKey->update(['last_used_at' => now()]);

        $merchantId = Merchant::query()
            ->where('user_id', $apiKey->user_id)
            ->value('id');

        // Merge user and environment into request so controllers can use it
        $request->merge([
            'api_user_id' => $apiKey->user_id,
            'api_environment' => $apiKey->environment,
            'api_key_id' => $apiKey->id,
            'api_merchant_id' => $merchantId,
        ]);

        return $next($request);
    }
}
