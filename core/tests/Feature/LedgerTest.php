<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Wallet;
use App\Models\LedgerEntry;
use App\Services\LedgerService;
use App\Services\Security\TenantBypass;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Exception;

class LedgerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Ensure SYSTEM-GENERAL wallet exists
        $user = User::firstOrCreate(
            ['email' => 'system@ledger.internal'],
            ['name' => 'System Ledger', 'password' => bcrypt('password')]
        );

        TenantBypass::run(function () use ($user) {
            Wallet::updateOrCreate(
                ['uuid' => 'SYSTEM-GENERAL'],
                [
                'user_id' => $user->id,
                'currency_id' => \App\Models\Currency::factory()->create()->id,
                'balance' => 1000000,
                'available_balance' => 1000000,
                'status' => true,
                ]
            );
        });
    }

    public function test_add_money_creates_ledger_entry_and_updates_balance()
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->create(['user_id' => $user->id, 'balance' => 100]);
        
        $walletService = app(WalletService::class);
        $walletService->addMoney($wallet, 50);

        $wallet->refresh();
        $this->assertEquals(150, $wallet->balance);

        $this->assertDatabaseHas('ledger_entries', [
            'wallet_id' => $wallet->id,
            'direction' => 'credit',
            'amount' => 50,
            'balance_after' => 150
        ]);
    }

    public function test_subtract_money_creates_ledger_entry_and_updates_balance()
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->create(['user_id' => $user->id, 'balance' => 100]);
        
        $walletService = app(WalletService::class);
        $walletService->subtractMoney($wallet, 50);

        $wallet->refresh();
        $this->assertEquals(50, $wallet->balance);

        $this->assertDatabaseHas('ledger_entries', [
            'wallet_id' => $wallet->id,
            'direction' => 'debit',
            'amount' => 50,
            'balance_after' => 50
        ]);
    }

    public function test_transfer_creates_two_legs_in_ledger()
    {
        $user1 = User::factory()->create();
        $wallet1 = Wallet::factory()->create(['user_id' => $user1->id, 'balance' => 100]);

        $user2 = User::factory()->create();
        $wallet2 = Wallet::factory()->create(['user_id' => $user2->id, 'balance' => 50]);
        
        $ledgerService = app(LedgerService::class);
        $ledgerService->transfer($wallet1, $wallet2, 50);

        $this->assertEquals(50, $wallet1->refresh()->balance);
        $this->assertEquals(100, $wallet2->refresh()->balance);

        $this->assertDatabaseHas('ledger_entries', [
            'wallet_id' => $wallet1->id,
            'direction' => 'debit',
            'amount' => 50
        ]);

        $this->assertDatabaseHas('ledger_entries', [
            'wallet_id' => $wallet2->id,
            'direction' => 'credit',
            'amount' => 50
        ]);
    }

    public function test_debit_without_balance_fails()
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->create(['user_id' => $user->id, 'balance' => 10]);
        
        $ledgerService = app(LedgerService::class);
        
        $this->expectException(\Exception::class);
        $ledgerService->debit($wallet, 50);
    }

    public function test_ledger_entry_cannot_be_edited()
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->create(['user_id' => $user->id, 'balance' => 100]);
        
        $entry = LedgerEntry::create([
            'wallet_id' => $wallet->id,
            'direction' => 'credit',
            'amount' => 50,
            'currency' => 'USD',
            'balance_after' => 150,
            'created_at' => now(),
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('LedgerEntry is immutable');
        
        $entry->amount = 100;
        $entry->save();
    }

    public function test_ledger_entry_cannot_be_deleted()
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->create(['user_id' => $user->id, 'balance' => 100]);
        
        $entry = LedgerEntry::create([
            'wallet_id' => $wallet->id,
            'direction' => 'credit',
            'amount' => 50,
            'currency' => 'USD',
            'balance_after' => 150,
            'created_at' => now(),
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('LedgerEntry is immutable');
        
        $entry->delete();
    }

    public function test_2_step_deposit()
    {
        $user = User::factory()->create();
        $userWallet = Wallet::factory()->create(['user_id' => $user->id, 'balance' => 0]);
        $holdingWallet = Wallet::factory()->create(['user_id' => $user->id, 'uuid' => 'GATEWAY_PIX_HOLDING', 'balance' => 0]);
        $externalNetwork = Wallet::factory()->create(['user_id' => $user->id, 'uuid' => 'EXTERNAL_BANKING_NETWORK', 'balance' => 1000]);

        $ledgerService = app(LedgerService::class);
        
        // Step 1: External to Holding
        $ledgerService->transfer($externalNetwork, $holdingWallet, 100);
        
        // Step 2: Holding to User Wallet
        $ledgerService->transfer($holdingWallet, $userWallet, 100);

        $this->assertEquals(900, $externalNetwork->refresh()->balance);
        $this->assertEquals(0, $holdingWallet->refresh()->balance);
        $this->assertEquals(100, $userWallet->refresh()->balance);
    }

    public function test_2_step_payout()
    {
        $user = User::factory()->create();
        $userWallet = Wallet::factory()->create(['user_id' => $user->id, 'balance' => 200]);
        $payoutHolding = Wallet::factory()->create(['user_id' => $user->id, 'uuid' => 'GATEWAY_PIX_PAYOUT_HOLDING', 'balance' => 0]);
        $externalNetwork = Wallet::factory()->create(['user_id' => $user->id, 'uuid' => 'EXTERNAL_BANKING_NETWORK', 'balance' => 0]);

        $ledgerService = app(LedgerService::class);
        
        // Step 1: User to Payout Holding
        $ledgerService->transfer($userWallet, $payoutHolding, 50);
        
        // Step 2: Payout Holding to External
        $ledgerService->transfer($payoutHolding, $externalNetwork, 50);

        $this->assertEquals(150, $userWallet->refresh()->balance);
        $this->assertEquals(0, $payoutHolding->refresh()->balance);
        $this->assertEquals(50, $externalNetwork->refresh()->balance);
    }

    public function test_split_balanced()
    {
        $user = User::factory()->create();
        $payer = Wallet::factory()->create(['user_id' => $user->id, 'balance' => 100]);
        $merchantA = Wallet::factory()->create(['user_id' => $user->id, 'balance' => 0]);
        $merchantB = Wallet::factory()->create(['user_id' => $user->id, 'balance' => 0]);
        $systemRev = Wallet::factory()->create(['user_id' => $user->id, 'uuid' => 'SYSTEM_REVENUE', 'balance' => 0]);

        $ledgerService = app(LedgerService::class);
        
        $destinations = [
            ['wallet' => $merchantA, 'amount' => 40],
            ['wallet' => $merchantB, 'amount' => 50],
            ['wallet' => $systemRev, 'amount' => 10]
        ];

        $ledgerService->split($payer, 100, $destinations);

        $this->assertEquals(0, $payer->refresh()->balance);
        $this->assertEquals(40, $merchantA->refresh()->balance);
        $this->assertEquals(50, $merchantB->refresh()->balance);
        $this->assertEquals(10, $systemRev->refresh()->balance);
    }

    public function test_split_invalid_fails()
    {
        $user = User::factory()->create();
        $payer = Wallet::factory()->create(['user_id' => $user->id, 'balance' => 100]);
        $merchantA = Wallet::factory()->create(['user_id' => $user->id, 'balance' => 0]);
        $merchantB = Wallet::factory()->create(['user_id' => $user->id, 'balance' => 0]);

        $ledgerService = app(LedgerService::class);
        
        $destinations = [
            ['wallet' => $merchantA, 'amount' => 40],
            ['wallet' => $merchantB, 'amount' => 50],
        ];

        $this->expectException(\App\Exceptions\NotifyErrorException::class);
        $this->expectExceptionMessage('does not equal');
        
        $ledgerService->split($payer, 100, $destinations);
    }

    public function test_fx_with_metadata()
    {
        $user = User::factory()->create();
        $walletUSD = Wallet::factory()->create(['user_id' => $user->id, 'balance' => 100]);
        $walletBRL = Wallet::factory()->create(['user_id' => $user->id, 'balance' => 0]);
        $fxRev = Wallet::factory()->create(['user_id' => $user->id, 'uuid' => 'SYSTEM_REVENUE_FX', 'balance' => 0]);

        $ledgerService = app(LedgerService::class);
        
        $metadata = [
            'conversion_id' => 'CV_123',
            'exchange_rate_applied' => 5.0,
            'base_currency' => 'USD',
            'target_currency' => 'BRL',
            'source_amount' => 10,
            'target_amount' => 48,
            'spread_amount' => 2
        ];

        $ledgerService->exchange($walletUSD, $walletBRL, $fxRev, 10, 48, 2, $metadata);

        $this->assertEquals(90, $walletUSD->refresh()->balance);
        $this->assertEquals(48, $walletBRL->refresh()->balance);
        $this->assertEquals(2, $fxRev->refresh()->balance);
    }

    public function test_system_general_fails_in_new_flows()
    {
        $user = User::factory()->create();
        $wallet = TenantBypass::run(
            fn () => Wallet::where('uuid', 'SYSTEM-GENERAL')->firstOrFail()
        );
        
        $ledgerService = app(LedgerService::class);

        $this->expectException(\App\Exceptions\NotifyErrorException::class);
        $this->expectExceptionMessage('SYSTEM-GENERAL wallet cannot be used');
        
        $ledgerService->debit($wallet, 50, null, null, ['legacy_call' => false]);
    }
}
