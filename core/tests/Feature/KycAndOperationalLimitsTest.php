<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WithdrawalRequest;
use App\Models\KycProfile;
use App\Models\RollingReserve;
use App\Models\AccountRestriction;
use App\Models\UserFinancialLimit;
use App\Models\WithdrawalSetting;
use App\Services\LedgerService;
use App\Services\Compliance\UserExposureService;
use App\Services\Compliance\AccountRestrictionService;
use App\Jobs\Treasury\ReleaseRollingReserveJob;

class KycAndOperationalLimitsTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_cannot_exceed_kyc_level_limit()
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->create(['user_id' => $user->id, 'balance' => 0]);
        KycProfile::create(['user_id' => $user->id, 'level' => 0]); // max 500

        $ledger = app(LedgerService::class);
        $ledger->credit($wallet, 600, null, 'Test');

        $this->assertDatabaseHas('account_restrictions', [
            'user_id' => $user->id,
            'restriction_type' => 'KYC_LIMIT_LOCK'
        ]);
        
        $this->assertDatabaseHas('financial_anomalies', [
            'type' => 'kyc_required_limit_exceeded',
            'entity_id' => $user->id
        ]);
    }

    public function test_rolling_reserve_reduces_available_balance()
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->create(['user_id' => $user->id, 'balance' => 0, 'available_balance' => 0, 'rolling_reserve_balance' => 0]);
        
        $ledger = app(LedgerService::class);
        // Reserve 10%
        $ledger->credit($wallet, 100, null, 'Sale', ['rolling_reserve_percent' => 10, 'rolling_reserve_days' => 7]);

        $wallet->refresh();
        $this->assertEquals(100, $wallet->balance);
        $this->assertEquals(90, $wallet->available_balance);
        $this->assertEquals(10, $wallet->rolling_reserve_balance);
        
        $this->assertDatabaseHas('rolling_reserves', [
            'user_id' => $user->id,
            'amount' => 10,
            'status' => 'HELD'
        ]);
    }

    public function test_rolling_reserve_is_released_after_period()
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->create(['user_id' => $user->id, 'balance' => 100, 'available_balance' => 90, 'rolling_reserve_balance' => 10]);
        
        $reserve = RollingReserve::create([
            'user_id' => $user->id,
            'wallet_id' => $wallet->id,
            'amount' => 10,
            'status' => 'HELD',
            'release_at' => now()->subDay() // Expired
        ]);

        (new ReleaseRollingReserveJob())->handle();

        $wallet->refresh();
        $this->assertEquals(100, $wallet->balance);
        $this->assertEquals(100, $wallet->available_balance);
        $this->assertEquals(0, $wallet->rolling_reserve_balance);
        
        $this->assertDatabaseHas('rolling_reserves', [
            'id' => $reserve->id,
            'status' => 'RELEASED'
        ]);
    }

    public function test_large_withdraw_goes_to_batch()
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->create(['user_id' => $user->id, 'balance' => 5000, 'available_balance' => 5000]);
        KycProfile::create(['user_id' => $user->id, 'level' => 3, 'status' => 'APPROVED']);
        WithdrawalSetting::first()->update(['auto_approve_enabled' => true]);
        
        $service = app(\App\Services\Payment\WithdrawalService::class);
        $request = $service->requestWithdrawal($user, 600, 'key', 'email'); // > 500 goes to batch
        
        // Wait, if auto_approve is true, it evaluates risk. If risk is APPROVE, it calls approveWithdrawal, which checks amount <= 500. 
        // 600 is > 500, so it will be PENDING_BATCH.
        $request->refresh();
        $this->assertEquals('PENDING_BATCH', $request->status);
    }

    public function test_dual_approval_required_for_large_withdraw()
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->create(['user_id' => $user->id, 'balance' => 20000, 'available_balance' => 20000]);
        KycProfile::create(['user_id' => $user->id, 'level' => 3, 'status' => 'APPROVED']);
        WithdrawalSetting::first()->update([
            'auto_approve_enabled' => true,
            'maximum_amount' => 20000,
        ]);
        
        $service = app(\App\Services\Payment\WithdrawalService::class);
        $request = $service->requestWithdrawal($user, 11000, 'key', 'email'); // > 10000 -> MANUAL_REVIEW -> DUAL_APPROVAL Governance
        
        // The Risk Engine forces MANUAL_REVIEW for > 2000.
        $request->refresh();
        $this->assertEquals('MANUAL_REVIEW', $request->status);
        
        // If we tried to approve it via Governance, we'd need dual approval
        $admin = User::factory()->create(['role' => 'FINANCE_ADMIN']);
        $govService = app(\App\Services\Compliance\WithdrawalGovernanceService::class);
        
        // The mode for 11000 should be DUAL_APPROVAL per our WithdrawalApprovalRule
        \App\Models\WithdrawalApprovalRule::create(['min_amount' => 10000, 'approval_mode' => 'DUAL_APPROVAL', 'is_active' => true]);
        $mode = $govService->getApprovalMode(11000);
        $this->assertEquals('DUAL_APPROVAL', $mode);
    }
    
    public function test_kyc_limit_lock_allows_inbound_blocks_outbound()
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->create(['user_id' => $user->id, 'balance' => 0]);
        
        AccountRestriction::create([
            'user_id' => $user->id,
            'restriction_type' => 'KYC_LIMIT_LOCK',
            'reason' => 'Test'
        ]);

        $ledger = app(LedgerService::class);
        
        // Credit (Inbound) should work
        $ledger->credit($wallet, 100, null, 'Test Inbound');
        $this->assertEquals(100, $wallet->fresh()->balance);
        
        // Debit (Outbound) should throw exception
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Action blocked due to account restriction: Test');
        $ledger->debit($wallet, 50, null, 'Test Outbound');
    }
}
