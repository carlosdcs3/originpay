<?php

namespace App\Domain\Payments;

class GatewayResult
{
    public function __construct(
        public readonly bool $success,
        public readonly string $status, // e.g. authorized, declined, failed
        public readonly ?string $gatewayReference = null,
        public readonly ?string $authorizationCode = null,
        public readonly ?string $failureCode = null,
        public readonly ?string $failureMessage = null,
        public readonly array $rawResponse = [],
        public readonly int $processingTime = 0,
        public readonly ?string $gatewayName = null,
        public readonly ?string $transactionId = null,
        public readonly array $metadata = [],
        public readonly bool $isTechnicalFailure = false
    ) {}
}
