<?php

namespace App\Gateway\Providers;

class NowPaymentsProvider implements GatewayProviderInterface
{
    public function definition(): GatewayDefinition
    {
        return new GatewayDefinition([
            'name' => 'NowPayments',
            'code' => 'nowpayments',
            'adapter' => 'NowPaymentsGatewayAdapter',
            'logo' => 'assets/providers/nowpayments.svg',
            'description' => 'Gateway líder para pagamentos e saques em Criptomoedas.',
            'operations' => [
                'CRYPTO_CHARGE',
                'CRYPTO_WITHDRAW',
            ],
            'credentials' => [
                'api_key' => [
                    'label' => 'API Key',
                    'group' => 'Autenticação',
                    'input' => 'password',
                    'credential_type' => 'api_key',
                    'required' => true,
                    'masked' => true,
                    'sensitive' => true,
                    'rules' => ['required', 'string'],
                    'icon' => 'fa-key',
                    'order' => 1,
                ],
                'ipn_secret' => [
                    'label' => 'IPN Secret',
                    'group' => 'Webhooks',
                    'input' => 'password',
                    'credential_type' => 'secret',
                    'required' => false,
                    'masked' => true,
                    'sensitive' => true,
                    'copyable' => true,
                    'rules' => ['nullable', 'string'],
                    'icon' => 'fa-shield-halved',
                    'order' => 2,
                ],
            ],
            'withdraw_fields' => [
                [
                    'label' => 'Endereço da Carteira (Wallet Address)',
                    'key' => 'wallet_address',
                    'type' => 'text',
                    'placeholder' => 'Cole seu endereço de destino',
                    'required' => true,
                ],
                [
                    'label' => 'Rede (Network)',
                    'key' => 'network',
                    'type' => 'text',
                    'placeholder' => 'Ex: TRC20, ERC20, BSC',
                    'required' => true,
                ],
            ]
        ]);
    }
}
