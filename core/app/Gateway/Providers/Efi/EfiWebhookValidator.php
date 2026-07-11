<?php

namespace App\Gateway\Providers\Efi;

use App\Contracts\GatewayWebhookValidatorInterface;
use App\DTOs\Gateway\GatewayWebhookData;

class EfiWebhookValidator implements GatewayWebhookValidatorInterface
{
    public function validate(array $payload, array $headers): bool
    {
        // Na Efi (PIX) a validacao muitas vezes e via mTLS (quem bateu) e se o webhook for pix, vem a chave pix
        // Isso depende da configuração de certificado. Supondo valido se houver 'pix'.
        return isset($payload['pix']);
    }

    public function normalize(array $payload): GatewayWebhookData
    {
        $pix = $payload['pix'][0] ?? [];

        return new GatewayWebhookData(
            event_type: 'pix.received',
            entity_type: 'charge',
            provider_reference: $pix['txid'] ?? null,
            status: 'paid', // Efi PIX in via de regra e pago
            amount: (float) ($pix['valor'] ?? 0),
            gateway: 'efi',
            raw_payload: $payload
        );
    }
}
