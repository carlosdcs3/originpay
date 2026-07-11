<?php

namespace Tests\Feature;

use App\Jobs\ProcessGatewayWebhookJob;
use App\Models\PaymentGateway;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class GatewayWebhookValidationTest extends TestCase
{
    private const PROVIDER = 'test_provider_cr04';

    private const SECRET = 'cr04-secret';

    protected function setUp(): void
    {
        parent::setUp();

        $this->ensurePaymentGatewaysTableExists();
        $this->ensureWebhookEventsTableExists();

        PaymentGateway::query()->updateOrCreate(
            ['code' => self::PROVIDER],
            [
                'provider' => self::PROVIDER,
                'adapter' => 'test',
                'logo' => '',
                'name' => 'CR-04 Test Provider',
                'currencies' => ['BRL'],
                'credentials' => ['webhook_secret' => self::SECRET],
                'is_withdraw' => 0,
                'status' => 1,
                'is_maintenance' => 0,
                'priority' => 1,
                'is_sandbox' => 1,
                'supports_pix' => 1,
                'supports_card' => 0,
                'supports_boleto' => 0,
                'supports_crypto' => 0,
                'supports_refund' => 0,
                'supports_withdrawal' => 0,
            ]
        );
    }

    private function ensureWebhookEventsTableExists(): void
    {
        if (Schema::hasTable('webhook_events')) {
            return;
        }

        Schema::create('webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('provider');
            $table->string('event_id')->nullable();
            $table->string('external_reference')->nullable();
            $table->string('event_type')->nullable();
            $table->longText('payload');
            $table->longText('headers')->nullable();
            $table->string('status')->default('RECEIVED');
            $table->integer('attempts')->default(0);
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('last_error')->nullable();
            $table->json('metadata')->nullable();
            $table->string('payload_hash')->nullable();
            $table->uuid('correlation_id')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'event_id']);
        });
    }

    private function ensurePaymentGatewaysTableExists(): void
    {
        if (Schema::hasTable('payment_gateways')) {
            return;
        }

        Schema::create('payment_gateways', function (Blueprint $table) {
            $table->id();
            $table->string('provider')->nullable();
            $table->string('adapter')->nullable();
            $table->string('logo')->nullable();
            $table->string('name');
            $table->string('code')->unique();
            $table->json('currencies')->nullable();
            $table->json('credentials')->nullable();
            $table->boolean('is_withdraw')->default(false);
            $table->boolean('status')->default(true);
            $table->boolean('is_maintenance')->default(false);
            $table->integer('priority')->default(1);
            $table->boolean('is_sandbox')->default(true);
            $table->boolean('supports_pix')->default(false);
            $table->boolean('supports_card')->default(false);
            $table->boolean('supports_boleto')->default(false);
            $table->boolean('supports_crypto')->default(false);
            $table->boolean('supports_refund')->default(false);
            $table->boolean('supports_withdrawal')->default(false);
            $table->timestamps();
        });
    }

    public function test_webhook_without_signature_is_rejected_and_not_queued(): void
    {
        Queue::fake();

        $response = $this->postJson($this->endpoint(), [
            'event' => 'payment.paid',
            'id' => 'evt_missing_signature',
        ]);

        $response->assertStatus(401);
        Queue::assertNothingPushed();
    }

    public function test_webhook_with_invalid_signature_is_rejected_and_not_queued(): void
    {
        Queue::fake();

        $response = $this->postJson($this->endpoint(), [
            'event' => 'payment.paid',
            'id' => 'evt_invalid_signature',
        ], [
            'X-Webhook-Signature' => 'invalid',
        ]);

        $response->assertStatus(401);
        Queue::assertNothingPushed();
    }

    public function test_unknown_provider_is_rejected_and_not_queued(): void
    {
        Queue::fake();

        $response = $this->postJson('/api/webhooks/gateway/provider_that_does_not_exist', [
            'event' => 'payment.paid',
            'id' => 'evt_unknown_provider',
        ], [
            'X-Webhook-Signature' => 'anything',
        ]);

        $response->assertStatus(404);
        Queue::assertNothingPushed();
    }

    public function test_invalid_payload_is_rejected_and_not_queued(): void
    {
        Queue::fake();

        $rawPayload = 'not-json';
        $signature = $this->signature($rawPayload);

        $response = $this->call(
            'POST',
            $this->endpoint(),
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_X_WEBHOOK_SIGNATURE' => $signature,
            ],
            $rawPayload
        );

        $response->assertStatus(422);
        Queue::assertNothingPushed();
    }

    public function test_provider_without_validation_support_is_rejected_and_not_queued(): void
    {
        Queue::fake();

        PaymentGateway::query()->updateOrCreate(
            ['code' => 'provider_without_webhook_secret'],
            [
                'provider' => 'provider_without_webhook_secret',
                'adapter' => 'test',
                'logo' => '',
                'name' => 'Provider Without Webhook Secret',
                'currencies' => ['BRL'],
                'credentials' => [],
                'is_withdraw' => 0,
                'status' => 1,
                'is_maintenance' => 0,
                'priority' => 1,
                'is_sandbox' => 1,
                'supports_pix' => 1,
                'supports_card' => 0,
                'supports_boleto' => 0,
                'supports_crypto' => 0,
                'supports_refund' => 0,
                'supports_withdrawal' => 0,
            ]
        );

        $response = $this->postJson('/api/webhooks/gateway/provider_without_webhook_secret', [
            'event' => 'payment.paid',
            'id' => 'evt_no_validation_support',
        ], [
            'X-Webhook-Signature' => 'anything',
        ]);

        $response->assertStatus(400);
        Queue::assertNothingPushed();
    }

    public function test_valid_webhook_is_accepted_and_queued(): void
    {
        Queue::fake();

        $payload = [
            'event' => 'payment.paid',
            'id' => 'evt_valid',
        ];
        $rawPayload = json_encode($payload);

        $response = $this->call(
            'POST',
            $this->endpoint(),
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_X_WEBHOOK_SIGNATURE' => $this->signature($rawPayload),
            ],
            $rawPayload
        );

        $response->assertStatus(200);
        Queue::assertPushed(ProcessGatewayWebhookJob::class);
    }

    private function endpoint(): string
    {
        return '/api/webhooks/gateway/'.self::PROVIDER;
    }

    private function signature(string $rawPayload): string
    {
        return hash_hmac('sha256', $rawPayload, self::SECRET);
    }
}
