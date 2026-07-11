<?php

namespace App\DTOs\Gateway;

class GatewayCapability
{
    public function __construct(
        public readonly bool $supports_pix,
        public readonly bool $supports_boleto,
        public readonly bool $supports_card,
        public readonly bool $supports_withdraw,
        public readonly bool $supports_refund,
        public readonly bool $supports_partial_refund,
        public readonly bool $supports_cancel,
        public readonly bool $supports_split,
        public readonly bool $supports_reconciliation,
        public readonly bool $supports_webhook,
        public readonly bool $supports_token_refresh,
        public readonly bool $supports_mtls
    ) {}
}
