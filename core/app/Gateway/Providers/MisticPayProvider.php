<?php

namespace App\Gateway\Providers;

class MisticPayProvider implements GatewayProviderInterface
{
    public function definition(): GatewayDefinition
    {
        return new GatewayDefinition([
            'name' => 'MisticPay',
            'code' => 'misticpay',
            'adapter' => 'MisticPayGatewayAdapter',
            'logo' => 'assets/providers/misticpay.svg',
            'description' => 'Solução de pagamentos com split e multi-adquirência.',
            'operations' => [
                'PIX_CHARGE',
                'PIX_WITHDRAW',
                'BOLETO',
                'CARD_CREDIT',
                'CARD_DEBIT',
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
                'secret' => [
                    'label' => 'Secret',
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
                'webhook_secret' => [
                    'label' => 'Webhook Secret',
                    'group' => 'Webhooks',
                    'input' => 'password',
                    'credential_type' => 'secret',
                    'required' => false,
                    'masked' => true,
                    'sensitive' => true,
                    'copyable' => true,
                    'rules' => ['nullable', 'string'],
                    'icon' => 'fa-shield-halved',
                    'order' => 4,
                ]
            ],
            'withdraw_fields' => [
                [
                    'label' => 'Chave PIX',
                    'key' => 'pix_key',
                    'type' => 'pix_key',
                    'placeholder' => 'Sua chave PIX',
                    'required' => true,
                ],
            ]
        ]);
    }
}
