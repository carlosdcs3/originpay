<?php

namespace Tests\Feature;

use App\Models\PaymentGateway;
use App\Models\Settlement;
use App\Models\Wallet;
use App\Models\WalletBalance;
use App\Models\WalletTransaction;
use App\Services\Security\TenantBypass;
use App\Services\SettlementActionService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SettlementReconciliationRuntimeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->ensureTables();
    }

    public function test_force_settle_is_idempotent_and_debits_once(): void
    {
        [$wallet, $gateway, $userId] = $this->createWalletAndGateway(balance: 100);

        WalletBalance::create([
            'wallet_id' => $wallet->id,
            'gateway_id' => $gateway->id,
            'available' => 100,
            'pending' => 0,
            'blocked' => 0,
        ]);

        $settlement = Settlement::create([
            'user_id' => $userId,
            'gateway_id' => $gateway->id,
            'destination' => 'pix:test@example.com',
            'gross_amount' => 25,
            'fees' => 5,
            'net_amount' => 20,
            'status' => 'pending',
            'metadata' => [],
        ]);

        $service = app(SettlementActionService::class);

        TenantBypass::run(fn () => $service->forceSettle($settlement, 999));
        TenantBypass::run(fn () => $service->forceSettle($settlement->fresh(), 999));

        $this->assertSame('settled', $settlement->fresh()->status);
        $this->assertSame(1, WalletTransaction::where('reference_type', Settlement::class)->where('reference_id', $settlement->id)->count());
        $this->assertSame(80.0, (float) $wallet->fresh()->balance);
        $this->assertSame(80.0, (float) $wallet->fresh()->available_balance);
        $this->assertSame(80.0, (float) WalletBalance::where('wallet_id', $wallet->id)->where('gateway_id', $gateway->id)->first()->available);
    }

    public function test_force_settle_rejects_insufficient_available_balance_without_partial_status_change(): void
    {
        [$wallet, $gateway, $userId] = $this->createWalletAndGateway(balance: 10);

        WalletBalance::create([
            'wallet_id' => $wallet->id,
            'gateway_id' => $gateway->id,
            'available' => 10,
            'pending' => 0,
            'blocked' => 0,
        ]);

        $settlement = Settlement::create([
            'user_id' => $userId,
            'gateway_id' => $gateway->id,
            'destination' => 'pix:test@example.com',
            'gross_amount' => 25,
            'fees' => 5,
            'net_amount' => 20,
            'status' => 'pending',
            'metadata' => [],
        ]);

        try {
            TenantBypass::run(fn () => app(SettlementActionService::class)->forceSettle($settlement, 999));
            $this->fail('Expected settlement with insufficient balance to throw.');
        } catch (\Exception $exception) {
            $this->assertNotSame('', $exception->getMessage());
        }

        $this->assertSame('pending', $settlement->fresh()->status);
        $this->assertSame(0, WalletTransaction::where('reference_type', Settlement::class)->where('reference_id', $settlement->id)->count());
    }

    private function createWalletAndGateway(float $balance = 0): array
    {
        $userId = DB::table('users')->insertGetId([
            'firstname' => 'Settlement',
            'lastname' => 'Tester',
            'username' => 'settlement_tester_' . uniqid(),
            'email' => uniqid('settlement_') . '@example.com',
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
            'name' => 'Settlement Test Gateway',
            'code' => 'settlement_test_' . uniqid(),
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

        return [$wallet, $gateway, $userId];
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

        if (!Schema::hasTable('settlements')) {
            Schema::create('settlements', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('gateway_id')->nullable();
                $table->string('destination');
                $table->decimal('gross_amount', 18, 8);
                $table->decimal('fees', 18, 8)->default(0);
                $table->decimal('net_amount', 18, 8);
                $table->string('status', 30)->default('pending');
                $table->timestamp('scheduled_date')->nullable();
                $table->timestamp('settled_date')->nullable();
                $table->unsignedBigInteger('split_rule_id')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }
    }
}
