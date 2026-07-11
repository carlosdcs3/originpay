<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Merchant;
use App\Models\WebhookEndpoint;
use App\Models\WebhookDelivery;
use App\Enums\ChargeStatus;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ChargeEngineTest extends TestCase
{
    use RefreshDatabase;

    public function test_charge_creation_success_mock()
    {
        // 1. Create a merchant
        $merchant = Merchant::factory()->create();
        
        // Setup API Key
        $apiKeyService = app(\App\Services\Auth\ApiKeyManagementService::class);
        $keyData = $apiKeyService->generateKeys($merchant->id, 'sandbox');
        $secretKey = $keyData['secret_key'];

        // 2. Setup Webhook Endpoint
        WebhookEndpoint::create([
            'merchant_id' => $merchant->id,
            'url' => 'https://webhook.site/test',
            'secret_encrypted' => Crypt::encryptString('whsec_test'),
            'secret_preview' => 'whsec_test',
            'environment' => 'sandbox',
            'events' => ['*'],
            'status' => 'active'
        ]);

        // 3. Perform a Charge (amount = 1000 => SUCCESS mock)
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$secretKey}"
        ])->postJson('/api/v1/core/charges', [
            'amount' => 1000,
            'currency' => 'BRL',
            'metadata' => ['order_id' => '123']
        ]);

        $response->assertStatus(201);
        $data = $response->json('data');
        
        $this->assertEquals(ChargeStatus::SUCCEEDED->value, $data['status']);
        
        $chargeId = $data['id'];

        // 4. Verify Webhooks were scheduled (charge.created + charge.succeeded)
        $deliveries = WebhookDelivery::where('webhook_endpoint_id', WebhookEndpoint::first()->id)->get();
        
        // We expect 2 deliveries: charge.created and charge.succeeded
        $this->assertCount(2, $deliveries);

        // 5. Test idempotency
        $idempotentResponse = $this->withHeaders([
            'Authorization' => "Bearer {$secretKey}",
            'Idempotency-Key' => 'test-idempotency-key'
        ])->postJson('/api/v1/core/charges', [
            'amount' => 1000,
            'currency' => 'BRL'
        ]);

        $idempotentResponse2 = $this->withHeaders([
            'Authorization' => "Bearer {$secretKey}",
            'Idempotency-Key' => 'test-idempotency-key'
        ])->postJson('/api/v1/core/charges', [
            'amount' => 1000,
            'currency' => 'BRL'
        ]);

        $this->assertEquals($idempotentResponse->json(), $idempotentResponse2->json());

        // 6. Test GET Charge isolated by merchant
        $getResponse = $this->withHeaders([
            'Authorization' => "Bearer {$secretKey}"
        ])->getJson("/api/v1/core/charges/{$chargeId}");

        $getResponse->assertStatus(200);
        $this->assertEquals(ChargeStatus::SUCCEEDED->value, $getResponse->json('data.status'));
    }

    public function test_charge_creation_failed_mock()
    {
        $merchant = Merchant::factory()->create();
        $apiKeyService = app(\App\Services\Auth\ApiKeyManagementService::class);
        $keyData = $apiKeyService->generateKeys($merchant->id, 'sandbox');
        $secretKey = $keyData['secret_key'];

        // amount = 999 -> FAILED
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$secretKey}"
        ])->postJson('/api/v1/core/charges', [
            'amount' => 999,
            'currency' => 'BRL'
        ]);

        $response->assertStatus(201);
        $this->assertEquals(ChargeStatus::FAILED->value, $response->json('data.status'));
        $this->assertEquals('mock_decline', $response->json('data.failure_code'));
    }
}
