<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\Gateways\Adapters\Efi\EfiOAuthService;
use App\Services\Gateways\Adapters\Efi\EfiHttpClient;
use App\Services\Gateways\TokenManager;
use App\Domain\Payments\GatewayRuntimeConfig;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class EfiOAuthServiceTest extends TestCase
{
    public function test_it_obtains_and_caches_access_token()
    {
        Http::fake([
            '*/oauth/token' => Http::response([
                'access_token' => 'mocked_efi_token',
                'token_type' => 'Bearer',
                'expires_in' => 3600
            ], 200)
        ]);

        $config = new GatewayRuntimeConfig(
            clientId: 'test_client_id',
            clientSecret: 'test_client_secret',
            certificatePath: storage_path('app/private/test.pem'),
            baseUrl: 'https://api-pix.gerencianet.com.br'
        );

        $mockHttpClient = \Mockery::mock(EfiHttpClient::class);
        $mockHttpClient->shouldReceive('makeClient')
            ->once()
            ->with($config, false)
            ->andReturn(Http::baseUrl('https://api-pix.gerencianet.com.br'));

        $service = new EfiOAuthService($mockHttpClient, app(TokenManager::class));
        
        $cacheKey = 'efi_token_' . md5($config->clientId);
        Cache::forget($cacheKey);

        $token = $service->getAccessToken($config);

        $this->assertEquals('mocked_efi_token', $token);
        $this->assertEquals('mocked_efi_token', Cache::get($cacheKey));
    }
}
