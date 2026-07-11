<?php

namespace App\Gateway\Security\Drivers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use App\Exceptions\Gateway\GatewayConfigurationException;
use App\Exceptions\Gateway\GatewayAuthenticationException;

class EfiOAuthDriver implements AuthenticationDriverInterface
{
    public function authenticate(array $config): array
    {
        $this->validateCertificate($config);

        $env = isset($config['sandbox']) && $config['sandbox'] ? 'sandbox' : 'production';
        $clientIdHash = md5($config['client_id'] ?? '');
        $cacheKey = "efi:{$env}:{$clientIdHash}:oauth";
        
        // Anti-Stampede Lock
        $lock = Cache::lock($cacheKey . ':lock', 10);
        
        if ($token = Cache::get($cacheKey)) {
            return $this->buildAuthArray($token, $config);
        }

        try {
            $lock->block(5); // Wait up to 5 seconds for the lock
            
            // Check again after acquiring lock
            if ($token = Cache::get($cacheKey)) {
                return $this->buildAuthArray($token, $config);
            }

            $tokenData = $this->fetchToken($config);
            $token = $tokenData['access_token'];
            $ttl = max(1, $tokenData['expires_in'] - 300);

            Cache::put($cacheKey, $token, $ttl);
            
            return $this->buildAuthArray($token, $config);
            
        } catch (\Illuminate\Contracts\Cache\LockTimeoutException $e) {
            // Fallback seguro: se não conseguiu lock, tenta ler uma vez e dps morre
            if ($token = Cache::get($cacheKey)) {
                return $this->buildAuthArray($token, $config);
            }
            throw new GatewayAuthenticationException("Timeout acquire Efi OAuth token lock.");
        } finally {
            $lock?->release();
        }
    }

    protected function buildAuthArray(string $token, array $config): array
    {
        return [
            'headers' => [
                'Authorization' => 'Bearer ' . $token
            ],
            'options' => [
                'cert' => [$config['certificate_path'], $config['certificate_password'] ?? '']
            ]
        ];
    }

    protected function validateCertificate(array $config): void
    {
        $path = $config['certificate_path'] ?? null;
        if (!$path || !file_exists($path)) {
            throw new GatewayConfigurationException("Efi mTLS certificate missing or not found at path: {$path}");
        }

        if (!is_readable($path)) {
            throw new GatewayConfigurationException("Efi mTLS certificate is not readable.");
        }

        if (filesize($path) === 0) {
            throw new GatewayConfigurationException("Efi mTLS certificate is empty.");
        }

        $ext = pathinfo($path, PATHINFO_EXTENSION);
        if ($ext !== 'p12' && $ext !== 'pem') {
            throw new GatewayConfigurationException("Efi mTLS certificate has unsupported extension.");
        }
    }

    protected function fetchToken(array $config): array
    {
        $oauthUrl = $config['oauth_endpoint'] ?? 'https://pix.api.efipay.com.br/oauth/token'; // Fallback
        $authHeader = base64_encode($config['client_id'] . ':' . $config['client_secret']);

        $response = Http::withHeaders([
            'Authorization' => 'Basic ' . $authHeader,
            'Content-Type' => 'application/json'
        ])->withOptions([
            'cert' => [$config['certificate_path'], $config['certificate_password'] ?? '']
        ])->post($oauthUrl, [
            'grant_type' => 'client_credentials'
        ]);

        if (!$response->successful()) {
            throw new GatewayAuthenticationException("Efi OAuth falhou: " . $response->body());
        }

        return $response->json();
    }
}
