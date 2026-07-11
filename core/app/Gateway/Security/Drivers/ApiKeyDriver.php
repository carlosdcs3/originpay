<?php

namespace App\Gateway\Security\Drivers;

class ApiKeyDriver implements AuthenticationDriverInterface
{
    public function authenticate(array $config): array
    {
        return [
            'headers' => [
                'access_token' => $config['client_secret'] ?? '' // Ex: Asaas
            ]
        ];
    }
}
