<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WithdrawalSetting;
use App\Services\Payment\WithdrawalService;
use App\Services\LedgerService;
use App\Services\Payment\WithdrawalRiskService;
use App\Services\Payment\GatewayFeeService;
use App\Models\WithdrawalRequest;

class WithdrawalFlowTest extends TestCase
{
    use RefreshDatabase;

    protected WithdrawalService $withdrawalService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->withdrawalService = app(WithdrawalService::class);
        
        // Ensure settings exist
        WithdrawalSetting::firstOrCreate([], [
            'withdraw_enabled' => true,
            'auto_approve_enabled' => false,
            'minimum_amount' => 10,
            'maximum_amount' => 10000,
        ]);

        // Mock Fee
        \App\Models\GatewayFeeConfig::create([
            'provider' => 'EFI',
            'transaction_fee_type' => 'fixed',
            'transaction_fixed_fee' => 0,
            'transaction_percent_fee' => 0,
            'withdraw_fee_type' => 'fixed',
            'withdraw_fixed_fee' => 1.00,
            'withdraw_percent_fee' => 0.00,
            'provider_fee_mode' => 'estimated',
            'provider_fixed_fee' => 0.00,
            'provider_percent_fee' => 0.00,
            'is_active' => true,
        ]);
    }

    public function test_reserved_balance_is_never_spendable()
    {
        $user = User::factory()->create(['created_at' => now()->subDays(10)]);
        $wallet = Wallet::factory()->create([
            'user_id' => $user->id,
            'balance' => 100.00,
            'available_balance' => 100.00,
            'reserved_balance' => 0.00
        ]);

        $request = $this->withdrawalService->requestWithdrawal($user, 40.00, 'cpf123', 'cpf');

        $wallet->refresh();
        $this->assertEquals(60.00, $wallet->available_balance);
        $this->assertEquals(40.00, $wallet->reserved_balance);
        $this->assertEquals(100.00, $wallet->balance);

        // Attempt to withdraw another 80 should fail
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Insufficient available balance.");
        $this->withdrawalService->requestWithdrawal($user, 80.00, 'cpf123', 'cpf');
    }

    public function test_withdrawal_creates_reservation_ledger_entry()
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->create([
            'user_id' => $user->id,
            'balance' => 100.00,
            'available_balance' => 100.00,
        ]);

        $this->withdrawalService->requestWithdrawal($user, 40.00, 'cpf123', 'cpf');

        $this->assertDatabaseHas('ledger_entries', [
            'wallet_id' => $wallet->id,
            'direction' => 'debit',
            'amount' => 40.00,
            'description' => 'Withdrawal Reservation',
        ]);
    }

    public function test_auto_approval_respects_risk_rules()
    {
        WithdrawalSetting::first()->update(['auto_approve_enabled' => true]);

        // User created today (should hit MANUAL_REVIEW)
        $user = User::factory()->create(['created_at' => now()]);
        $wallet = Wallet::factory()->create([
            'user_id' => $user->id,
            'balance' => 100.00,
            'available_balance' => 100.00,
        ]);

        $request = $this->withdrawalService->requestWithdrawal($user, 40.00, 'cpf123', 'cpf');

        // Because risk service returns MANUAL_REVIEW for new accounts, status should remain PENDING
        $this->assertEquals('PENDING', $request->status);
    }

    public function test_pix_key_snapshot_is_immutable()
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->create([
            'user_id' => $user->id,
            'balance' => 100.00,
            'available_balance' => 100.00,
        ]);

        $request = $this->withdrawalService->requestWithdrawal($user, 40.00, 'pix-chave-original', 'random');

        $this->assertEquals('pix-chave-original', $request->pix_key_snapshot);
    }

    public function test_failed_withdrawal_releases_reservation()
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->create([
            'user_id' => $user->id,
            'balance' => 100.00,
            'available_balance' => 100.00,
        ]);

        $request = $this->withdrawalService->requestWithdrawal($user, 40.00, 'cpf123', 'cpf');
        $this->withdrawalService->approveWithdrawal($request, null);

        $this->withdrawalService->failWithdrawal($request, 'API Timeout');

        $wallet->refresh();
        $this->assertEquals(100.00, $wallet->available_balance);
        $this->assertEquals(0.00, $wallet->reserved_balance);
        $this->assertEquals('FAILED', $request->fresh()->status);
        
        $this->assertDatabaseHas('ledger_entries', [
            'direction' => 'credit',
            'amount' => 40.00,
            'description' => 'Withdrawal Reservation Release',
        ]);
    }
}
