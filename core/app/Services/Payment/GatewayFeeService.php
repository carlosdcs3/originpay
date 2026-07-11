<?php

namespace App\Services\Payment;

use App\Models\GatewayFeeConfig;

class FeeResultDTO
{
    public float $gross_amount = 0;
    public float $platform_fee_amount = 0;
    public float $provider_fee_amount = 0;
    public float $withdraw_fee_amount = 0;
    public float $net_amount = 0;
    public array $snapshot = [];

    public function toArray(): array
    {
        return $this->snapshot;
    }
}

class GatewayFeeService
{
    public function calculateForDeposit(float $amount, string $provider): FeeResultDTO
    {
        return $this->calculate($amount, $provider, 'transaction');
    }

    public function calculateForWithdraw(float $amount, string $provider): FeeResultDTO
    {
        return $this->calculate($amount, $provider, 'withdraw');
    }

    public function calculateForRefund(float $amount, string $provider): FeeResultDTO
    {
        return $this->calculate($amount, $provider, 'refund');
    }

    protected function calculate(float $amount, string $provider, string $operationType): FeeResultDTO
    {
        $config = GatewayFeeConfig::where('provider', $provider)
            ->where('is_active', true)
            ->first();

        $result = new FeeResultDTO();
        $result->gross_amount = $amount;
        $result->net_amount = $amount; // Default before fees

        if (!$config) {
            // Se não tem config, assume taxa ZERO para não quebrar testes legados ou ambientes mal configurados,
            // mas armazena no snapshot que não havia config
            $result->snapshot = [
                'fee_config_id' => null,
                'provider' => $provider,
                'operation_type' => $operationType,
                'gross_amount' => $amount,
                'net_amount' => $amount,
                'platform_fee_amount' => 0,
                'provider_fee_amount' => 0,
                'withdraw_fee_amount' => 0,
                'fee_type' => 'none',
                'fixed_fee' => 0,
                'percent_fee' => 0,
                'provider_fee_mode' => 'none',
                'currency' => 'BRL'
            ];
            return $result;
        }

        // 1. Calculate Platform Fee
        $feeType = $config->{$operationType . '_fee_type'};
        $fixedFee = (float) $config->{$operationType . '_fixed_fee'};
        $percentFee = (float) $config->{$operationType . '_percent_fee'};

        $platformFee = 0;
        if ($feeType === 'fixed') {
            $platformFee = $fixedFee;
        } elseif ($feeType === 'percent') {
            $platformFee = ($amount * $percentFee) / 100;
        } elseif ($feeType === 'fixed_plus_percent') {
            $platformFee = $fixedFee + (($amount * $percentFee) / 100);
        }

        if ($platformFee < 0) {
            $platformFee = 0;
        }

        // 2. Calculate Provider Fee
        $providerFee = 0;
        if ($config->provider_fee_mode !== 'manual') {
            $pFixed = (float) $config->provider_fixed_fee;
            $pPercent = (float) $config->provider_percent_fee;
            $providerFee = $pFixed + (($amount * $pPercent) / 100);
            
            if ($providerFee < 0) {
                $providerFee = 0;
            }
        }

        // 3. Compute Net
        $netAmount = $amount - $platformFee - $providerFee;

        // Regra: Líquido nunca pode ser menor que zero
        if ($netAmount < 0) {
            throw new \Exception("Fee calculation resulted in negative net amount.");
        }

        $result->platform_fee_amount = round($platformFee, 2);
        $result->provider_fee_amount = round($providerFee, 2);
        $result->net_amount = round($netAmount, 2);

        $withdrawFee = $operationType === 'withdraw' ? $result->platform_fee_amount : 0;
        $result->withdraw_fee_amount = round($withdrawFee, 2);

        $result->snapshot = [
            'fee_config_id' => $config->id,
            'provider' => $provider,
            'operation_type' => $operationType,
            'gross_amount' => $result->gross_amount,
            'net_amount' => $result->net_amount,
            'platform_fee_amount' => $result->platform_fee_amount,
            'provider_fee_amount' => $result->provider_fee_amount,
            'withdraw_fee_amount' => $result->withdraw_fee_amount,
            'fee_type' => $feeType,
            'fixed_fee' => $fixedFee,
            'percent_fee' => $percentFee,
            'provider_fee_mode' => $config->provider_fee_mode,
            'currency' => $config->currency
        ];

        return $result;
    }
}
