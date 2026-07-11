<?php

namespace Tests\Feature;

use App\Models\WalletTransaction;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LedgerIntegrityHardeningTest extends TestCase
{
    private bool $transactionStarted = false;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ensureTables();

        DB::beginTransaction();
        $this->transactionStarted = true;

        DB::table('wallet_transactions')->delete();
        DB::table('wallets')->delete();
        DB::table('users')->delete();
    }

    protected function tearDown(): void
    {
        if ($this->transactionStarted) {
            DB::rollBack();
        }

        parent::tearDown();
    }

    public function test_WalletTransaction_generates_integrity_hash_and_links_previous_hash(): void
    {
        [$userId, $walletId] = $this->createUserAndWallet();

        $first = WalletTransaction::create([
            'user_id' => $userId,
            'wallet_id' => $walletId,
            'type' => 'adjustment',
            'amount' => 100,
            'balance_before' => 0,
            'balance_after' => 100,
            'description' => 'first integrity transaction',
        ]);

        $second = WalletTransaction::create([
            'user_id' => $userId,
            'wallet_id' => $walletId,
            'type' => 'fee',
            'amount' => -10,
            'balance_before' => 100,
            'balance_after' => 90,
            'description' => 'second integrity transaction',
        ]);

        $this->assertNull($first->previous_integrity_hash);
        $this->assertNotNull($first->integrity_hash);
        $this->assertSame($first->integrity_hash, $second->previous_integrity_hash);
        $this->assertNotNull($second->integrity_hash);
    }

    public function test_LedgerIntegrity_classifies_missing_hash_without_hash_mismatch(): void
    {
        [$userId, $walletId] = $this->createUserAndWallet();

        $this->insertLegacyTransaction($userId, $walletId);

        $exitCode = Artisan::call('ledger:verify-integrity');
        $output = Artisan::output();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('missing_hash', $output);
        $this->assertStringContainsString('Registro sem baseline de integridade detectado', $output);
        $this->assertStringContainsString('| 1         | 1            | 0             | 0', $output);
        $this->assertStringNotContainsString('dados alterados silenciosamente', $output);
    }

    public function test_LedgerIntegrity_classifies_tampered_hash_as_hash_mismatch(): void
    {
        [$userId, $walletId] = $this->createUserAndWallet();

        $transaction = WalletTransaction::create([
            'user_id' => $userId,
            'wallet_id' => $walletId,
            'type' => 'adjustment',
            'amount' => 100,
            'balance_before' => 0,
            'balance_after' => 100,
            'description' => 'tampered transaction',
        ]);

        DB::table('wallet_transactions')
            ->where('id', $transaction->id)
            ->update(['integrity_hash' => str_repeat('a', 64)]);

        $exitCode = Artisan::call('ledger:verify-integrity');
        $output = Artisan::output();

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('hash_mismatch', $output);
        $this->assertStringContainsString('Hash invalido. Possivel alteracao de dados.', $output);
    }

    public function test_LedgerIntegrity_accepts_valid_transactions_without_error(): void
    {
        [$userId, $walletId] = $this->createUserAndWallet();

        WalletTransaction::create([
            'user_id' => $userId,
            'wallet_id' => $walletId,
            'type' => 'adjustment',
            'amount' => 100,
            'balance_before' => 0,
            'balance_after' => 100,
            'description' => 'valid transaction',
        ]);

        $exitCode = Artisan::call('ledger:verify-integrity');
        $output = Artisan::output();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('| 1         | 0            | 0             | 0', $output);
        $this->assertStringContainsString('Nenhuma violacao encontrada', $output);
    }

    private function createUserAndWallet(): array
    {
        $userId = DB::table('users')->insertGetId([
            'first_name' => 'Ledger',
            'last_name' => 'Integrity',
            'username' => 'ledger_integrity_' . uniqid(),
            'email' => uniqid('ledger_integrity_') . '@example.com',
            'password' => bcrypt('password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $walletId = DB::table('wallets')->insertGetId([
            'user_id' => $userId,
            'uuid' => 'ledger-wallet-' . uniqid(),
            'balance' => 0,
            'available_balance' => 0,
            'pending_balance' => 0,
            'status' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$userId, $walletId];
    }

    private function insertLegacyTransaction(int $userId, int $walletId): void
    {
        DB::table('wallet_transactions')->insert([
            'user_id' => $userId,
            'wallet_id' => $walletId,
            'type' => 'adjustment',
            'amount' => 100,
            'balance_before' => 0,
            'balance_after' => 100,
            'description' => 'legacy pre-integrity transaction',
            'previous_integrity_hash' => null,
            'integrity_hash' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function ensureTables(): void
    {
        if (!Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('first_name')->nullable();
                $table->string('last_name')->nullable();
                $table->string('username')->nullable();
                $table->string('email')->nullable();
                $table->string('password')->nullable();
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
                $table->boolean('status')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('wallet_transactions')) {
            Schema::create('wallet_transactions', function (Blueprint $table) {
                $table->id();
                $table->string('correlation_id')->nullable();
                $table->string('idempotency_key')->nullable();
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('wallet_id');
                $table->string('type');
                $table->decimal('amount', 28, 8);
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
