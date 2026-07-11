<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\WebhookDlq;
use App\Payment\Modern\Providers\NewProviderGateway;
use App\Payment\Modern\ModernPaymentGatewayFactory;
use App\Payment\Modern\DTO\DepositDTO;
use App\Payment\Modern\DTO\WithdrawDTO;
use App\Enums\ProviderType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Exception;

class NewProviderHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('services.new_provider.webhook_secret', 'my_sandbox_secret');
        Config::set('services.new_provider.api_key', 'my_api_key');
        Cache::forget('new_provider_circuit_breaker');

        $factory = new ModernPaymentGatewayFactory();
        $factory->registerGateway(ProviderType::MANUAL, NewProviderGateway::class);
        $this->app->instance(ModernPaymentGatewayFactory::class, $factory);
    }

    private function postSignedWebhook(string $payload, string $signature, int $timestamp, array $headers = [])
    {
        $server = [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_NEWPROVIDER_SIGNATURE' => $signature,
            'HTTP_X_NEWPROVIDER_TIMESTAMP' => (string) $timestamp,
        ];

        foreach ($headers as $name => $value) {
            $server['HTTP_' . strtoupper(str_replace('-', '_', $name))] = $value;
        }

        return $this->call('POST', '/api/webhook/modern/manual', [], [], [], $server, $payload);
    }

    public function test_circuit_breaker_blocks_outbound_but_allows_inbound()
    {
        Cache::put('new_provider_circuit_breaker', 'OFFLINE', now()->addMinutes(2));

        $gateway = new NewProviderGateway();
        
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Circuit Breaker: Provider NEW_PROVIDER is currently OFFLINE.');

        // Outbound blocked
        $gateway->createDeposit(new DepositDTO(100, 'USD', '123'));
    }

    public function test_circuit_breaker_allows_inbound_webhook()
    {
        Cache::put('new_provider_circuit_breaker', 'OFFLINE', now()->addMinutes(2));

        $gateway = new NewProviderGateway();
        
        $payload = json_encode(['id' => '123']);
        $timestamp = time();
        $signedPayload = $timestamp . '.' . $payload;
        $signature = hash_hmac('sha256', $signedPayload, 'my_sandbox_secret');

        $request = Request::create('/webhook', 'POST', [], [], [], [
            'HTTP_X-NewProvider-Signature' => $signature,
            'HTTP_X-NewProvider-Timestamp' => $timestamp
        ], $payload);

        // Inbound allowed
        $this->assertTrue($gateway->verifyWebhook($request));
    }

    public function test_dlq_saves_internal_errors_with_valid_signature_and_masks_payload()
    {
        // Mock the TransactionService to throw an exception
        $this->mock(\App\Services\TransactionService::class, function ($mock) {
            $mock->shouldReceive('processModernWebhook')->andThrow(new Exception('Deadlock Simulation'));
        });

        $payload = json_encode(['id' => 'tx_999', 'status' => 'PAID', 'amount' => 100, 'cpf' => '123.456.789-00']);
        $timestamp = time();
        $signedPayload = $timestamp . '.' . $payload;
        $signature = hash_hmac('sha256', $signedPayload, 'my_sandbox_secret');

        $response = $this->postSignedWebhook($payload, $signature, $timestamp, [
            'api_key' => 'my_secret_key',
        ]);

        $response->assertStatus(500);

        $dlq = WebhookDlq::first();
        $this->assertNotNull($dlq);
        $this->assertEquals('Deadlock Simulation', $dlq->error_message);
        
        // Assert Masking
        $this->assertStringContainsString('***MASKED***', $dlq->payload);
        $this->assertStringNotContainsString('123.456.789-00', $dlq->payload);
        $this->assertStringContainsString('***MASKED***', $dlq->headers);
        $this->assertStringNotContainsString('my_secret_key', $dlq->headers);
    }

    public function test_invalid_signature_does_not_save_in_dlq()
    {
        $payload = json_encode(['id' => 'tx_999']);
        $timestamp = time();
        $signedPayload = $timestamp . '.' . $payload;
        $signature = hash_hmac('sha256', $signedPayload, 'WRONG');

        $response = $this->postSignedWebhook($payload, $signature, $timestamp);

        $response->assertStatus(401);
        $this->assertEquals(0, WebhookDlq::count());
    }

    public function test_outbound_requests_have_idempotency_headers_and_logs()
    {
        Http::fake([
            'api.sandbox.newprovider.com/v1/deposits' => Http::response(['id' => 'DEP_123', 'redirect_url' => 'http'], 200)
        ]);

        $gateway = new NewProviderGateway();
        $dto = new DepositDTO(100, 'USD', 'MY_INT_ID_1');
        
        $gateway->createDeposit($dto);

        Http::assertSent(function (\Illuminate\Http\Client\Request $request) {
            return $request->hasHeader('Idempotency-Key');
        });
    }

    public function test_retry_on_429()
    {
        // Fake 2 failures of 429, then 1 success
        Http::fakeSequence()
            ->push('Too Many Requests', 429)
            ->push('Too Many Requests', 429)
            ->push(['id' => 'DEP_123', 'redirect_url' => 'http'], 200);

        $gateway = new NewProviderGateway();
        $dto = new DepositDTO(100, 'USD', 'MY_INT_ID_2');
        
        $response = $gateway->createDeposit($dto);
        $this->assertTrue($response->isSuccess);
    }
}
