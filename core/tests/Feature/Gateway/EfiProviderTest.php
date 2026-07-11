<?php

namespace Tests\Feature\Gateway;

use Tests\TestCase;
use App\Gateway\Providers\Efi\EfiProvider;
use App\Gateway\Providers\Efi\EfiOAuthDriver;
use App\Gateway\Contracts\Data\GatewayCredentials;
use App\Gateway\Contracts\Enums\GatewayOperation;
use App\Gateway\Http\GatewayHttpClient;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use App\Gateway\Exceptions\GatewayAuthenticationException;

class EfiProviderTest extends TestCase
{
    protected GatewayCredentials $credentials;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->credentials = new GatewayCredentials(
            clientId: 'test_client_id',
            clientSecret: 'test_client_secret',
            certificate: 'test_cert.pem', // Assumes mocked validation later
            pixKey: 'test_pix_key',
            sandbox: true
        );
    }

    public function test_oauth_cache_stampede_protection()
    {
        Cache::flush();
        
        // Mock do Http Client para responder o /oauth/token
        Http::fake([
            '*/oauth/token' => Http::response(['access_token' => 'mock_token', 'expires_in' => 3600], 200)
        ]);

        // Simular que o certificado existe
        $certPath = tempnam(sys_get_temp_dir(), 'pem');
        file_put_contents($certPath, 'dummy_cert_data');

        $driver = new EfiOAuthDriver(
            $this->credentials,
            new GatewayHttpClient('efi'),
            $certPath,
            'https://pix-h.api.efipay.com.br'
        );

        $token1 = $driver->getAccessToken();
        $token2 = $driver->getAccessToken();

        $this->assertEquals('mock_token', $token1);
        $this->assertEquals('mock_token', $token2);
        
        // Assegurar que só bateu 1 vez na API
        Http::assertSentCount(1);
        
        unlink($certPath);
    }

    public function test_efi_provider_idempotency_with_txid()
    {
        Http::fake([
            '*/oauth/token' => Http::response(['access_token' => 'mock_token', 'expires_in' => 3600], 200),
            '*/v2/cob/mock_txid_123' => Http::response(['txid' => 'mock_txid_123', 'status' => 'ATIVA'], 200),
        ]);

        // Evitar Exception de certificado não encontrado mockando o provider
        $provider = $this->getMockBuilder(EfiProvider::class)
            ->setConstructorArgs([$this->credentials])
            ->onlyMethods(['getCertPath'])
            ->getMock();

        $provider->method('getCertPath')->willReturn(__FILE__); // Fake readable file
        
        $response = $provider->sendRequest(GatewayOperation::CHARGE_PIX, [
            'amount' => 50.00,
            'txid' => 'mock_txid_123',
            'description' => 'Test Idempotency'
        ]);

        $this->assertTrue($response->success);
        $this->assertEquals('mock_txid_123', $response->txid);
        
        // Assegurar que chamou PUT e não POST
        Http::assertSent(function (\Illuminate\Http\Client\Request $request) {
            return $request->method() === 'PUT' && Str::endsWith($request->url(), '/v2/cob/mock_txid_123');
        });
    }

    public function test_log_sanitization_removes_sensitive_data()
    {
        $client = new GatewayHttpClient('efi');
        
        Http::fake([
            '*' => Http::response(['client_secret' => 'SECRET_MUST_BE_HIDDEN', 'status' => 'OK'], 200)
        ]);

        $client->withHeaders(['Authorization' => 'Bearer HIDE_ME']);
        $client->post('https://mock.api');

        // Log já disparou internamente. Em testes reais, usaríamos Log::spy()
        // Por ora garantimos que a função nativa limpa o payload:
        $reflection = new \ReflectionClass($client);
        $method = $reflection->getMethod('sanitizeLogData');
        $method->setAccessible(true);
        
        $sanitized = $method->invoke($client, [
            'client_secret' => 'SECRET_MUST_BE_HIDDEN',
            'normal_field' => 'OK',
            'nested' => [
                'authorization' => 'Bearer HIDE_ME'
            ]
        ]);

        $this->assertEquals('********', $sanitized['client_secret']);
        $this->assertEquals('OK', $sanitized['normal_field']);
        $this->assertEquals('********', $sanitized['nested']['authorization']);
    }
}
