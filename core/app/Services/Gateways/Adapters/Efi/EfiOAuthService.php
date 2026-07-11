<?php

namespace App\Services\Gateways\Adapters\Efi;

use App\Domain\Payments\GatewayRuntimeConfig;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Exception;

class EfiOAuthService
{
    public function __construct(
        private readonly EfiHttpClient $httpClient,
        private readonly \App\Services\Gateways\TokenManager $tokenManager
    ) {}

    public function getAccessToken(GatewayRuntimeConfig $config): string
    {
        if (!$config->clientId || !$config->clientSecret) {
            throw new Exception("EFI credentials missing.");
        }

        $cacheKey = 'efi_token_' . md5($config->clientId);

        return $this->tokenManager->getToken($cacheKey, 3000, function () use ($config) {
            // EFI OAuth requires Basic Auth for the token request.
            // But we use the EfiHttpClient to handle the certificate mounting.
            // We tell EfiHttpClient not to require Bearer auth for this specific call.
            $client = $this->httpClient->makeClient($config, requiresAuth: false);

            $response = $client
                ->withBasicAuth($config->clientId, $config->clientSecret)
                ->post('/oauth/token', [
                    'grant_type' => 'client_credentials'
                ]);

            if ($response->failed()) {
                throw new Exception("Failed to obtain EFI access token: " . $response->body());
            }

            $data = $response->json();
            return $data['access_token'] ?? throw new Exception("Access token missing in EFI response.");
        });
    }

    public function invalidateToken(GatewayRuntimeConfig $config): void
    {
        $cacheKey = 'efi_token_' . md5($config->clientId);
        $this->tokenManager->invalidate($cacheKey);
    }
}
