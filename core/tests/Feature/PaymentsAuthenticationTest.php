<?php

namespace Tests\Feature;

use App\Models\Merchant;
use App\Services\Auth\ApiKeyManagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentsAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_missing_api_key_returns_unauthorized()
    {
        $response = $this->getJson('/api/v1/me');

        $response->assertStatus(401)
            ->assertJson([
                'error' => [
                    'type' => 'authentication_error',
                    'code' => 'invalid_api_key',
                    'message' => 'Unauthorized.'
                ]
            ]);
    }

    public function test_invalid_api_key_returns_unauthorized()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer invalid_key'
        ])->getJson('/api/v1/me');

        $response->assertStatus(401)
            ->assertJson([
                'error' => [
                    'type' => 'authentication_error',
                    'code' => 'invalid_api_key',
                    'message' => 'Unauthorized.'
                ]
            ]);
    }

    public function test_valid_api_key_returns_merchant_context()
    {
        $merchant = Merchant::factory()->create();
        $keyData = app(ApiKeyManagementService::class)->generateKeys($merchant->id, 'sandbox');

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $keyData['secret_key']
        ])->getJson('/api/v1/me');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'merchant',
                'environment',
                'permissions',
                'api_version',
                'request_id'
            ])
            ->assertJson([
                'merchant' => (string) $merchant->id,
                'environment' => 'sandbox',
            ]);

        $this->assertNotEmpty($response->headers->get('X-OriginPay-Request-Id'));
    }
}
