<?php

namespace App\Services;

use App\Models\PlatformFeeSetting;

class PlatformFeeService
{
    /**
     * Get the active platform fee settings.
     *
     * @return PlatformFeeSetting|null
     */
    public function getSettings()
    {
        return PlatformFeeSetting::where('is_active', true)->first() ?? new PlatformFeeSetting([
            'small_transaction_limit' => 10.00,
            'small_transaction_fixed_fee' => 0.35,
            'standard_percentage_fee' => 2.00,
            'standard_fixed_fee' => 0.30,
        ]);
    }

    /**
     * Calculate the platform fee for a given amount.
     *
     * @param float $amount The transaction amount
     * @return array Detailed fee breakdown
     */
    public function calculateFee(float $amount): array
    {
        $settings = $this->getSettings();

        $platformFee = 0.0;
        
        if ($amount <= $settings->small_transaction_limit) {
            $platformFee = (float) $settings->small_transaction_fixed_fee;
        } else {
            $percentageFee = ($amount * $settings->standard_percentage_fee) / 100;
            $platformFee = (float) ($percentageFee + $settings->standard_fixed_fee);
        }

        $gatewayFee = 0.0; // Future integration with PSP fees
        $totalFee = $platformFee + $gatewayFee;

        return [
            'platform_fee' => $platformFee,
            'gateway_fee' => $gatewayFee,
            'total_fee' => $totalFee,
            'net_amount' => $amount - $totalFee,
            'fee_breakdown' => [
                'type' => $amount <= $settings->small_transaction_limit ? 'fixed_small' : 'percentage_plus_fixed',
                'small_limit' => $settings->small_transaction_limit,
            ]
        ];
    }
}
