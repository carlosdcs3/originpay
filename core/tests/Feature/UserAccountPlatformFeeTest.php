<?php

namespace Tests\Feature;

use App\Models\PlatformFeeRule;
use App\Models\User;
use App\Services\Fees\PlatformFeeResolver;
use App\Http\Controllers\Frontend\SettingController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserAccountPlatformFeeTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_without_override_sees_default_platform_fee(): void
    {
        $user = User::factory()->create();
        $this->createRule([
            'payment_method' => 'pix',
            'fixed_fee' => 0.50,
            'percentage_fee' => 2.50,
        ]);

        $view = $this->actingAs($user)->accountView();
        $pixFee = $view->getData()['appliedFees']->firstWhere('method', 'pix');

        $this->assertSame('global', $pixFee['source']);
        $this->assertSame('Taxa padrão', $pixFee['source_label']);
        $this->assertEquals(0.50, $pixFee['fixed_fee']);
        $this->assertEquals(2.50, $pixFee['percentage_fee']);
    }

    public function test_customer_with_override_sees_negotiated_platform_fee(): void
    {
        $user = User::factory()->create();
        $this->createRule([
            'payment_method' => 'pix',
            'fixed_fee' => 0.50,
            'percentage_fee' => 2.50,
        ]);
        $this->createRule([
            'scope' => PlatformFeeRule::SCOPE_MERCHANT,
            'user_id' => $user->id,
            'payment_method' => 'pix',
            'fixed_fee' => 0.20,
            'percentage_fee' => 1.10,
        ]);

        $view = $this->actingAs($user)->accountView();
        $pixFee = $view->getData()['appliedFees']->firstWhere('method', 'pix');

        $this->assertSame('merchant', $pixFee['source']);
        $this->assertSame('Taxa negociada', $pixFee['source_label']);
        $this->assertEquals(0.20, $pixFee['fixed_fee']);
        $this->assertEquals(1.10, $pixFee['percentage_fee']);
    }

    public function test_method_without_active_rule_uses_safe_fallback_on_account_page(): void
    {
        $user = User::factory()->create();

        $view = $this->actingAs($user)->accountView();
        $pixFee = $view->getData()['appliedFees']->firstWhere('method', 'pix');

        $this->assertSame('fallback', $pixFee['source']);
        $this->assertTrue($pixFee['is_fallback']);
    }

    private function accountView()
    {
        return app(SettingController::class)->account(app(PlatformFeeResolver::class));
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
