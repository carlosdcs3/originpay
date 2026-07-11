<?php

namespace App\Gateway\Security;

use App\Gateway\Config\GatewayCredentials;

class GatewayAuthenticationService
{
    public function __construct(protected GatewayAuthenticationRegistry $registry) {}

    public function authenticate(string $gatewaySlug, GatewayCredentials $credentials): array
    {
        $driver = $this->registry->get($gatewaySlug);
        
        // Converte o objeto tipado para um array configuracional básico para o driver
        $config = [
            'client_id' => $credentials->clientId,
            'client_secret' => $credentials->clientSecret,
            'certificate_path' => $credentials->certificate,
            'certificate_password' => $credentials->certificatePassword,
        ];

        return $driver->authenticate($config);
    }
}
