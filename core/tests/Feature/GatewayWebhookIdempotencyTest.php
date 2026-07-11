<?php

namespace Tests\Feature;

use App\Jobs\ProcessGatewayWebhookJob;
use App\Models\PaymentGateway;
use App\Models\WebhookEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class GatewayWebhookIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_gateway_webhook_is_persisted_before_queue_and_duplicate_is_blocked(): void
    {
        Queue::fake();

        $secret = 'whsec_test';
        PaymentGateway::factory()->create([
            'code' => 'efi',
            'credentials' => [
                'webhook_secret' => $secret,
                'webhook_requires_timestamp' => true,
            ],
        ]);

        $payload = ['pix' => [['txid' => 'txid_r2_001', 'valor' => '10.00']]];
        $rawPayload = json_encode($payload);
        $timestamp = (string) time();
        $signature = hash_hmac('sha256', $timestamp . '.' . $rawPayload, $secret);

        $headers = [
            'X-Webhook-Timestamp' => $timestamp,
            'X-Webhook-Signature' => $signature,
            'X-Correlation-ID' => 'corr-r2-001',
        ];

        $this->postJson('/api/webhooks/gateway/efi', $payload, $headers)
            ->assertOk()
            ->assertJson(['status' => 'received']);

        $this->assertDatabaseHas('webhook_events', [
            'provider' => 'EFI',
            'event_id' => 'txid_r2_001',
            'correlation_id' => 'corr-r2-001',
        ]);

        Queue::assertPushed(ProcessGatewayWebhookJob::class, 1);

        $this->postJson('/api/webhooks/gateway/efi', $payload, $headers)
            ->assertOk()
            ->assertJson(['status' => 'received', 'duplicate' => true]);

        Queue::assertPushed(ProcessGatewayWebhookJob::class, 1);
        $this->assertSame(1, WebhookEvent::where('provider', 'EFI')->where('event_id', 'txid_r2_001')->count());
    }

    public function test_invalid_signature_does_not_persist_or_enqueue(): void
    {
        Queue::fake();

        PaymentGateway::factory()->create([
            'code' => 'efi',
            'credentials' => [
                'webhook_secret' => 'whsec_test',
                'webhook_requires_timestamp' => true,
            ],
        ]);

        $this->postJson('/api/webhooks/gateway/efi', ['pix' => [['txid' => 'txid_bad']]], [
            'X-Webhook-Timestamp' => (string) time(),
            'X-Webhook-Signature' => 'invalid',
        ])->assertStatus(401);

        $this->assertDatabaseMissing('webhook_events', [
            'provider' => 'EFI',
            'event_id' => 'txid_bad',
        ]);

        Queue::assertNothingPushed();
    }
}
