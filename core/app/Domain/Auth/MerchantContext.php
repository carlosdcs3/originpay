<?php

namespace App\Domain\Auth;

class MerchantContext
{
    public function __construct(
        public readonly string $merchantId,
        public readonly string $merchantName,
        public readonly string $environment,
        public readonly array $permissions,
        public readonly string $requestId,
        public readonly string $apiVersion,
        public readonly ?string $credentialId = null
    ) {
    }
}
