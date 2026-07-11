<?php

namespace App\Services;

use App\Enums\EnvironmentMode;
use App\Models\Merchant;

class PaymentCheckoutPresentationService
{
    /**
     * Detect the environment mode based on transaction data.
     */
    public function detectEnvironmentMode(mixed $transaction): EnvironmentMode
    {
        if (isset($transaction->trx_data['environment'])) {
            return EnvironmentMode::from($transaction->trx_data['environment']);
        }

        if (isset($transaction->trx_data['is_sandbox'])) {
            return $transaction->trx_data['is_sandbox'] ? EnvironmentMode::SANDBOX : EnvironmentMode::PRODUCTION;
        }

        $merchant = Merchant::find($transaction->trx_data['merchant_id']);
        if ($merchant && $merchant->sandbox_enabled) {
            if (str_contains($transaction->remarks ?? '', 'SANDBOX_TRANSACTION')) {
                return EnvironmentMode::SANDBOX;
            }
        }

        return EnvironmentMode::PRODUCTION;
    }

    /**
     * Build the presentation data array for the checkout view.
     */
    public function buildCheckoutData(mixed $transaction, mixed $merchantData): array
    {
        $environment = $this->detectEnvironmentMode($transaction);
        $isSandbox = $environment->isSandbox();

        // Convert merchant data to array if it's a model
        $merchantArray = is_array($merchantData) ? $merchantData : $merchantData->toArray();

        $data = array_merge($transaction->trx_data, $merchantArray, [
            'payment_amount' => "{$transaction->payable_amount} {$transaction->payable_currency}",
            'environment' => $environment->value,
            'is_sandbox' => $isSandbox,
            'environment_label' => $environment->getLabel(),
            'environment_badge_class' => $environment->getBadgeClass(),
        ]);

        if ($isSandbox) {
            $data['sandbox_notice'] = __('This is a sandbox transaction. No real money will be processed.');
            $data['sandbox_transaction_id'] = $transaction->trx_id;
        }

        return $data;
    }
}
