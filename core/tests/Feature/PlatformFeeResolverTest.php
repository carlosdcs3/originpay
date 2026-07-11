<?php

namespace Tests\Feature;

use App\Models\PlatformFeeRule;
use App\Models\User;
use App\Services\Fees\PlatformFeeResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlatformFeeResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_without_individual_rule_uses_global_rule(): void
    {
        $user = User::factory()->create();
        $this->createRule(['scope' => PlatformFeeRule::SCOPE_GLOBAL, 'percentage_fee' => 2.00, 'fixed_fee' => 0.30]);

        $result = app(PlatformFeeResolver::class)->resolve($user, 'pix', 100.00);

        $this->assertEquals(PlatformFeeRule::SCOPE_GLOBAL, $result->source);
        $this->assertEquals(2.30, $result->platformFeeAmount);
    }

    public function test_customer_individual_rule_overrides_global_rule(): void
    {
        $user = User::factory()->create();
        $this->createRule(['scope' => PlatformFeeRule::SCOPE_GLOBAL, 'percentage_fee' => 2.00, 'fixed_fee' => 0.30]);
        $merchantRule = $this->createRule([
            'scope' => PlatformFeeRule::SCOPE_MERCHANT,
            'user_id' => $user->id,
            'percentage_fee' => 1.00,
            'fixed_fee' => 0.10,
        ]);

        $result = app(PlatformFeeResolver::class)->resolve($user, 'pix', 100.00);

        $this->assertEquals(PlatformFeeRule::SCOPE_MERCHANT, $result->source);
        $this->assertEquals($merchantRule->id, $result->ruleId);
        $this->assertEquals(1.10, $result->platformFeeAmount);
    }

    public function test_expired_individual_rule_is_ignored(): void
    {
        $user = User::factory()->create();
        $this->createRule(['scope' => PlatformFeeRule::SCOPE_GLOBAL, 'percentage_fee' => 2.00, 'fixed_fee' => 0.30]);
        $this->createRule([
            'scope' => PlatformFeeRule::SCOPE_MERCHANT,
            'user_id' => $user->id,
            'percentage_fee' => 1.00,
            'fixed_fee' => 0.10,
            'starts_at' => now()->subDays(10),
            'ends_at' => now()->subDay(),
        ]);

        $result = app(PlatformFeeResolver::class)->resolve($user, 'pix', 100.00);

        $this->assertEquals(PlatformFeeRule::SCOPE_GLOBAL, $result->source);
        $this->assertEquals(2.30, $result->platformFeeAmount);
    }

    public function test_future_individual_rule_is_ignored(): void
    {
        $user = User::factory()->create();
        $this->createRule(['scope' => PlatformFeeRule::SCOPE_GLOBAL, 'percentage_fee' => 2.00, 'fixed_fee' => 0.30]);
        $this->createRule([
            'scope' => PlatformFeeRule::SCOPE_MERCHANT,
            'user_id' => $user->id,
            'percentage_fee' => 1.00,
            'fixed_fee' => 0.10,
            'starts_at' => now()->addDay(),
        ]);

        $result = app(PlatformFeeResolver::class)->resolve($user, 'pix', 100.00);

        $this->assertEquals(PlatformFeeRule::SCOPE_GLOBAL, $result->source);
        $this->assertEquals(2.30, $result->platformFeeAmount);
    }

    public function test_uses_safe_fallback_when_no_active_rule_exists(): void
    {
        $user = User::factory()->create();

        $result = app(PlatformFeeResolver::class)->resolve($user, 'pix', 100.00);

        $this->assertEquals('fallback', $result->source);
        $this->assertNull($result->ruleId);
        $this->assertEquals(2.30, $result->platformFeeAmount);
    }

    private function createRule(array $overrides = []): PlatformFeeRule
    {
        return PlatformFeeRule::create(array_merge([
            'scope' => PlatformFeeRule::SCOPE_GLOBAL,
            'user_id' => null,
            'payment_method' => 'pix',
            'currency' => 'BRL',
            'fixed_fee' => 0.30,
            'percentage_fee' => 2.00,
            'minimum_fee' => 0,
            'maximum_fee' => null,
            'settlement_delay_days' => 1,
            'reserve_percentage' => 0,
            'status' => PlatformFeeRule::STATUS_ACTIVE,
            'starts_at' => now()->subDay(),
            'ends_at' => null,
            'metadata' => ['test' => true],
        ], $overrides));
    }
}
