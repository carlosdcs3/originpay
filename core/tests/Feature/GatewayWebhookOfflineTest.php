<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\PaymentGateway;
use Illuminate\Support\Facades\Queue;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Mockery;

class GatewayWebhookOfflineTest extends TestCase
{
    public function test_returns_500_if_redis_is_offline_during_dispatch()
    {
        $this->ensurePaymentGatewaysTableExists();

        PaymentGateway::query()->updateOrCreate(['code' => 'test_provider'], [
            'provider' => 'test_provider',
            'adapter' => 'test',
            'logo' => '',
            'name' => 'Test Provider',
            'currencies' => ['BRL'],
            'credentials' => ['webhook_secret' => 'offline-secret'],
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
        ]);

        $payload = json_encode([
            'event' => 'payment.created',
            'id' => 123,
        ]);

        // Simula falha ao enviar job (Ex: Redis down throw exception)
        Queue::shouldReceive('connection')->andThrow(new \Exception("Redis connection refused"));

        // Envia Webhook
        $response = $this->call(
            'POST',
            '/api/webhooks/gateway/test_provider',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_X_WEBHOOK_SIGNATURE' => hash_hmac('sha256', $payload, 'offline-secret'),
            ],
            $payload
        );

        // Verifica o retorno 500
        $response->assertStatus(500);
        $response->assertJson([
            'error' => [
                'type' => 'api_error',
                'code' => 'internal_error',
                'message' => 'An internal error occurred.',
            ],
        ]);
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
}
