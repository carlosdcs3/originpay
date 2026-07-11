<?php

namespace Tests\Feature;

use App\Models\PlatformFeeRule;
use App\Modules\Fees\Domain\Contracts\PlatformFeeSimulator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeesPilotActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_fee_simulator_uses_global_rule(): void
    {
        PlatformFeeRule::create([
            'scope' => PlatformFeeRule::SCOPE_GLOBAL,
            'payment_method' => 'pix',
            'currency' => 'BRL',
            'fixed_fee' => 0.30,
            'percentage_fee' => 2.00,
            'minimum_fee' => 0,
            'maximum_fee' => null,
            'settlement_delay_days' => 1,
            'reserve_percentage' => 0,
            'status' => PlatformFeeRule::STATUS_ACTIVE,
        ]);

        $result = app(PlatformFeeSimulator::class)->simulate([
            'amount' => 100,
            'payment_method' => 'pix',
            'currency' => 'BRL',
        ]);

        $this->assertSame(100.0, $result->grossAmount);
        $this->assertSame(2.3, $result->platformFeeAmount);
        $this->assertSame(97.7, $result->netAmount);
        $this->assertSame(PlatformFeeRule::SCOPE_GLOBAL, $result->source);
    }

    public function test_platform_fee_simulator_can_use_specific_rule(): void
    {
        $rule = PlatformFeeRule::create([
            'scope' => PlatformFeeRule::SCOPE_GLOBAL,
            'payment_method' => 'card',
            'currency' => 'BRL',
            'fixed_fee' => 1.00,
            'percentage_fee' => 1.50,
            'minimum_fee' => 0,
            'maximum_fee' => null,
            'settlement_delay_days' => 1,
            'reserve_percentage' => 0,
            'status' => PlatformFeeRule::STATUS_ACTIVE,
        ]);

        $result = app(PlatformFeeSimulator::class)->simulate([
            'amount' => 200,
            'payment_method' => 'pix',
            'currency' => 'BRL',
            'rule_id' => $rule->id,
        ]);

        $this->assertSame(4.0, $result->platformFeeAmount);
        $this->assertSame(196.0, $result->netAmount);
        $this->assertSame($rule->id, $result->ruleId);
    }
}
