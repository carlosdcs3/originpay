<?php

namespace App\Domain\Auth;

class ApiRequestContext
{
    public function __construct(
        public readonly string $requestId,
        public readonly MerchantContext $merchant,
        public readonly ?string $origin,
        public readonly ?string $userAgent,
        public readonly ?string $ipAddress,
        public readonly ?string $idempotencyKey,
        public readonly string $apiVersion,
        public readonly string $timestamp
    ) {
    }
}
