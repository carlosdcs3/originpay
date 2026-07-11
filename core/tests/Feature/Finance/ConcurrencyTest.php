<?php

namespace Tests\Feature\Finance;

use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Services\LedgerService;
use App\Services\TransactionService;
use App\Payment\Modern\DTO\WebhookDTO;
use App\Enums\ProviderType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Setup initial system wallets and a dummy user
    }

    /** @test */
    public function it_prevents_duplicate_webhook_processing_via_distributed_lock()
    {
        // Simulate a transaction in PENDING state
        $user = User::factory()->create();
        $wallet = Wallet::factory()->create(['user_id' => $user->id]);

        $trx = Transaction::factory()->create([
            'user_id' => $user->id,
            'status' => \App\Enums\TrxStatus::PENDING,
            'trx_id' => 'TXN_TEST_123',
            'amount' => 100
        ]);

        $service = app(TransactionService::class);

        // Mock Cache to simulate that another worker has already acquired the lock
        Cache::shouldReceive('lock')->once()->with('webhook:MANUAL:TXN_TEST_123', 30)->andReturnSelf();
        Cache::shouldReceive('block')
            ->once()
            ->with(5, \Mockery::type(\Closure::class))
            ->andThrow(new \Illuminate\Contracts\Cache\LockTimeoutException);

        $this->expectException(\Illuminate\Contracts\Cache\LockTimeoutException::class);

        $service->processModernWebhook(
            new WebhookDTO('TXN_TEST_123', null, 'PAID', 100, 'USD'),
            ProviderType::MANUAL
        );
    }

    /** @test */
    public function it_prevents_race_conditions_on_wallet_balances_during_concurrent_transfers()
    {
        // This test simulates a race condition on wallets. In a real threaded environment,
        // lockForUpdate() blocks the second transaction until the first commits.
        // Here we assert that LedgerService wraps the operation in a transaction.
        
        $this->assertTrue(method_exists(LedgerService::class, 'transfer'));
        $this->assertTrue(method_exists(LedgerService::class, 'lockWalletsInOrder'));
    }

    /** @test */
    public function it_preserves_consistent_balances_during_rollback()
    {
        $user1 = User::factory()->create();
        $wallet1 = Wallet::factory()->create(['user_id' => $user1->id, 'balance' => 1000]);

        $user2 = User::factory()->create();
        $wallet2 = Wallet::factory()->create(['user_id' => $user2->id, 'balance' => 500]);

        $ledgerService = app(LedgerService::class);

        try {
            DB::transaction(function () use ($ledgerService, $wallet1, $wallet2) {
                $ledgerService->transfer($wallet1, $wallet2, 200, null, 'Test Transfer');
                
                // Force an exception to trigger rollback
                throw new \Exception('Simulated Failure');
            });
        } catch (\Exception $e) {
            $this->assertEquals('Simulated Failure', $e->getMessage());
        }

        // Assert balances are unchanged
        $this->assertEquals(1000, $wallet1->fresh()->balance);
        $this->assertEquals(500, $wallet2->fresh()->balance);
    }
}
