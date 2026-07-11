<?php

namespace Tests\Feature;

use App\Models\PlatformFeeRule;
use App\Services\Fees\PlatformFeeCalculator;
use InvalidArgumentException;
use RuntimeException;
use Tests\TestCase;

class PlatformFeeCalculatorTest extends TestCase
{
    public function test_calculates_fixed_plus_percentage_fee(): void
    {
        $result = app(PlatformFeeCalculator::class)->calculate(100.00, $this->rule([
            'fixed_fee' => 0.30,
            'percentage_fee' => 2.00,
        ]));

        $this->assertEquals(2.30, $result->platformFeeAmount);
        $this->assertEquals(97.70, $result->netAmount);
        $this->assertEquals(100.00, $result->grossAmount);
        $this->assertEquals(2.30, $result->snapshot['platform_fee_amount']);
    }

    public function test_respects_minimum_fee(): void
    {
        $result = app(PlatformFeeCalculator::class)->calculate(100.00, $this->rule([
            'fixed_fee' => 0,
            'percentage_fee' => 1.00,
            'minimum_fee' => 2.00,
        ]));

        $this->assertEquals(2.00, $result->platformFeeAmount);
        $this->assertEquals(98.00, $result->netAmount);
    }

    public function test_respects_maximum_fee(): void
    {
        $result = app(PlatformFeeCalculator::class)->calculate(100.00, $this->rule([
            'fixed_fee' => 0,
            'percentage_fee' => 10.00,
            'maximum_fee' => 5.00,
        ]));

        $this->assertEquals(5.00, $result->platformFeeAmount);
        $this->assertEquals(95.00, $result->netAmount);
    }

    public function test_calculates_fee_by_amount_tiers(): void
    {
        $rule = $this->rule([
            'fixed_fee' => 0.30,
            'percentage_fee' => 1.50,
            'metadata' => [
                'pricing_model' => 'tiered',
                'tiers' => [
                    [
                        'from_amount' => 0,
                        'to_amount' => 20,
                        'fixed_fee' => 0.30,
                        'percentage_fee' => 0,
                    ],
                    [
                        'from_amount' => 20.01,
                        'to_amount' => null,
                        'fixed_fee' => 0.30,
                        'percentage_fee' => 1.50,
                    ],
                ],
            ],
        ]);

        $smallCharge = app(PlatformFeeCalculator::class)->calculate(10.00, $rule);
        $largeCharge = app(PlatformFeeCalculator::class)->calculate(100.00, $rule);

        $this->assertEquals(0.30, $smallCharge->platformFeeAmount);
        $this->assertEquals(9.70, $smallCharge->netAmount);
        $this->assertEquals(1.80, $largeCharge->platformFeeAmount);
        $this->assertEquals(98.20, $largeCharge->netAmount);
        $this->assertEquals('tiered', $largeCharge->snapshot['pricing_model']);
        $this->assertEquals(20.01, $largeCharge->snapshot['selected_tier']['from_amount']);
    }

    public function test_rejects_negative_amount(): void
    {
        $this->expectException(InvalidArgumentException::class);

        app(PlatformFeeCalculator::class)->calculate(-1.00, $this->rule());
    }

    public function test_rejects_fee_greater_than_gross_amount(): void
    {
        $this->expectException(RuntimeException::class);

        app(PlatformFeeCalculator::class)->calculate(100.00, $this->rule([
            'fixed_fee' => 200.00,
            'percentage_fee' => 0,
        ]));
    }

    private function rule(array $overrides = []): PlatformFeeRule
    {
        return new PlatformFeeRule(array_merge([
            'scope' => PlatformFeeRule::SCOPE_GLOBAL,
            'payment_method' => 'pix',
            'currency' => 'BRL',
            'fixed_fee' => 0.30,
            'percentage_fee' => 2.00,
            'minimum_fee' => null,
            'maximum_fee' => null,
            'settlement_delay_days' => 1,
            'reserve_percentage' => 0,
            'status' => PlatformFeeRule::STATUS_ACTIVE,
        ], $overrides));
    }
}
