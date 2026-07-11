<?php

namespace App\Http\Middleware;

use App\Enums\ApiErrorCode;
use App\Enums\ApiErrorType;
use App\Enums\EnvironmentMode;
use App\Factories\ApiResponse;
use App\Models\Merchant;
use App\Services\Auth\ApiAuthenticationService;
use Closure;
use Illuminate\Http\Request;

class MerchantApiAuth
{
    public function __construct(
        private readonly ApiAuthenticationService $apiAuthenticationService
    ) {
    }

    /**
     * Handle an incoming request.
     *
     * Preferred authentication:
     * - Authorization: Bearer sk_...
     *
     * Temporary legacy compatibility:
     * - X-Merchant-Key
     * - X-API-Key
     * - X-Environment (optional: 'production' or 'sandbox')
     *
     * Legacy credentials are deprecated because they are stored in the historical
     * merchants columns. Keep this fallback only until every merchant migrates to
     * hash-based ApiCredential secrets.
     */
    public function handle(Request $request, Closure $next)
    {
        $environment = $request->header('X-Environment', EnvironmentMode::PRODUCTION->value);
        $validEnvironments = array_column(EnvironmentMode::cases(), 'value');

        if (! in_array($environment, $validEnvironments, true)) {
            return ApiResponse::error(
                ApiErrorType::INVALID_REQUEST,
                ApiErrorCode::INVALID_PARAMETERS,
                'The request is invalid.',
                400
            );
        }

        $environmentEnum = EnvironmentMode::from($environment);
        $requestId = $request->attributes->get('request_id') ?? ('legacy_' . bin2hex(random_bytes(8)));

        if ($authorization = $request->header('Authorization')) {
            $merchantContext = $this->apiAuthenticationService->authenticate($authorization, $requestId);

            if ($merchantContext) {
                $merchant = Merchant::find($merchantContext->merchantId);

                if ($merchant && (! $environmentEnum->isSandbox() || $merchant->sandbox_enabled)) {
                    $request->merge([
                        'merchant' => $merchant,
                        'environment' => $merchantContext->environment,
                        'is_sandbox' => $merchantContext->environment === EnvironmentMode::SANDBOX->value,
                        'api_user_id' => $merchant->user_id,
                        'api_environment' => $merchantContext->environment,
                        'api_key_id' => $merchantContext->credentialId,
                        'api_merchant_id' => $merchant->id,
                        'api_auth_mode' => 'hash_based',
                    ]);

                    return $next($request);
                }
            }
        }

        $merchant = $this->findMerchantByLegacyCredentials(
            $request->header('X-API-Key'),
            $request->header('X-Merchant-Key'),
            $environmentEnum
        );

        if (! $merchant) {
            return ApiResponse::unauthorized();
        }

        if ($environmentEnum->isSandbox() && ! $merchant->sandbox_enabled) {
            return ApiResponse::unauthorized();
        }

        $request->merge([
            'merchant' => $merchant,
            'environment' => $environmentEnum->value,
            'is_sandbox' => $environmentEnum->isSandbox(),
            'api_user_id' => $merchant->user_id,
            'api_environment' => $environmentEnum->value,
            'api_key_id' => null,
            'api_merchant_id' => $merchant->id,
            'api_auth_mode' => 'legacy_deprecated',
        ]);

        $response = $next($request);
        $response->headers->set('X-OriginPay-Auth-Mode', 'legacy_deprecated');
        $response->headers->set('Deprecation', 'true');

        return $response;
    }

    private function findMerchantByLegacyCredentials(?string $apiKey, ?string $merchantKey, EnvironmentMode $environment): ?Merchant
    {
        if (empty($apiKey) || empty($merchantKey)) {
            return null;
        }

        if ($environment->isSandbox()) {
            return Merchant::where('test_api_key', $apiKey)
                ->where('test_merchant_key', $merchantKey)
                ->where('sandbox_enabled', true)
                ->first();
        }

        return Merchant::where('api_key', $apiKey)
            ->where('merchant_key', $merchantKey)
            ->first();
    }
}
