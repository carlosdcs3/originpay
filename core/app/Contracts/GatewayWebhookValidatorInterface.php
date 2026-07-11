<?php

namespace App\Contracts;

use App\DTOs\Gateway\GatewayWebhookData;

interface GatewayWebhookValidatorInterface
{
    /**
     * Valida a autenticidade do payload/assinatura do webhook
     */
    public function validate(array $payload, array $headers): bool;

    /**
     * Converte o payload nativo do Gateway para o nosso DTO padrão
     */
    public function normalize(array $payload): GatewayWebhookData;
}
