<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Merchant;
use App\Services\Auth\ApiKeyManagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PaymentsIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_idempotency_returns_cached_response_for_same_key()
    {
        $merchant = Merchant::factory()->create();
        $keyData = app(ApiKeyManagementService::class)->generateKeys($merchant->id, 'sandbox');

        $idempotencyKey = 'idemp_' . uniqid();
        $payload = [
            'amount' => 100,
            'currency' => 'BRL',
            'reference_id' => 'pedido_idempotente_001',
            'customer' => [
                'name' => 'Cliente Idempotente',
                'email' => 'cliente@example.com',
                'document' => '12345678909',
            ],
        ];

        $response1 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $keyData['secret_key'],
            'Accept' => 'application/json',
            'Idempotency-Key' => $idempotencyKey
        ])->postJson('/api/v1/sessions', $payload);

        $response1->assertStatus(201);
        $this->assertArrayHasKey('session_id', $response1->json());

        $response2 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $keyData['secret_key'],
            'Accept' => 'application/json',
            'Idempotency-Key' => $idempotencyKey
        ])->postJson('/api/v1/sessions', $payload);

        $response2->assertStatus(201);
        $this->assertEquals($response1->json(), $response2->json());
        $this->assertEquals('true', $response2->headers->get('Idempotent-Replayed'));
    }
}
