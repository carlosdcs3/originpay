<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\WithdrawalRequest;
use App\Models\WithdrawalApprovalRule;
use App\Models\Transaction;
use App\Models\AccountRestriction;
use App\Models\BlacklistedPixKey;
use App\Models\Wallet;
use App\Models\Currency;
use App\Models\PixKey;
use App\Services\Compliance\WithdrawalGovernanceService;
use App\Services\Compliance\BehavioralRiskService;
use App\Services\Compliance\SharedPixKeyRiskService;
use App\Services\Compliance\WithdrawalExposureService;
use App\Services\Compliance\AccountRestrictionService;
use App\Enums\MethodType;
use App\Enums\TrxType;
use App\Enums\TrxStatus;
use App\Enums\UserRole;
use App\Models\FinancialAnomaly;
use Illuminate\Support\Str;

class ComplianceGovernanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    private function createWalletFor(User $user): Wallet
    {
        $currency = Currency::create([
            'name' => 'Brazilian Real',
            'code' => 'BRL',
            'symbol' => 'R$',
            'type' => 'fiat',
            'default' => 1,
            'status' => 1,
        ]);

        return Wallet::create([
            'currency_id' => $currency->id,
            'user_id' => $user->id,
            'uuid' => (string) Str::uuid(),
            'balance' => 0,
            'status' => true,
        ]);
    }

    private function createWithdrawalRequest(array $overrides = []): WithdrawalRequest
    {
        $user = $overrides['user'] ?? User::factory()->create();
        $wallet = $overrides['wallet'] ?? $this->createWalletFor($user);

        unset($overrides['user'], $overrides['wallet']);

        return WithdrawalRequest::create(array_merge([
            'user_id' => $user->id,
            'wallet_id' => $wallet->id,
            'amount' => 10000,
            'fee_amount' => 0,
            'net_amount' => 10000,
            'pix_key_snapshot' => 'key',
            'pix_key_type' => 'cpf',
            'status' => 'PENDING',
        ], $overrides));
    }

    public function test_shared_pix_key_forces_manual_review()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        PixKey::create([
            'user_id' => $user1->id,
            'key_type' => 'email',
            'pix_key' => 'same-key@pix.com',
            'verified' => true,
        ]);
        PixKey::create([
            'user_id' => $user2->id,
            'key_type' => 'email',
            'pix_key' => 'same-key@pix.com',
            'verified' => true,
        ]);

        $service = app(SharedPixKeyRiskService::class);
        $risk = $service->detectSharedKey('same-key@pix.com');

        $this->assertTrue($risk);
    }

    public function test_shared_pix_key_generates_anomaly()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        PixKey::create([
            'user_id' => $user1->id,
            'key_type' => 'email',
            'pix_key' => 'same-key@pix.com',
            'verified' => true,
        ]);
        PixKey::create([
            'user_id' => $user2->id,
            'key_type' => 'email',
            'pix_key' => 'same-key@pix.com',
            'verified' => true,
        ]);

        $service = app(SharedPixKeyRiskService::class);
        $service->detectSharedKey('same-key@pix.com');

        $this->assertDatabaseHas('financial_anomalies', [
            'type' => 'shared_pix_key_detected'
        ]);
    }

    public function test_dual_approval_requires_different_admins()
    {
        $admin1 = User::factory()->create(['role' => UserRole::FINANCE_ADMIN]);
        $request = $this->createWithdrawalRequest();

        $service = app(WithdrawalGovernanceService::class);
        
        $level = $service->approveLevel($request, $admin1);
        $this->assertEquals(1, $level);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Admin cannot approve the same request twice.');
        
        // Same admin tries again
        $service->approveLevel($request, $admin1);
    }

    public function test_second_approval_requires_financial_role()
    {
        $supportAdmin = User::factory()->create(['role' => UserRole::SUPPORT]);
        $request = $this->createWithdrawalRequest();

        $service = app(WithdrawalGovernanceService::class);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Admin does not have the required role to approve withdrawals.');
        
        $service->approveLevel($request, $supportAdmin);
    }

    public function test_behavioral_risk_uses_financial_context()
    {
        $user = User::factory()->create(['created_at' => now()->subDays(1)]);

        // Deposited 10,000
        Transaction::create([
            'user_id' => $user->id,
            'amount' => 10000,
            'trx_type' => TrxType::DEPOSIT,
            'processing_type' => MethodType::SYSTEM,
            'status' => TrxStatus::COMPLETED,
            'currency' => 'BRL',
            'trx_id' => 'test'
        ]);

        $service = app(BehavioralRiskService::class);
        // Tries to withdraw 9500 (95% of deposit)
        $risk = $service->evaluate($user, 9500);

        $this->assertEquals('CRITICAL', $risk);
    }

    public function test_large_first_withdraw_is_high_risk()
    {
        $user = User::factory()->create(['created_at' => now()->subDays(10)]);

        $service = app(BehavioralRiskService::class);
        // First withdraw of 2000
        $risk = $service->evaluate($user, 2000);

        $this->assertEquals('HIGH', $risk);
        $this->assertDatabaseHas('financial_anomalies', [
            'type' => 'first_withdraw_high_risk'
        ]);
    }

    public function test_withdrawal_exposure_dashboard_metrics()
    {
        $this->createWithdrawalRequest([
            'amount' => 500,
            'net_amount' => 500,
            'status' => 'APPROVED',
            'approved_at' => now()
        ]);

        $service = app(WithdrawalExposureService::class);
        $metrics = $service->getDailyMetrics();

        $this->assertEquals(500, $metrics['requested_today']);
        $this->assertEquals(500, $metrics['approved_today']);
    }

    public function test_dual_approval_bypass_generates_anomaly()
    {
        $admin1 = User::factory()->create(['role' => UserRole::FINANCE_ADMIN]);
        $request = $this->createWithdrawalRequest();

        $service = app(WithdrawalGovernanceService::class);
        $service->approveLevel($request, $admin1);

        try {
            $service->approveLevel($request, $admin1);
        } catch (\Exception $e) {}

        $this->assertDatabaseHas('financial_anomalies', [
            'type' => 'dual_approval_bypass_attempt'
        ]);
    }

    public function test_frozen_account_cannot_withdraw()
    {
        $user = User::factory()->create();
        
        AccountRestriction::create([
            'user_id' => $user->id,
            'restriction_type' => 'FULL_FREEZE',
            'reason' => 'Test freeze'
        ]);

        $service = app(AccountRestrictionService::class);
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Action blocked due to account restriction: Test freeze');

        $service->checkRestrictionOrThrow($user, 'WITHDRAW_BLOCK');
    }
}
