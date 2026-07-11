<?php

namespace App\Gateway\Providers\Efi;

use App\Gateway\Contracts\Data\GatewayCredentials;
use App\Gateway\Exceptions\GatewayAuthenticationException;
use App\Gateway\Http\GatewayHttpClient;
use Illuminate\Support\Facades\Cache;

class EfiOAuthDriver
{
    protected GatewayCredentials $credentials;
    protected GatewayHttpClient $httpClient;
    protected string $certPath;
    protected string $baseUrl;

    public function __construct(
        GatewayCredentials $credentials,
        GatewayHttpClient $httpClient,
        string $certPath,
        string $baseUrl
    ) {
        $this->credentials = $credentials;
        $this->httpClient = $httpClient;
        $this->certPath = $certPath;
        $this->baseUrl = $baseUrl;
    }

    public function getAccessToken(): string
    {
        $clientId = $this->credentials->clientId;
        $clientSecret = $this->credentials->clientSecret;
        $env = $this->credentials->sandbox ? 'sandbox' : 'production';

        if (empty($clientId) || empty($clientSecret)) {
            throw new GatewayAuthenticationException("Credenciais (Client ID / Secret) Efí ausentes.");
        }

        $hash = md5($clientId);
        $cacheKey = "efi:{$env}:{$hash}:oauth";
        $lockKey = "{$cacheKey}:lock";

        // Fast path
        $cachedToken = Cache::get($cacheKey);
        if ($cachedToken) {
            return $cachedToken;
        }

        // Cache Stampede Protection
        $lock = Cache::lock($lockKey, 10);
        
        try {
            if ($lock->block(5)) {
                // Double check após adquirir o lock
                $cachedToken = Cache::get($cacheKey);
                if ($cachedToken) {
                    return $cachedToken;
                }

                return $this->fetchNewToken($clientId, $clientSecret, $cacheKey);
            }
            throw new GatewayAuthenticationException("Timeout ao aguardar renovação do token Efí.");
        } finally {
            optional($lock)->release();
        }
    }

    protected function fetchNewToken(string $clientId, string $clientSecret, string $cacheKey): string
    {
        $client = clone $this->httpClient;
        
        $response = $client->withHeaders([
            'Authorization' => 'Basic ' . base64_encode("{$clientId}:{$clientSecret}")
        ])->withOptions([
            'cert' => $this->certPath
        ])->post($this->baseUrl . '/oauth/token', [
            'grant_type' => 'client_credentials'
        ]);

        if (!$response->successful()) {
            $data = $response->json() ?? [];
            $errorMsg = $data['error_description'] ?? $data['mensagem'] ?? 'Erro desconhecido';
            throw new GatewayAuthenticationException("Falha na autenticação OAuth Efí: {$errorMsg}");
        }

        $token = $response->json('access_token');
        $expiresIn = (int) $response->json('expires_in', 3600);
        $safeTtl = max(60, $expiresIn - 300);

        Cache::put($cacheKey, $token, $safeTtl);
        
        // Registrar hora da autenticação para o Health Check
        Cache::put("{$cacheKey}:last_success", now()->toIso8601String(), 86400 * 7);
        Cache::put("{$cacheKey}:token_expires_at", now()->addSeconds($safeTtl)->toIso8601String(), $safeTtl);

        return $token;
    }
}
