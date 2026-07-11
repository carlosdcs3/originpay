<?php

namespace App\DTOs\Gateway;

class GatewayWebhookData
{
    public function __construct(
        public readonly string $gateway,
        public readonly string $event_id,
        public readonly string $event_type, // ex: payment_received, chargeback_opened
        public readonly string $entity_type, // ex: charge, withdrawal, settlement
        public readonly string $provider_reference,
        public readonly string $status,
        public readonly float $amount,
        public readonly ?float $fee = null,
        public readonly ?string $payment_method = null,
        public readonly ?array $raw_payload = null
    ) {}
}
