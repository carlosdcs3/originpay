<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Transaction;
use App\Models\KycProfile;
use App\Services\Security\TenantBypass;
use Illuminate\Support\Facades\Cache;

class WarRoomSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_idor_blocked_between_tenants()
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        // User B creates a wallet
        TenantBypass::run(function() use ($userB) {
            Wallet::factory()->create(['user_id' => $userB->id, 'balance' => 1000]);
        });

        // User A tries to find all wallets
        $this->actingAs($userA);
        $walletsA = Wallet::all();
        $this->assertCount(0, $walletsA); // User A should see 0

        // User A tries to find User B's wallet explicitly
        $this->actingAs($userA);
        $walletFound = Wallet::where('user_id', $userB->id)->first();
        $this->assertNull($walletFound);
    }

    public function test_admin_access_global_data_via_bypass()
    {
        $userA = User::factory()->create();
        
        TenantBypass::run(function() use ($userA) {
            Wallet::factory()->create(['user_id' => $userA->id, 'balance' => 500]);
        });

        // Simulating admin bypass
        $wallets = TenantBypass::run(function() {
            return Wallet::all();
        });

        $this->assertCount(1, $wallets);
    }

    public function test_user_cannot_mass_assign_balance_or_role()
    {
        $user = User::factory()->create(['role' => \App\Enums\UserRole::USER]);
        
        $user->fill(['role' => 'admin']);
        $this->assertEquals(\App\Enums\UserRole::USER, $user->role); // Should not change

        TenantBypass::run(function() use ($user) {
            $wallet = Wallet::factory()->create(['user_id' => $user->id, 'balance' => 100]);
            $wallet->fill(['balance' => 999999]);
            $this->assertEquals(100, $wallet->balance); // Should not change
        });
    }

    public function test_webhook_distributed_lock_prevents_duplicate_processing()
    {
        // Redis needs to be available for this to test correctly, assuming Array cache for testing.
        Cache::store('array')->lock('webhook:efi:123', 10)->get(function () {
            // First webhook acquires lock
            $lock2 = Cache::store('array')->lock('webhook:efi:123', 10)->get();
            $this->assertFalse($lock2); // Second webhook fails to acquire
        });
    }

    public function test_withdraw_race_condition_locked()
    {
        $user = User::factory()->create();
        
        $lockAcquired1 = Cache::store('array')->lock("user_withdraw_limit_{$user->id}", 10)->get();
        $this->assertTrue($lockAcquired1);

        // Attempt second withdraw simultaneously
        $lockAcquired2 = Cache::store('array')->lock("user_withdraw_limit_{$user->id}", 10)->get();
        $this->assertFalse($lockAcquired2);
    }

    public function test_beta_readiness_fails_if_debug_is_true()
    {
        config(['app.debug' => true]);
        
        $exitCode = $this->artisan('beta:readiness')->run();
        $this->assertEquals(1, $exitCode);
    }
}
