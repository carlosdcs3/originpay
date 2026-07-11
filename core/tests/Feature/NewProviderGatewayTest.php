<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Payment\Modern\Providers\NewProviderGateway;
use App\Payment\Modern\DTO\DepositDTO;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

class NewProviderGatewayTest extends TestCase
{
    public function test_create_deposit_returns_dto()
    {
        Http::fake([
            'api.sandbox.newprovider.com/v1/deposits' => Http::response([
                'id' => 'SANDBOX_DEP_123',
                'redirect_url' => 'https://sandbox.newprovider.com/checkout/SANDBOX_DEP_123',
            ], 200),
        ]);

        $gateway = new NewProviderGateway();
        $dto = new DepositDTO(100.0, 'USD', 'INT_123');
        $response = $gateway->createDeposit($dto);

        $this->assertTrue($response->isSuccess);
        $this->assertStringContainsString('SANDBOX_DEP_', $response->providerTransactionId);
        $this->assertStringContainsString('sandbox.newprovider.com/checkout', $response->redirectUrl);
    }

    public function test_verify_webhook_valid_signature()
    {
        Config::set('services.new_provider.webhook_secret', 'my_sandbox_secret');
        $gateway = new NewProviderGateway();
        
        $payload = json_encode(['id' => '123']);
        $timestamp = time();
        $signedPayload = $timestamp . '.' . $payload;
        $signature = hash_hmac('sha256', $signedPayload, 'my_sandbox_secret');

        $request = Request::create('/webhook', 'POST', [], [], [], [
            'HTTP_X-NewProvider-Signature' => $signature,
            'HTTP_X-NewProvider-Timestamp' => $timestamp
        ], $payload);

        $this->assertTrue($gateway->verifyWebhook($request));
    }

    public function test_verify_webhook_invalid_signature()
    {
        Config::set('services.new_provider.webhook_secret', 'my_sandbox_secret');
        $gateway = new NewProviderGateway();
        
        $payload = json_encode(['id' => '123']);
        $timestamp = time();
        $signedPayload = $timestamp . '.' . $payload;
        $signature = hash_hmac('sha256', $signedPayload, 'WRONG_SECRET');

        $request = Request::create('/webhook', 'POST', [], [], [], [
            'HTTP_X-NewProvider-Signature' => $signature,
            'HTTP_X-NewProvider-Timestamp' => $timestamp
        ], $payload);

        $this->assertFalse($gateway->verifyWebhook($request));
    }

    public function test_verify_webhook_replay_attack()
    {
        Config::set('services.new_provider.webhook_secret', 'my_sandbox_secret');
        $gateway = new NewProviderGateway();
        
        $payload = json_encode(['id' => '123']);
        // 6 minutes old timestamp
        $timestamp = time() - 360; 
        $signedPayload = $timestamp . '.' . $payload;
        $signature = hash_hmac('sha256', $signedPayload, 'my_sandbox_secret');

        $request = Request::create('/webhook', 'POST', [], [], [], [
            'HTTP_X-NewProvider-Signature' => $signature,
            'HTTP_X-NewProvider-Timestamp' => $timestamp
        ], $payload);

        // Should be rejected for replay attack
        $this->assertFalse($gateway->verifyWebhook($request));
    }

    public function test_verify_webhook_altered_payload()
    {
        Config::set('services.new_provider.webhook_secret', 'my_sandbox_secret');
        $gateway = new NewProviderGateway();
        
        $payload = json_encode(['amount' => 100]);
        $timestamp = time();
        $signedPayload = $timestamp . '.' . $payload;
        $signature = hash_hmac('sha256', $signedPayload, 'my_sandbox_secret');

        // Alter payload after signature
        $alteredPayload = json_encode(['amount' => 999]);

        $request = Request::create('/webhook', 'POST', [], [], [], [
            'HTTP_X-NewProvider-Signature' => $signature,
            'HTTP_X-NewProvider-Timestamp' => $timestamp
        ], $alteredPayload);

        $this->assertFalse($gateway->verifyWebhook($request));
    }
}
