<?php

namespace App\Gateway\Providers;

class CoinRemitterProvider implements GatewayProviderInterface
{
    public function definition(): GatewayDefinition
    {
        return new GatewayDefinition([
            'name' => 'CoinRemitter',
            'code' => 'coinremitter',
            'adapter' => 'CoinRemitterGatewayAdapter',
            'logo' => 'assets/providers/coinremitter.svg',
            'description' => 'Gateway focado em infraestrutura de carteiras Crypto.',
            'operations' => [
                'CRYPTO_CHARGE',
                'CRYPTO_WITHDRAW',
            ],
            'credentials' => [
                'merchant_id' => [
                    'label' => 'Merchant ID',
                    'group' => 'Autenticação',
                    'input' => 'text',
                    'credential_type' => 'identifier',
                    'required' => true,
                    'rules' => ['required', 'string'],
                    'icon' => 'fa-id-badge',
                    'order' => 1,
                ],
                'api_key' => [
                    'label' => 'API Key',
                    'group' => 'Autenticação',
                    'input' => 'password',
                    'credential_type' => 'secret',
                    'required' => true,
                    'masked' => true,
                    'sensitive' => true,
                    'rules' => ['required', 'string'],
                    'icon' => 'fa-key',
                    'order' => 2,
                ],
                'password' => [
                    'label' => 'Password',
                    'group' => 'Autenticação',
                    'input' => 'password',
                    'credential_type' => 'secret',
                    'required' => true,
                    'masked' => true,
                    'sensitive' => true,
                    'rules' => ['required', 'string'],
                    'icon' => 'fa-lock',
                    'order' => 3,
                ],
            ],
            'withdraw_fields' => [
                [
                    'label' => 'Endereço da Carteira',
                    'key' => 'wallet_address',
                    'type' => 'text',
                    'placeholder' => 'Cole seu endereço de destino',
                    'required' => true,
                ],
            ]
        ]);
    }
}
