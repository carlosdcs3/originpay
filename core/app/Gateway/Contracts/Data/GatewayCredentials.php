<?php

namespace App\Gateway\Contracts\Data;

class GatewayCredentials
{
    public function __construct(
        public readonly ?string $clientId = null,
        public readonly ?string $clientSecret = null,
        public readonly ?string $certificate = null,
        public readonly ?string $certificatePassword = null,
        public readonly ?string $pixKey = null,
        public readonly ?string $baseUrl = null,
        public readonly bool $sandbox = false,
        public readonly array $timeouts = ['connect' => 10, 'timeout' => 30],
        public readonly array $headers = []
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            clientId: $data['client_id'] ?? null,
            clientSecret: $data['client_secret'] ?? null,
            certificate: $data['certificate'] ?? null,
            certificatePassword: $data['certificate_password'] ?? null,
            pixKey: $data['pix_key'] ?? null,
            baseUrl: $data['base_url'] ?? null,
            sandbox: (bool) ($data['sandbox'] ?? false),
            timeouts: $data['timeouts'] ?? ['connect' => 10, 'timeout' => 30],
            headers: $data['headers'] ?? []
        );
    }
}
