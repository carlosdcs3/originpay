<?php

namespace Tests\Feature;

use App\Enums\KycStatus;
use App\Enums\PaymentOperation;
use App\Enums\RoutingStrategy;
use App\Models\Charge;
use App\Models\PaymentGateway;
use App\Models\PaymentMethodRoute;
use App\Models\User;
use App\Services\ChargeService;
use App\Services\CircuitBreakerService;
use App\Services\Fraud\FraudEngineService;
use App\Services\PlatformFeeService;
use Exception;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ChargeServiceOrphanFlowTest extends TestCase
{
    private bool $transactionStarted = false;
    private ChargeServiceRedisFake $redisFake;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ensureTables();

        DB::beginTransaction();
        $this->transactionStarted = true;

        $this->redisFake = new ChargeServiceRedisFake();
        app()->instance('redis', $this->redisFake);
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
                    'platform_fee' => 1.00,
                    'gateway_fee' => 0.00,
                    'total_fee' => 1.00,
                    'net_amount' => $amount - 1.00,
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
    }

    protected function tearDown(): void
    {
        if ($this->transactionStarted) {
            DB::rollBack();
        }

        parent::tearDown();
    }

    public function test_successful_mock_gateway_charge_is_persisted_waiting_payment(): void
    {
        [$user, $gateway] = $this->createChargeFixture('mock');
        $idempotencyKey = 'hi02-success-' . uniqid();

        $charge = app(ChargeService::class)->create($user, 100.00, 'pix', [
            'idempotency_key' => $idempotencyKey,
            'name' => 'HI-02 Customer',
            'email' => 'hi02@example.com',
        ]);

        $fresh = Charge::where('idempotency_key', $idempotencyKey)->firstOrFail();

        $this->assertSame($charge->id, $fresh->id);
        $this->assertSame('waiting_payment', $fresh->status->value);
        $this->assertSame($gateway->id, $fresh->gateway_id);
        $this->assertNotNull($fresh->gateway_charge_id);
        $this->assertNotNull($fresh->payment_link);
    }

    public function test_unknown_gateway_adapter_failure_does_not_leave_pending_orphan_charge(): void
    {
        [$user] = $this->createChargeFixture('adapter_not_implemented_hi02');
        $idempotencyKey = 'hi02-unknown-' . uniqid();

        try {
            app(ChargeService::class)->create($user, 100.00, 'pix', [
                'idempotency_key' => $idempotencyKey,
            ]);
            $this->fail('Expected unknown adapter failure.');
        } catch (Exception $exception) {
            $this->assertStringContainsString('Todos os adquirentes falharam', $exception->getMessage());
        }

        $this->assertSame(0, Charge::where('idempotency_key', $idempotencyKey)->count());
    }

    public function test_redis_funnel_throwable_with_gateway_failure_does_not_leave_pending_orphan_charge(): void
    {
        [$user] = $this->createChargeFixture('adapter_not_implemented_hi02');
        $this->redisFake->failFunnel = true;
        $idempotencyKey = 'hi02-redis-' . uniqid();

        try {
            app(ChargeService::class)->create($user, 100.00, 'pix', [
                'idempotency_key' => $idempotencyKey,
            ]);
            $this->fail('Expected gateway failure after redis funnel fallback.');
        } catch (Exception $exception) {
            $this->assertStringContainsString('Todos os adquirentes falharam', $exception->getMessage());
        }

        $this->assertSame(0, Charge::where('idempotency_key', $idempotencyKey)->count());
    }

    public function test_no_available_gateway_does_not_leave_pending_orphan_charge(): void
    {
        PaymentGateway::query()->update(['status' => 0]);

        $user = $this->createUserWithWallet();
        $idempotencyKey = 'hi02-no-gateway-' . uniqid();

        try {
            app(ChargeService::class)->create($user, 100.00, 'pix', [
                'idempotency_key' => $idempotencyKey,
            ]);
            $this->fail('Expected no gateway failure.');
        } catch (Exception $exception) {
            $this->assertStringContainsString('Nenhum adquirente', $exception->getMessage());
        }

        $this->assertSame(0, Charge::where('idempotency_key', $idempotencyKey)->count());
    }

    private function createChargeFixture(string $gatewayCode): array
    {
        $user = $this->createUserWithWallet();
        PaymentMethodRoute::where('payment_operation', PaymentOperation::PIX_CHARGE->value)
            ->orWhere('payment_method', 'pix')
            ->delete();

        $gatewayAttributes = [
            'provider' => $gatewayCode,
            'adapter' => $gatewayCode,
            'logo' => '',
            'name' => 'HI-02 ' . $gatewayCode,
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
        ];

        if ($gatewayCode === 'mock') {
            $gateway = PaymentGateway::updateOrCreate(['code' => 'mock'], $gatewayAttributes);
        } else {
            $gateway = PaymentGateway::create($gatewayAttributes + ['code' => $gatewayCode . '_' . uniqid()]);
        }

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

    private function createUserWithWallet(): User
    {
        DB::table('currencies')->update(['default' => 0]);

        $currencyId = DB::table('currencies')->insertGetId([
            'flag' => 'br',
            'name' => 'Brazilian Real Test',
            'code' => 'BRL',
            'symbol' => 'R$',
            'type' => 'fiat',
            'exchange_rate' => 1,
            'rate_live' => 0,
            'auto_wallet' => 1,
            'default' => 1,
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $userId = DB::table('users')->insertGetId([
            'firstname' => 'HI02',
            'lastname' => 'Tester',
            'username' => 'hi02_' . uniqid(),
            'email' => uniqid('hi02_') . '@example.com',
            'password' => bcrypt('password'),
            'kyc_status' => KycStatus::APPROVED->value,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('wallets')->insert([
            'user_id' => $userId,
            'currency_id' => $currencyId,
            'uuid' => 'hi02-wallet-' . uniqid(),
            'balance' => 0,
            'available_balance' => 0,
            'pending_balance' => 0,
            'withdrawn_balance' => 0,
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return User::findOrFail($userId);
    }

    private function ensureTables(): void
    {
        if (!Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('firstname')->nullable();
                $table->string('lastname')->nullable();
                $table->string('username')->nullable();
                $table->string('email')->nullable();
                $table->string('password')->nullable();
                $table->unsignedTinyInteger('kyc_status')->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasColumn('users', 'kyc_status')) {
            Schema::table('users', function (Blueprint $table) {
                $table->unsignedTinyInteger('kyc_status')->default(0);
            });
        }

        if (!Schema::hasTable('currencies')) {
            Schema::create('currencies', function (Blueprint $table) {
                $table->id();
                $table->string('flag')->nullable();
                $table->string('name');
                $table->string('code');
                $table->string('symbol')->nullable();
                $table->string('type')->nullable();
                $table->decimal('exchange_rate', 18, 8)->default(1);
                $table->boolean('rate_live')->default(false);
                $table->boolean('auto_wallet')->default(false);
                $table->boolean('default')->default(false);
                $table->boolean('status')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('wallets')) {
            Schema::create('wallets', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('currency_id')->nullable();
                $table->unsignedBigInteger('user_id');
                $table->string('uuid')->unique();
                $table->decimal('balance', 15, 2)->default(0);
                $table->decimal('available_balance', 15, 2)->default(0);
                $table->decimal('pending_balance', 15, 2)->default(0);
                $table->decimal('withdrawn_balance', 15, 2)->default(0);
                $table->boolean('status')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('payment_gateways')) {
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

        if (!Schema::hasTable('payment_method_routes')) {
            Schema::create('payment_method_routes', function (Blueprint $table) {
                $table->id();
                $table->string('payment_method')->nullable();
                $table->string('payment_operation')->nullable();
                $table->unsignedBigInteger('primary_gateway_id')->nullable();
                $table->json('fallback_gateway_ids')->nullable();
                $table->string('routing_strategy')->nullable();
                $table->json('gateway_weights')->nullable();
                $table->boolean('enabled')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('api_charges')) {
            Schema::create('api_charges', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid');
                $table->uuid('correlation_id')->nullable();
                $table->string('idempotency_key')->nullable();
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('wallet_id')->nullable();
                $table->unsignedBigInteger('currency_id')->nullable();
                $table->unsignedBigInteger('gateway_id')->nullable();
                $table->string('gateway_charge_id')->nullable();
                $table->string('payment_method');
                $table->decimal('amount', 15, 2);
                $table->decimal('platform_fee', 15, 2)->default(0);
                $table->decimal('gateway_fee', 15, 2)->default(0);
                $table->unsignedBigInteger('fee_rule_id')->nullable();
                $table->json('fee_snapshot')->nullable();
                $table->decimal('net_amount', 15, 2)->default(0);
                $table->string('description')->nullable();
                $table->string('customer_name')->nullable();
                $table->string('customer_email')->nullable();
                $table->string('customer_document')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->string('payment_link')->nullable();
                $table->text('qr_code')->nullable();
                $table->text('pix_copy_paste')->nullable();
                $table->json('metadata')->nullable();
                $table->string('status')->default('pending');
                $table->softDeletes();
                $table->timestamps();
            });
        } else {
            Schema::table('api_charges', function (Blueprint $table) {
                if (!Schema::hasColumn('api_charges', 'fee_rule_id')) {
                    $table->unsignedBigInteger('fee_rule_id')->nullable();
                }

                if (!Schema::hasColumn('api_charges', 'fee_snapshot')) {
                    $table->json('fee_snapshot')->nullable();
                }
            });
        }

        if (!Schema::hasTable('platform_fee_rules')) {
            Schema::create('platform_fee_rules', function (Blueprint $table) {
                $table->id();
                $table->string('scope', 20)->default('global');
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('payment_method', 30);
                $table->string('currency', 3)->default('BRL');
                $table->decimal('fixed_fee', 28, 8)->default(0);
                $table->decimal('percentage_fee', 8, 4)->default(0);
                $table->decimal('minimum_fee', 28, 8)->nullable();
                $table->decimal('maximum_fee', 28, 8)->nullable();
                $table->unsignedInteger('settlement_delay_days')->default(0);
                $table->decimal('reserve_percentage', 8, 4)->default(0);
                $table->string('status', 20)->default('active');
                $table->timestamp('starts_at')->nullable();
                $table->timestamp('ends_at')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('gateway_logs')) {
            Schema::create('gateway_logs', function (Blueprint $table) {
                $table->id();
                $table->string('gateway_code');
                $table->json('request_payload')->nullable();
                $table->json('response_payload')->nullable();
                $table->unsignedSmallInteger('http_status')->nullable();
                $table->unsignedInteger('execution_time_ms')->nullable();
                $table->string('correlation_id')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('payment_links')) {
            Schema::create('payment_links', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->string('slug', 64)->unique();
                $table->unsignedBigInteger('user_id');
                $table->string('type', 30);
                $table->unsignedBigInteger('charge_id')->nullable();
                $table->unsignedBigInteger('customer_subscription_id')->nullable();
                $table->unsignedBigInteger('customer_id')->nullable();
                $table->decimal('amount', 18, 2);
                $table->string('currency', 3)->default('BRL');
                $table->string('payment_method', 30);
                $table->json('allowed_payment_methods')->nullable();
                $table->string('title');
                $table->string('description')->nullable();
                $table->string('status', 20)->default('pending');
                $table->timestamp('expires_at')->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->timestamp('canceled_at')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'type', 'status'], 'payment_links_user_type_status_idx');
                $table->index(['user_id', 'status', 'expires_at'], 'payment_links_user_status_exp_idx');
                $table->index(['status', 'expires_at'], 'payment_links_status_exp_idx');
                $table->index(['charge_id', 'status'], 'payment_links_charge_status_idx');
                $table->index(['customer_subscription_id', 'status'], 'payment_links_subscription_status_idx');
            });
        }
    }
}

class ChargeServiceRedisFake
{
    public bool $failFunnel = false;

    public function connection(): self
    {
        return $this;
    }

    public function funnel(string $key): ChargeServiceRedisLimiterFake
    {
        if ($this->failFunnel) {
            throw new \Error('Redis funnel unavailable');
        }

        return new ChargeServiceRedisLimiterFake();
    }

    public function exists(string $key): bool
    {
        return false;
    }

    public function zrange(string $key, int $start, int $stop): array
    {
        return [];
    }

    public function zadd(string $key, int $score, string $member): int
    {
        return 1;
    }

    public function zremrangebyscore(string $key, string|int $min, string|int $max): int
    {
        return 0;
    }

    public function zcount(string $key, string|int $min, string|int $max): int
    {
        return 0;
    }

    public function get(string $key): int
    {
        return 0;
    }

    public function incr(string $key): int
    {
        return 1;
    }

    public function expire(string $key, int $seconds): bool
    {
        return true;
    }

    public function setex(string $key, int $seconds, string $value): bool
    {
        return true;
    }

    public function del(string $key): int
    {
        return 1;
    }
}

class ChargeServiceRedisLimiterFake
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
