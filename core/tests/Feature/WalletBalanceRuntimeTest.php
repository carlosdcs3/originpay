<?php

namespace Tests\Feature;

use App\Models\PaymentGateway;
use App\Models\Wallet;
use App\Models\WalletBalance;
use App\Models\WalletTransaction;
use App\Services\Financial\WalletBalanceService;
use App\Services\Security\TenantBypass;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class WalletBalanceRuntimeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->ensureTables();
    }

    public function test_wallet_balances_table_exists_after_migration_contract(): void
    {
        $this->assertTrue(Schema::hasTable('wallet_balances'));
        $this->assertSame(
            ['id', 'wallet_id', 'gateway_id', 'available', 'pending', 'blocked', 'created_at', 'updated_at'],
            Schema::getColumnListing('wallet_balances')
        );
    }

    public function test_wallet_balance_query_by_gateway_works_without_sqlstate(): void
    {
        [$wallet, $gateway] = $this->createWalletAndGateway();

        WalletBalance::create([
            'wallet_id' => $wallet->id,
            'gateway_id' => $gateway->id,
            'available' => 25,
            'pending' => 0,
            'blocked' => 0,
        ]);

        $balance = WalletBalance::where('wallet_id', $wallet->id)
            ->where('available', '>=', 1)
            ->with('gateway')
            ->first();

        $this->assertNotNull($balance);
        $this->assertSame($gateway->id, $balance->gateway->id);
    }

    public function test_wallet_debit_gateway_fails_controlled_when_gateway_balance_is_insufficient(): void
    {
        [$wallet, $gateway] = $this->createWalletAndGateway(balance: 100);

        WalletBalance::create([
            'wallet_id' => $wallet->id,
            'gateway_id' => $gateway->id,
            'available' => 5,
            'pending' => 0,
            'blocked' => 0,
        ]);

        $this->expectException(\Exception::class);

        TenantBypass::run(fn () => $wallet->debitGateway($gateway->id, 10));
    }

    public function test_wallet_debit_gateway_reduces_available_when_balance_is_sufficient(): void
    {
        [$wallet, $gateway] = $this->createWalletAndGateway(balance: 100);

        WalletBalance::create([
            'wallet_id' => $wallet->id,
            'gateway_id' => $gateway->id,
            'available' => 40,
            'pending' => 0,
            'blocked' => 0,
        ]);

        $this->assertTrue(TenantBypass::run(fn () => $wallet->debitGateway($gateway->id, 15)));

        $this->assertSame(25.0, (float) WalletBalance::first()->available);
        $this->assertSame(85.0, (float) $wallet->fresh()->balance);
    }

    public function test_wallet_balance_service_uses_available_pending_blocked_schema(): void
    {
        [$wallet, $gateway] = $this->createWalletAndGateway(balance: 0);
        $service = app(WalletBalanceService::class);

        TenantBypass::run(fn () => $service->creditGateway($wallet->id, $gateway->id, 100, ['idempotency_key' => 'credit-1']));
        $this->assertSame(100.0, (float) WalletBalance::first()->available);
        $this->assertSame(100.0, (float) $wallet->fresh()->available_balance);
        $this->assertSame(1, WalletTransaction::count());

        TenantBypass::run(fn () => $service->debitGateway($wallet->id, $gateway->id, 20, ['idempotency_key' => 'debit-1']));
        $this->assertSame(80.0, (float) WalletBalance::first()->available);
        $this->assertSame(80.0, (float) $wallet->fresh()->available_balance);
        $this->assertSame(2, WalletTransaction::count());

        TenantBypass::run(fn () => $service->blockFunds($wallet->id, $gateway->id, 30, ['idempotency_key' => 'block-1']));
        $this->assertSame(50.0, (float) WalletBalance::first()->available);
        $this->assertSame(30.0, (float) WalletBalance::first()->blocked);
        $this->assertSame(3, WalletTransaction::count());

        TenantBypass::run(fn () => $service->releaseFunds($wallet->id, $gateway->id, 10, ['idempotency_key' => 'release-1']));
        $this->assertSame(60.0, (float) WalletBalance::first()->available);
        $this->assertSame(20.0, (float) WalletBalance::first()->blocked);
        $this->assertSame(4, WalletTransaction::count());
    }

    public function test_wallet_balance_service_is_idempotent_for_replayed_financial_event(): void
    {
        [$wallet, $gateway] = $this->createWalletAndGateway(balance: 0);
        $service = app(WalletBalanceService::class);

        $first = TenantBypass::run(fn () => $service->creditGateway($wallet->id, $gateway->id, 100, ['idempotency_key' => 'charge-paid-1']));
        $second = TenantBypass::run(fn () => $service->creditGateway($wallet->id, $gateway->id, 100, ['idempotency_key' => 'charge-paid-1']));

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, WalletTransaction::count());
        $this->assertSame(100.0, (float) WalletBalance::first()->available);
        $this->assertSame(100.0, (float) $wallet->fresh()->available_balance);
        $this->assertSame(100.0, (float) $wallet->fresh()->balance);
    }

    public function test_wallet_balance_service_rejects_negative_or_zero_amounts(): void
    {
        [$wallet, $gateway] = $this->createWalletAndGateway(balance: 0);
        $service = app(WalletBalanceService::class);

        $this->expectException(\InvalidArgumentException::class);

        TenantBypass::run(fn () => $service->creditGateway($wallet->id, $gateway->id, 0, ['idempotency_key' => 'zero']));
    }

    private function createWalletAndGateway(float $balance = 0): array
    {
        $userId = DB::table('users')->insertGetId([
            'firstname' => 'Wallet',
            'lastname' => 'Tester',
            'username' => 'wallet_tester_' . uniqid(),
            'email' => uniqid('wallet_') . '@example.com',
            'password' => bcrypt('password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $wallet = Wallet::create([
            'user_id' => $userId,
            'uuid' => 'wallet-' . uniqid(),
            'balance' => $balance,
            'available_balance' => $balance,
            'pending_balance' => 0,
            'withdrawn_balance' => 0,
            'status' => true,
        ]);

        $gateway = PaymentGateway::create([
            'provider' => 'test',
            'adapter' => 'test',
            'logo' => '',
            'name' => 'Wallet Balance Test Gateway',
            'code' => 'wallet_balance_test_' . uniqid(),
            'currencies' => ['BRL'],
            'credentials' => [],
            'is_withdraw' => 1,
            'status' => 1,
            'is_maintenance' => 0,
            'priority' => 1,
            'is_sandbox' => 1,
            'supports_pix' => 1,
            'supports_card' => 0,
            'supports_boleto' => 0,
            'supports_crypto' => 0,
            'supports_refund' => 0,
            'supports_withdrawal' => 1,
        ]);

        return [$wallet, $gateway];
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
                $table->string('referral_code')->nullable();
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

        if (!Schema::hasTable('wallet_balances')) {
            Schema::create('wallet_balances', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('wallet_id');
                $table->unsignedBigInteger('gateway_id');
                $table->decimal('available', 15, 2)->default(0);
                $table->decimal('pending', 15, 2)->default(0);
                $table->decimal('blocked', 15, 2)->default(0);
                $table->timestamps();
                $table->unique(['wallet_id', 'gateway_id']);
            });
        }

        if (!Schema::hasTable('wallet_transactions')) {
            Schema::create('wallet_transactions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('wallet_id');
                $table->string('type');
                $table->decimal('amount', 28, 8);
                $table->string('correlation_id')->nullable();
                $table->string('idempotency_key')->nullable();
                $table->decimal('balance_before', 28, 8)->default(0);
                $table->decimal('balance_after', 28, 8);
                $table->string('description')->nullable();
                $table->string('reference_type')->nullable();
                $table->unsignedBigInteger('reference_id')->nullable();
                $table->string('previous_integrity_hash')->nullable();
                $table->string('integrity_hash')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }
    }
}
