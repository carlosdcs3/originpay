<?php

namespace App\Domain\Payments;

class GatewayRuntimeConfig
{
    public function __construct(
        public readonly ?string $clientId = null,
        public readonly ?string $clientSecret = null,
        public readonly ?string $certificatePath = null,
        public readonly ?string $certificatePassword = null,
        public readonly ?string $pixKey = null,
        public readonly ?string $baseUrl = null,
        public readonly ?string $environment = null
    ) {}
}
