<?php

namespace Tests\Feature;

use App\Enums\ChargeStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentOperation;
use App\Enums\RoutingStrategy;
use App\Http\Middleware\BlockIp;
use App\Http\Middleware\CheckTransactionPassword;
use App\Http\Middleware\CheckUserStatus;
use App\Http\Middleware\EnsureTwoFactorAuthenticated;
use App\Jobs\ProcessGatewayWebhookJob;
use App\Models\ApiKey;
use App\Models\Charge;
use App\Models\Currency;
use App\Models\PaymentGateway;
use App\Models\PaymentMethodRoute;
use App\Models\PlatformFeeRule;
use App\Models\User;
use App\Models\Wallet;
use App\Services\ChargeService;
use App\Services\CircuitBreakerService;
use App\Services\Fraud\FraudEngineService;
use App\Services\GatewayHealthScoreService;
use App\Services\GatewayMetricsService;
use App\Services\PlatformFeeService;
use App\Services\WalletService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Str;
use Tests\TestCase;

class BoletoChargeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app()->instance('redis', new BoletoRedisFake());
        Facade::clearResolvedInstance('redis');

        app()->instance(FraudEngineService::class, new class {
            public function evaluateRisk(array $customerData, string $ip, int $userId): array
            {
                return ['is_blocked' => false, 'reasons' => []];
            }
        });

        app()->instance(PlatformFeeService::class, new class extends PlatformFeeService {
            public function calculateFee(float $amount): array
            {
                return [
                    'platform_fee' => 0,
                    'gateway_fee' => 0.70,
                    'total_fee' => 0.70,
                    'net_amount' => $amount - 0.70,
                    'fee_breakdown' => [],
                ];
            }
        });

        app()->instance(WalletService::class, new BoletoWalletServiceFake());

        app()->instance(CircuitBreakerService::class, new class {
            public function getState(string $gatewayCode): string
            {
                return CircuitBreakerService::STATE_CLOSED;
            }

            public function attemptRequest(string $gatewayCode): bool
            {
                return true;
            }

            public function recordSuccess(string $gatewayCode): void
            {
            }

            public function recordFailure(string $gatewayCode, Exception $exception): void
            {
            }
        });

        app()->instance(GatewayHealthScoreService::class, new class {
            public function getScore(string $gatewayCode): int
            {
                return 100;
            }
        });

        app()->instance(GatewayMetricsService::class, new class {
            public function increment(string $metric, int $value = 1): void
            {
            }

            public function recordLatency(string $metric, int $milliseconds): void
            {
            }
        });
    }

    public function test_boleto_charge_is_created_and_persists_boleto_fields_and_fees(): void
    {
        [$user] = $this->fixture();

        $charge = app(ChargeService::class)->create($user, 100.00, 'boleto', [
            'idempotency_key' => 'boleto-create-1',
            'name' => 'Cliente Boleto',
            'email' => 'cliente@example.com',
            'document' => '12345678909',
            'description' => 'Pedido boleto',
        ])->refresh();

        $this->assertSame(PaymentMethod::BOLETO, $charge->payment_method);
        $this->assertSame(ChargeStatus::WAITING_PAYMENT, $charge->status);
        $this->assertNotNull($charge->boleto_url);
        $this->assertNotNull($charge->boleto_pdf_url);
        $this->assertNotNull($charge->barcode);
        $this->assertNotNull($charge->digitable_line);
        $this->assertNotNull($charge->expires_at);
        $this->assertEquals(2.30, (float) $charge->platform_fee);
        $this->assertEquals(0.70, (float) $charge->gateway_fee);
        $this->assertEquals(97.00, (float) $charge->net_amount);
        $this->assertSame('global', $charge->fee_snapshot['source']);
    }

    public function test_api_returns_boleto_payload(): void
    {
        [$user, $headers] = $this->fixtureWithApiKey();

        $response = $this->postJson('/api/v1/charges', [
            'amount' => 100.00,
            'payment_method' => 'boleto',
            'description' => 'Pedido API boleto',
            'customer' => [
                'name' => 'Cliente API',
                'email' => 'api@example.com',
                'document' => '12345678909',
            ],
        ], $headers);

        $response->assertCreated()
            ->assertJsonPath('payment_method', 'boleto')
            ->assertJsonPath('status', 'waiting_payment')
            ->assertJsonStructure([
                'id',
                'status',
                'payment_method',
                'boleto' => ['url', 'pdf', 'barcode', 'digitable_line', 'expires_at'],
            ]);

        $this->assertSame($user->id, Charge::firstOrFail()->user_id);
    }

    public function test_paid_and_expired_webhooks_update_boleto_charge(): void
    {
        [$user, $gateway, $wallet] = $this->fixture();

        $paidCharge = Charge::factory()->create([
            'user_id' => $user->id,
            'wallet_id' => $wallet->id,
            'currency_id' => $wallet->currency_id,
            'gateway_id' => $gateway->id,
            'payment_method' => PaymentMethod::BOLETO,
            'gateway_charge_id' => 'boleto_paid_1',
            'status' => ChargeStatus::WAITING_PAYMENT,
            'platform_fee' => 2.30,
            'net_amount' => 97.00,
        ]);

        app(ProcessGatewayWebhookJob::class, [
            'provider' => 'mock',
            'payload' => ['gateway_charge_id' => 'boleto_paid_1', 'status' => 'paid'],
            'headers' => [],
        ])->handle(app(ChargeService::class));

        $paidCharge->refresh();
        $this->assertSame(ChargeStatus::PAID, $paidCharge->status);
        $this->assertNotNull($paidCharge->paid_at);

        $expiredCharge = Charge::factory()->create([
            'user_id' => $user->id,
            'wallet_id' => $wallet->id,
            'currency_id' => $wallet->currency_id,
            'gateway_id' => $gateway->id,
            'payment_method' => PaymentMethod::BOLETO,
            'gateway_charge_id' => 'boleto_expired_1',
            'status' => ChargeStatus::WAITING_PAYMENT,
        ]);

        app(ProcessGatewayWebhookJob::class, [
            'provider' => 'mock',
            'payload' => ['gateway_charge_id' => 'boleto_expired_1', 'status' => 'expired'],
            'headers' => [],
        ])->handle(app(ChargeService::class));

        $this->assertSame(ChargeStatus::EXPIRED, $expiredCharge->refresh()->status);
    }

    public function test_second_copy_updates_boleto_links_without_new_charge(): void
    {
        [$user, $gateway, $wallet] = $this->fixture();
        $charge = Charge::factory()->create([
            'user_id' => $user->id,
            'wallet_id' => $wallet->id,
            'currency_id' => $wallet->currency_id,
            'gateway_id' => $gateway->id,
            'payment_method' => PaymentMethod::BOLETO,
            'gateway_charge_id' => 'old_boleto',
            'boleto_url' => null,
            'boleto_pdf_url' => null,
            'barcode' => null,
            'digitable_line' => null,
        ]);

        $this->withoutMiddleware($this->dashboardOnlyMiddleware())
            ->actingAs($user)
            ->post(route('user.boleto.second-copy', $charge->id))
            ->assertRedirect();

        $charge->refresh();
        $this->assertSame(1, Charge::count());
        $this->assertNotNull($charge->boleto_url);
        $this->assertNotNull($charge->boleto_pdf_url);
        $this->assertNotNull($charge->barcode);
        $this->assertNotNull($charge->digitable_line);
    }

    public function test_legacy_boleto_dashboard_redirects_to_charges_filtered_by_boleto(): void
    {
        [$user] = $this->fixture();
        $other = User::factory()->create(['email_verified_at' => now()]);

        Charge::factory()->create([
            'user_id' => $user->id,
            'payment_method' => PaymentMethod::BOLETO,
            'customer_name' => 'Cliente Boleto Visivel',
            'digitable_line' => 'linha-visivel',
        ]);
        Charge::factory()->create([
            'user_id' => $user->id,
            'payment_method' => PaymentMethod::PIX,
            'customer_name' => 'Cliente Pix Oculto',
        ]);
        Charge::factory()->create([
            'user_id' => $other->id,
            'payment_method' => PaymentMethod::BOLETO,
            'customer_name' => 'Cliente Outro Merchant',
        ]);

        $this->withoutMiddleware($this->dashboardOnlyMiddleware())
            ->actingAs($user)
            ->get(route('user.boleto.index'))
            ->assertRedirect(route('user.charge.index', ['method' => 'boleto']));

        $response = $this->withoutMiddleware($this->dashboardOnlyMiddleware())
            ->actingAs($user)
            ->get(route('user.charge.index', ['method' => 'boleto']));

        $response->assertOk();
        $response->assertSee('Cobranças');
        $response->assertSee('Cliente Boleto Visivel');
        $response->assertDontSee('Cliente Pix Oculto');
        $response->assertDontSee('Cliente Outro Merchant');
    }

    private function fixtureWithApiKey(): array
    {
        [$user] = $this->fixture();
        $plain = 'sk_test_' . Str::random(32);
        ApiKey::factory()->for($user, 'user')->forPlainKey($plain)->create();

        return [$user, [
            'Authorization' => 'Bearer ' . $plain,
            'Accept' => 'application/json',
            'Idempotency-Key' => 'boleto-api-' . Str::random(8),
        ]];
    }

    private function fixture(): array
    {
        $currency = Currency::factory()->create([
            'code' => 'BRL',
            'default' => true,
            'auto_wallet' => true,
        ]);

        $user = User::factory()->create(['email_verified_at' => now()]);
        $wallet = Wallet::factory()->create([
            'user_id' => $user->id,
            'currency_id' => $currency->id,
        ]);

        PlatformFeeRule::create([
            'scope' => PlatformFeeRule::SCOPE_GLOBAL,
            'user_id' => null,
            'payment_method' => 'boleto',
            'currency' => 'BRL',
            'fixed_fee' => 0.30,
            'percentage_fee' => 2.00,
            'minimum_fee' => 0,
            'maximum_fee' => null,
            'settlement_delay_days' => 1,
            'reserve_percentage' => 0,
            'status' => PlatformFeeRule::STATUS_ACTIVE,
            'starts_at' => now()->subDay(),
            'ends_at' => null,
            'metadata' => ['test' => true],
        ]);

        $gateway = PaymentGateway::create([
            'provider' => 'mock',
            'adapter' => 'mock',
            'logo' => '',
            'name' => 'Mock Gateway',
            'code' => 'mock',
            'currencies' => ['BRL'],
            'credentials' => [],
            'is_withdraw' => 0,
            'status' => 1,
            'is_maintenance' => 0,
            'priority' => 1,
            'is_sandbox' => 1,
            'supports_pix' => 1,
            'supports_card' => 0,
            'supports_boleto' => 1,
            'supports_crypto' => 0,
            'supports_refund' => 0,
            'supports_withdrawal' => 0,
        ]);

        PaymentMethodRoute::create([
            'payment_method' => 'boleto',
            'payment_operation' => PaymentOperation::BOLETO->value,
            'primary_gateway_id' => $gateway->id,
            'fallback_gateway_ids' => [],
            'routing_strategy' => RoutingStrategy::MANUAL->value,
            'enabled' => true,
        ]);

        return [$user, $gateway, $wallet];
    }

    private function dashboardOnlyMiddleware(): array
    {
        return [
            CheckUserStatus::class,
            EnsureTwoFactorAuthenticated::class,
            BlockIp::class,
            CheckTransactionPassword::class,
        ];
    }
}

class BoletoRedisFake
{
    public function connection(): self
    {
        return $this;
    }

    public function funnel(string $key): BoletoRedisLimiterFake
    {
        return new BoletoRedisLimiterFake();
    }
}

class BoletoRedisLimiterFake
{
    public function limit(int $limit): self
    {
        return $this;
    }

    public function then(callable $callback, callable $fallback): mixed
    {
        return $callback();
    }
}

class BoletoWalletServiceFake extends WalletService
{
    public function getDefaultWalletByUserId(int $userId): ?\App\Models\Wallet
    {
        return \App\Models\Wallet::withoutGlobalScopes()->where('user_id', $userId)->first();
    }

    public function creditPending(\App\Models\Wallet $wallet, float $amount, string $description, $reference = null, ?string $correlationId = null, ?string $idempotencyKey = null): \App\Models\WalletTransaction
    {
        return $this->transaction($wallet, $amount, 'charge', $description, $reference, $correlationId, $idempotencyKey);
    }

    public function settlePendingToAvailable(\App\Models\Wallet $wallet, float $amount, string $description, $reference = null, ?string $correlationId = null, ?string $idempotencyKey = null): \App\Models\WalletTransaction
    {
        return $this->transaction($wallet, $amount, 'adjustment', $description, $reference, $correlationId, $idempotencyKey);
    }

    public function debitAvailable(\App\Models\Wallet $wallet, float $amount, string $type, string $description, $reference = null, bool $forceNegative = false, ?string $correlationId = null, ?string $idempotencyKey = null): \App\Models\WalletTransaction
    {
        return $this->transaction($wallet, -$amount, $type, $description, $reference, $correlationId, $idempotencyKey);
    }

    private function transaction(\App\Models\Wallet $wallet, float $amount, string $type, string $description, $reference = null, ?string $correlationId = null, ?string $idempotencyKey = null): \App\Models\WalletTransaction
    {
        return \App\Models\WalletTransaction::create([
            'user_id' => $wallet->user_id,
            'wallet_id' => $wallet->id,
            'type' => $type,
            'amount' => $amount,
            'balance_before' => 0,
            'balance_after' => 0,
            'description' => $description,
            'reference_type' => $reference ? get_class($reference) : null,
            'reference_id' => $reference ? $reference->id : null,
            'correlation_id' => $correlationId,
            'idempotency_key' => $idempotencyKey,
        ]);
    }
}
