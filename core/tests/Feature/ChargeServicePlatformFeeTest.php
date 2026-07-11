<?php

namespace Tests\Feature;

use App\Enums\PaymentOperation;
use App\Enums\RoutingStrategy;
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
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class ChargeServicePlatformFeeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app()->instance('redis', new ChargeServicePlatformFeeRedisFake());
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
                    'platform_fee' => 999.99,
                    'gateway_fee' => 0.70,
                    'total_fee' => 0.70,
                    'net_amount' => $amount - 0.70,
                    'fee_breakdown' => [],
                ];
            }
        });

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
        });
    }

    public function test_charge_without_override_uses_global_platform_fee_rule(): void
    {
        [$user] = $this->fixture();
        $globalRule = $this->createRule(['percentage_fee' => 2.00, 'fixed_fee' => 0.30]);

        $charge = $this->createCharge($user);

        $this->assertEquals(2.30, (float) $charge->platform_fee);
        $this->assertEquals(0.70, (float) $charge->gateway_fee);
        $this->assertEquals(97.00, (float) $charge->net_amount);
        $this->assertSame($globalRule->id, $charge->fee_rule_id);
        $this->assertSame('global', $charge->fee_snapshot['source']);
    }

    public function test_charge_with_override_uses_individual_platform_fee_rule(): void
    {
        [$user] = $this->fixture();
        $this->createRule(['percentage_fee' => 2.00, 'fixed_fee' => 0.30]);
        $merchantRule = $this->createRule([
            'scope' => PlatformFeeRule::SCOPE_MERCHANT,
            'user_id' => $user->id,
            'percentage_fee' => 1.00,
            'fixed_fee' => 0.10,
        ]);

        $charge = $this->createCharge($user);

        $this->assertEquals(1.10, (float) $charge->platform_fee);
        $this->assertEquals(98.20, (float) $charge->net_amount);
        $this->assertSame($merchantRule->id, $charge->fee_rule_id);
        $this->assertSame('merchant', $charge->fee_snapshot['source']);
    }

    public function test_charge_persists_fee_snapshot_and_does_not_recalculate_after_rule_changes(): void
    {
        [$user] = $this->fixture();
        $rule = $this->createRule(['percentage_fee' => 2.00, 'fixed_fee' => 0.30]);

        $charge = $this->createCharge($user);
        $snapshot = $charge->fee_snapshot;

        $rule->update(['percentage_fee' => 9.99, 'fixed_fee' => 9.99]);

        $charge->refresh();

        $this->assertEquals(2.30, (float) $charge->platform_fee);
        $this->assertSame($snapshot, $charge->fee_snapshot);
        $this->assertEquals(2.30, $charge->fee_snapshot['platform_fee_amount']);
    }

    public function test_charge_uses_safe_fallback_when_no_active_rule_exists(): void
    {
        Log::shouldReceive('warning')
            ->once()
            ->with('Using fallback platform fee rule for charge creation.', \Mockery::type('array'));

        [$user] = $this->fixture();

        $charge = $this->createCharge($user);

        $this->assertNull($charge->fee_rule_id);
        $this->assertEquals(2.30, (float) $charge->platform_fee);
        $this->assertSame('fallback', $charge->fee_snapshot['source']);
    }

    public function test_negative_net_amount_is_rejected(): void
    {
        [$user] = $this->fixture();
        $this->createRule([
            'fixed_fee' => 200.00,
            'percentage_fee' => 0,
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Platform fee cannot be greater than the gross amount.');

        $this->createCharge($user);
    }

    private function createCharge(User $user): Charge
    {
        return app(ChargeService::class)->create($user, 100.00, 'pix', [
            'idempotency_key' => 'fee-test-' . uniqid(),
            'name' => 'Fee Test Customer',
            'email' => 'fee-test@example.com',
        ])->refresh();
    }

    private function createRule(array $overrides = []): PlatformFeeRule
    {
        return PlatformFeeRule::create(array_merge([
            'scope' => PlatformFeeRule::SCOPE_GLOBAL,
            'user_id' => null,
            'payment_method' => 'pix',
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
        ], $overrides));
    }

    private function fixture(): array
    {
        $currency = Currency::factory()->create([
            'code' => 'BRL',
            'default' => true,
            'auto_wallet' => true,
        ]);

        $user = User::factory()->create();
        Wallet::factory()->create([
            'user_id' => $user->id,
            'currency_id' => $currency->id,
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
            'supports_boleto' => 0,
            'supports_crypto' => 0,
            'supports_refund' => 0,
            'supports_withdrawal' => 0,
        ]);

        PaymentMethodRoute::create([
            'payment_method' => 'pix',
            'payment_operation' => PaymentOperation::PIX_CHARGE->value,
            'primary_gateway_id' => $gateway->id,
            'fallback_gateway_ids' => [],
            'routing_strategy' => RoutingStrategy::MANUAL->value,
            'enabled' => true,
        ]);

        return [$user, $gateway];
    }
}

class ChargeServicePlatformFeeRedisFake
{
    public function connection(): self
    {
        return $this;
    }

    public function funnel(string $key): ChargeServicePlatformFeeRedisLimiterFake
    {
        return new ChargeServicePlatformFeeRedisLimiterFake();
    }
}

class ChargeServicePlatformFeeRedisLimiterFake
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
