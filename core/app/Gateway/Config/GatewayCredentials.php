<?php

namespace App\Gateway\Config;

class GatewayCredentials
{
    public function __construct(
        public readonly string $clientId,
        public readonly string $clientSecret,
        public readonly ?string $certificate = null,
        public readonly ?string $certificatePassword = null,
        public readonly ?string $pixKey = null,
        public readonly ?array $headers = []
    ) {}
}
