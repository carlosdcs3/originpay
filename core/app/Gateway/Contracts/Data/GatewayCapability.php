<?php

namespace App\Gateway\Contracts\Data;

class GatewayCapability
{
    public function __construct(
        public readonly string $operation,
        public readonly bool $enabled,
        public readonly float $minimumAmount = 0.0,
        public readonly ?float $maximumAmount = null,
        public readonly int $settlementTime = 0,
        public readonly string $feeType = 'fixed',
        public readonly float $feeValue = 0.0,
        public readonly bool $supportsPartialRefund = false,
        public readonly bool $supportsSplit = false,
        public readonly bool $supportsWebhooks = true,
        public readonly bool $supportsCancel = false,
        public readonly bool $supportsReconciliation = false
    ) {}
}
