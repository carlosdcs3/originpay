<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Charge as EloquentCharge;
use App\Enums\ChargeStatus;
use Illuminate\Support\Facades\Event;
use App\Events\ChargeStatusChanged;
use App\Services\Gateways\GatewayManager;
use App\Domain\Payments\GatewayResult;
use Illuminate\Foundation\Testing\RefreshDatabase;

class EfiWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_efi_webhook_updates_charge_to_succeeded()
    {
        Event::fake([ChargeStatusChanged::class]);

        $charge = EloquentCharge::factory()->create([
            'charge_id' => 'ch_webhook_test',
            'status' => ChargeStatus::PENDING->value,
            'metadata' => [
                'internal_metadata' => [
                    'txid' => 'txid_777'
                ]
            ]
        ]);

        // Mock GatewayManager to return a success status
        $mockGatewayManager = \Mockery::mock(GatewayManager::class);
        $mockGatewayManager->shouldReceive('getStatus')
            ->with($charge->merchant_id, $charge->environment, 'efi', 'txid_777')
            ->andReturn(new GatewayResult(
                success: true,
                status: 'succeeded',
                gatewayReference: 'txid_777',
                isTechnicalFailure: false
            ));

        $this->app->instance(GatewayManager::class, $mockGatewayManager);

        $payload = [
            'pix' => [
                [
                    'txid' => 'txid_777',
                    'valor' => '50.00'
                ]
            ]
        ];

        $response = $this->postJson('/api/webhooks/efi', $payload);

        $response->assertStatus(200);

        $this->assertDatabaseHas('api_charges', [
            'id' => $charge->id,
            'status' => ChargeStatus::SUCCEEDED->value
        ]);

        Event::assertDispatched(ChargeStatusChanged::class);
    }
}
