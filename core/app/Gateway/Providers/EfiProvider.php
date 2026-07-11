<?php

namespace App\Gateway\Providers;

class EfiProvider implements GatewayProviderInterface
{
    public function definition(): GatewayDefinition
    {
        return new GatewayDefinition([
            'name' => 'Efí (Gerencianet)',
            'code' => 'efi',
            'adapter' => 'EfiGatewayAdapter',
            'logo' => 'assets/providers/efi.svg',
            'description' => 'Gateway brasileiro completo, focado em PIX, Boletos e Splits.',
            'operations' => [
                'PIX_CHARGE',
                'PIX_WITHDRAW',
                'BOLETO',
                'CARD_CREDIT',
                'CARD_DEBIT',
            ],
            'credentials' => [
                'client_id' => [
                    'label' => 'Client ID',
                    'group' => 'Autenticação',
                    'input' => 'text',
                    'credential_type' => 'identifier',
                    'required' => true,
                    'placeholder' => 'Client ID da Efí',
                    'rules' => ['required', 'string'],
                    'icon' => 'fa-id-card',
                    'order' => 1,
                    'depends_on' => null,
                ],
                'client_secret' => [
                    'label' => 'Client Secret',
                    'group' => 'Autenticação',
                    'input' => 'password',
                    'credential_type' => 'secret',
                    'required' => true,
                    'masked' => true,
                    'sensitive' => true,
                    'rules' => ['required', 'string'],
                    'icon' => 'fa-key',
                    'order' => 2,
                    'depends_on' => null,
                ],
                'certificate_path' => [
                    'label' => 'Certificado de Produção (.p12)',
                    'group' => 'Autenticação',
                    'input' => 'file',
                    'credential_type' => 'certificate',
                    'required' => true,
                    ' sensitive' => true,
                    'accept' => '.p12',
                    'storage' => 'private',
                    'rules' => ['required', 'file', 'mimes:p12,pem', 'max:5120'],
                    'icon' => 'fa-certificate',
                    'order' => 3,
                    'depends_on' => null,
                    'description' => 'Certificado gerado no painel da Efí',
                    'documentation_url' => 'https://sejaefi.com.br/api/certificado',
                ],
                'pix_key' => [
                    'label' => 'Chave PIX',
                    'group' => 'Cobrança PIX',
                    'input' => 'text',
                    'credential_type' => 'identifier',
                    'required' => true,
                    'rules' => ['required', 'string'],
                    'icon' => 'fa-brands fa-pix',
                    'order' => 4,
                ],
                'webhook_secret' => [
                    'label' => 'Webhook Secret (Hash)',
                    'group' => 'Webhooks',
                    'input' => 'password',
                    'credential_type' => 'secret',
                    'required' => false,
                    'masked' => true,
                    'sensitive' => true,
                    'copyable' => true,
                    'rules' => ['nullable', 'string'],
                    'icon' => 'fa-shield-halved',
                    'order' => 5,
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
