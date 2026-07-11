<?php

namespace App\Gateway\Providers;

class PushinPayProvider implements GatewayProviderInterface
{
    public function definition(): GatewayDefinition
    {
        return new GatewayDefinition([
            'name' => 'PushinPay',
            'code' => 'pushinpay',
            'adapter' => 'PushinPayGatewayAdapter',
            'logo' => 'assets/providers/pushinpay.svg',
            'description' => 'Solução moderna focada em PIX com alta performance.',
            'operations' => [
                'PIX_CHARGE',
                'PIX_WITHDRAW',
            ],
            'credentials' => [
                'api_token' => [
                    'label' => 'API Token',
                    'group' => 'Autenticação',
                    'input' => 'password',
                    'credential_type' => 'secret',
                    'required' => true,
                    'masked' => true,
                    'sensitive' => true,
                    'rules' => ['required', 'string'],
                    'icon' => 'fa-key',
                    'order' => 1,
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
                    'order' => 2,
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
