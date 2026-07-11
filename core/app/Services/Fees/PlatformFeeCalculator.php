<?php

namespace App\Services\Fees;

use App\Models\PlatformFeeRule;
use Illuminate\Support\Carbon;
use InvalidArgumentException;
use RuntimeException;

class PlatformFeeCalculator
{
    public function calculate(
        float $amount,
        PlatformFeeRule $rule,
        string $source = PlatformFeeRule::SCOPE_GLOBAL,
        Carbon|string|null $at = null
    ): PlatformFeeResult {
        if ($amount < 0) {
            throw new InvalidArgumentException('Amount must be greater than or equal to zero.');
        }

        $selectedTier = null;
        $fixedFee = (float) $rule->fixed_fee;
        $percentageFee = (float) $rule->percentage_fee;

        if (($rule->metadata['pricing_model'] ?? 'flat') === 'tiered') {
            $selectedTier = $this->selectTier($amount, $rule->metadata['tiers'] ?? []);

            if ($selectedTier !== null) {
                $fixedFee = (float) $selectedTier['fixed_fee'];
                $percentageFee = (float) $selectedTier['percentage_fee'];
            }
        }

        $calculatedFee = $fixedFee + ($amount * ($percentageFee / 100));

        if ($rule->minimum_fee !== null) {
            $calculatedFee = max($calculatedFee, (float) $rule->minimum_fee);
        }

        if ($rule->maximum_fee !== null) {
            $calculatedFee = min($calculatedFee, (float) $rule->maximum_fee);
        }

        $platformFeeAmount = round($calculatedFee, 2);
        $netAmount = round($amount - $platformFeeAmount, 2);

        if ($netAmount < 0) {
            throw new RuntimeException('Platform fee cannot be greater than the gross amount.');
        }

        $calculatedAt = ($at ? Carbon::parse($at) : now())->toISOString();

        $snapshot = [
            'rule_id' => $rule->exists ? $rule->id : null,
            'source' => $source,
            'payment_method' => $rule->payment_method,
            'currency' => $rule->currency,
            'fixed_fee' => (float) $rule->fixed_fee,
            'percentage_fee' => (float) $rule->percentage_fee,
            'pricing_model' => $rule->metadata['pricing_model'] ?? 'flat',
            'selected_tier' => $selectedTier,
            'applied_fixed_fee' => $fixedFee,
            'applied_percentage_fee' => $percentageFee,
            'minimum_fee' => $rule->minimum_fee === null ? null : (float) $rule->minimum_fee,
            'maximum_fee' => $rule->maximum_fee === null ? null : (float) $rule->maximum_fee,
            'settlement_delay_days' => (int) $rule->settlement_delay_days,
            'reserve_percentage' => (float) $rule->reserve_percentage,
            'gross_amount' => round($amount, 2),
            'platform_fee_amount' => $platformFeeAmount,
            'net_amount' => $netAmount,
            'calculated_at' => $calculatedAt,
        ];

        return new PlatformFeeResult(
            grossAmount: round($amount, 2),
            platformFeeAmount: $platformFeeAmount,
            netAmount: $netAmount,
            ruleId: $rule->exists ? $rule->id : null,
            source: $source,
            snapshot: $snapshot,
        );
    }

    private function selectTier(float $amount, array $tiers): ?array
    {
        foreach ($tiers as $tier) {
            $fromAmount = (float) ($tier['from_amount'] ?? 0);
            $toAmount = $tier['to_amount'] ?? null;

            if ($amount < $fromAmount) {
                continue;
            }

            if ($toAmount !== null && $amount > (float) $toAmount) {
                continue;
            }

            return [
                'from_amount' => round($fromAmount, 2),
                'to_amount' => $toAmount === null ? null : round((float) $toAmount, 2),
                'fixed_fee' => (float) ($tier['fixed_fee'] ?? 0),
                'percentage_fee' => (float) ($tier['percentage_fee'] ?? 0),
            ];
        }

        return null;
    }
}
