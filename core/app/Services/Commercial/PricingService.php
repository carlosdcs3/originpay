<?php

namespace App\Services\Commercial;

use App\Models\PlanVersion;
use App\Models\Price;

class PricingService
{
    /**
     * Resolves the final price given a plan version and billing period
     * Future: Add promotion and coupon logic here
     */
    public function resolvePrice(PlanVersion $planVersion, $billingPeriod = 'monthly')
    {
        $price = $planVersion->prices()->where('billing_period', $billingPeriod)->where('is_active', true)->first();
        
        if (!$price) {
            throw new \Exception("Price not configured for this period.");
        }

        // Base values
        $amount = $price->amount;
        $setupFee = $price->setup_fee;

        return [
            'price_id' => $price->id,
            'plan_version_id' => $planVersion->id,
            'amount' => $amount,
            'setup_fee' => $setupFee,
            'total' => $amount + $setupFee,
            'currency' => $price->currency,
            'billing_period' => $price->billing_period,
            'applied_promotions' => [],
            'applied_coupon' => null
        ];
    }
}
