<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\KycProfile;
use App\Models\FeatureFlag;
use App\Models\Wallet;
use App\Models\PixKey;
use App\Models\FraudProfile;
use App\Models\KycDocument;
use App\Services\Compliance\KycDecisionService;
use App\Services\Compliance\PixOwnershipService;
use App\Services\Compliance\SellerHealthService;
use App\Services\LedgerService;

class KycOperationsCenterTest extends TestCase
{
    use RefreshDatabase;

    public function test_kyc_score_approval()
    {
        $user = User::factory()->create();
        $kycProfile = KycProfile::factory()->create(['user_id' => $user->id, 'status' => 'PENDING']);
        
        $service = new KycDecisionService();
        // Passing mocked bureau data that yields > 80 score
        $service->evaluate($kycProfile, [
            'document_valid' => true,
            'cpf_valid' => true,
            'name_match' => true,
            'selfie_valid' => true,
            'bureau_fraud' => false
        ]);

        $this->assertEquals('APPROVED', $kycProfile->fresh()->status);
        $this->assertEquals(2, $kycProfile->fresh()->level);
    }

    public function test_kyc_score_rejection()
    {
        $user = User::factory()->create();
        $kycProfile = KycProfile::factory()->create(['user_id' => $user->id, 'status' => 'PENDING']);
        
        $service = new KycDecisionService();
        // Failing most checks (Score < 50)
        $service->evaluate($kycProfile, [
            'document_valid' => false,
            'cpf_valid' => false,
            'name_match' => true,
            'selfie_valid' => false,
            'bureau_fraud' => true
        ]);

        $this->assertEquals('REJECTED', $kycProfile->fresh()->status);
    }

    public function test_pix_owner_mismatch_generates_review()
    {
        $user = User::factory()->create();
        KycDocument::create([
            'user_id' => $user->id,
            'document_type' => 'cpf',
            'storage_path' => '11122233344',
        ]);
        $service = new PixOwnershipService();
        
        // Passing a different CPF as trusted source
        $decision = $service->validateOwnership($user, 'test@pix.com', 'email', '99988877766', true);

        // Score is not high, so it should be MANUAL_REVIEW, not WITHDRAW_BLOCK
        $this->assertEquals('MANUAL_REVIEW', $decision);
        
        $this->assertDatabaseHas('financial_anomalies', [
            'type' => 'pix_owner_mismatch'
        ]);
        
        $this->assertDatabaseHas('pix_keys', [
            'pix_key' => 'test@pix.com',
            'status' => 'BLOCKED'
        ]);
    }

    public function test_feature_flags_disable_deposits()
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->create(['user_id' => $user->id]);
        
        FeatureFlag::updateOrCreate(['key' => 'deposits_enabled'], ['is_active' => false]);

        $ledger = app(LedgerService::class);

        $this->expectException(\App\Exceptions\NotifyErrorException::class);
        $this->expectExceptionMessage('Deposits are currently disabled globally by the administrator.');
        
        $ledger->credit($wallet, 100, null, 'Test Deposit');
    }

    public function test_seller_health_score_calculation()
    {
        $user = User::factory()->create(['created_at' => now()->subDays(100)]); // +15
        
        KycProfile::create(['user_id' => $user->id, 'level' => 2, 'status' => 'APPROVED']); // +20
        FraudProfile::create(['user_id' => $user->id, 'risk_level' => 'LOW']); // +20
        // No restrictions: +10
        // No chargebacks: +15
        
        // Sum so far: 15 + 20 + 20 + 15 + 10 = 80 (GOOD)
        
        $service = new SellerHealthService();
        $score = $service->calculateScore($user);
        
        $this->assertEquals(80, $score);
        $this->assertEquals('GOOD', $service->classifyScore($score));
    }
}
