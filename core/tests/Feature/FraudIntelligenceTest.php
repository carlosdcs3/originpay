<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\DeviceFingerprint;
use App\Models\FraudProfile;
use App\Models\AccountRestriction;
use App\Models\IdentityFingerprint;
use App\Services\Fraud\DeviceFingerprintService;
use App\Services\Fraud\IdentityRiskService;
use App\Services\Fraud\FraudScoringService;

class FraudIntelligenceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Set secret for test
        putenv('FRAUD_FINGERPRINT_SECRET=test_secret_for_hmac');
    }

    public function test_fingerprint_uses_hmac_sha256()
    {
        $user = User::factory()->create();
        $service = new DeviceFingerprintService();
        
        $deviceData = [
            'user_agent' => 'Mozilla/5.0 Test',
            'timezone' => 'America/Sao_Paulo',
            'language' => 'pt-BR',
            'resolution' => '1920x1080',
            'platform' => 'Win32',
            'frontend_hash' => 'abc123hash'
        ];

        // Ensure IP is not passed in the deviceData that forms the fingerprint
        $isShared = $service->recordFingerprint($user, $deviceData);

        $fingerprint = DeviceFingerprint::where('user_id', $user->id)->first();
        
        // Ensure it's a 64 char string (SHA256 hex)
        $this->assertEquals(64, strlen($fingerprint->fingerprint_hash));
        $this->assertFalse($isShared);
    }

    public function test_shared_device_isolated_does_not_freeze_account()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        
        $service = new DeviceFingerprintService();
        $deviceData = ['user_agent' => 'SameDevice'];

        $service->recordFingerprint($user1, $deviceData);
        $isShared = $service->recordFingerprint($user2, $deviceData); // User 2 is sharing

        $this->assertTrue($isShared);

        $fraudService = new FraudScoringService();
        $profile = $fraudService->evaluateUser($user2, ['shared_device' => ['fingerprint_hash' => 'dummy']]);

        // Score should be 30 (MEDIUM)
        $this->assertEquals(30, $profile->fraud_score);
        $this->assertEquals('MEDIUM', $profile->risk_level);

        // Account should NOT have FULL_FREEZE or WITHDRAW_BLOCK
        $this->assertDatabaseMissing('account_restrictions', [
            'user_id' => $user2->id
        ]);
    }

    public function test_duplicate_cpf_freezes_account()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        
        $identityService = new IdentityRiskService();
        $identityService->checkIdentity($user1, ['cpf' => '12345678900']);
        
        $risks = $identityService->checkIdentity($user2, ['cpf' => '12345678900']);
        
        $this->assertTrue($risks['duplicate_cpf']);

        $fraudService = new FraudScoringService();
        $profile = $fraudService->evaluateUser($user2, ['duplicated_identity' => ['cpf_hash' => 'dummy']]);

        // Score should be 100 (CRITICAL)
        $this->assertEquals(100, $profile->fraud_score);
        $this->assertEquals('CRITICAL', $profile->risk_level);

        // Account should have FULL_FREEZE
        $this->assertDatabaseHas('account_restrictions', [
            'user_id' => $user2->id,
            'restriction_type' => 'FULL_FREEZE'
        ]);
    }

    public function test_high_score_blocks_withdraw_but_not_freeze()
    {
        $user = User::factory()->create();
        $fraudService = new FraudScoringService();
        
        // 50 (Shared Pix) + 20 (Geo Velocity) = 70 (HIGH)
        $profile = $fraudService->evaluateUser($user, [
            'shared_pix' => [],
            'geo_velocity_impossible' => []
        ]);

        $this->assertEquals(70, $profile->fraud_score);
        $this->assertEquals('HIGH', $profile->risk_level);

        $this->assertDatabaseHas('account_restrictions', [
            'user_id' => $user->id,
            'restriction_type' => 'WITHDRAW_BLOCK'
        ]);

        $this->assertDatabaseMissing('account_restrictions', [
            'user_id' => $user->id,
            'restriction_type' => 'FULL_FREEZE'
        ]);
    }
}
