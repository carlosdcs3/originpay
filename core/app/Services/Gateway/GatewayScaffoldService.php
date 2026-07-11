<?php

namespace App\Services\Gateway;

use App\Enums\MethodType;
use App\Models\DepositMethod;
use App\Models\PaymentGateway;
use App\Models\WithdrawMethod;

class GatewayScaffoldService
{
    /**
     * Parse the provider definition schema and build an array of empty credentials.
     */
    public function resolveInitialCredentials(mixed $definition): array
    {
        if (empty($definition->credentials)) {
            return [];
        }

        $initialCredentials = [];

        // Se o primeiro item for array (schema complexo), iteramos pegando as chaves
        if (is_array(reset($definition->credentials))) {
            foreach ($definition->credentials as $key => $schema) {
                $initialCredentials[$key] = '';
            }

            return $initialCredentials;
        }

        // Caso seja legacy array
        foreach ($definition->credentials as $key => $value) {
            if (is_int($key)) {
                $initialCredentials[$value] = ''; // was a flat array
            } else {
                $initialCredentials[$key] = ''; // associative legacy
            }
        }

        return $initialCredentials;
    }

    /**
     * Auto scaffold PIX Deposit and Withdraw methods if supported.
     */
    public function scaffoldAutomaticMethods(PaymentGateway $gateway, mixed $definition): void
    {
        // Auto Scaffold PIX Deposit Se suportado
        if ($gateway->supports_pix) {
            $pixCharge = new DepositMethod;
            $pixCharge->payment_gateway_id = $gateway->id;
            $pixCharge->type = MethodType::AUTOMATIC;
            $pixCharge->name = 'Cobrança PIX';
            $pixCharge->code = $gateway->code.'_pix';
            $pixCharge->icon = 'fa-brands fa-pix'; // Added
            $pixCharge->currency = 'BRL';
            $pixCharge->currency_symbol = 'R$';
            $pixCharge->min_limit = 1; // Added
            $pixCharge->max_limit = 10000; // Added
            $pixCharge->rate_type = 'fixed'; // Added
            $pixCharge->rate = 1; // Replaced conversion_rate
            $pixCharge->charge_type = 'fixed';
            $pixCharge->charge = 0;
            $pixCharge->status = false;
            $pixCharge->fields = []; // Added
            $pixCharge->notes = ''; // Added
            $pixCharge->save();
        }

        // Auto Scaffold PIX Withdraw se suportado
        if ($gateway->is_withdraw) {
            $pixWithdraw = new WithdrawMethod;
            $pixWithdraw->payment_gateway_id = $gateway->id;
            $pixWithdraw->type = MethodType::AUTOMATIC;
            $pixWithdraw->name = 'Saque Automático';
            $pixWithdraw->code = $gateway->code.'_withdraw';
            $pixWithdraw->icon = 'fa-brands fa-pix'; // Added
            $pixWithdraw->currency = 'BRL';
            $pixWithdraw->currency_symbol = 'R$';
            $pixWithdraw->min_limit = 1; // Added
            $pixWithdraw->max_limit = 10000; // Added
            $pixWithdraw->rate_type = 'fixed'; // Added
            $pixWithdraw->rate = 1; // Replaced conversion_rate
            $pixWithdraw->charge_type = 'fixed';
            $pixWithdraw->charge = 0;
            $pixWithdraw->status = false;
            $pixWithdraw->fields = $definition->withdraw_fields;
            $pixWithdraw->save();
        }
    }
}
