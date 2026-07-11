<?php

namespace App\Gateway\Contracts\Data;

class GatewayConfiguration
{
    public function __construct(
        public readonly string $productionUrl,
        public readonly string $sandboxUrl,
        public readonly array $endpoints,
        public readonly string $version,
        public readonly array $mandatoryHeaders = [],
        public readonly ?string $signatureAlgorithm = null,
        public readonly string $authenticationPolicy = 'bearer'
    ) {}
}
