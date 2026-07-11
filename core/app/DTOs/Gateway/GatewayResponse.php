<?php

namespace App\DTOs\Gateway;

class GatewayResponse
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $transaction_id = null,
        public readonly ?string $status = null,
        public readonly ?string $error_message = null,
        public readonly ?array $payload = null,
        
        // Novos campos
        public readonly ?string $request_id = null,
        public readonly ?string $correlation_id = null,
        public readonly ?string $provider_reference = null,
        public readonly ?int $status_code = null,
        public readonly ?array $headers = null,
        public readonly ?string $raw_body = null,
        public readonly ?int $latency = null,
        public readonly ?int $retry_count = null
    ) {}
}
