<?php

namespace App\Gateway\Config;

class GatewayConfiguration
{
    public function __construct(
        public readonly string $baseUrl,
        public readonly EndpointCollection $endpoints,
        public readonly bool $sandbox = false,
        public readonly ?string $version = null,
        public readonly array $defaultHeaders = [],
        public readonly int $timeout = 30,
        public readonly string $userAgent = 'OriginPay Gateway Integration/1.0'
    ) {}
}
