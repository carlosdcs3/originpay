<?php

namespace App\DTOs\Gateway;

class GatewayOperation
{
    public function __construct(
        public readonly string $type, // pix, boleto, credit_card
        public readonly bool $enabled,
        public readonly ?float $min_amount = null,
        public readonly ?float $max_amount = null
    ) {}
}
