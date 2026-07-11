<?php

namespace App\Gateway\Security\Drivers;

class CertificateDriver implements AuthenticationDriverInterface
{
    public function authenticate(array $config): array
    {
        return [
            'options' => [
                'cert' => [$config['certificate_path'], $config['certificate_password']]
            ]
        ];
    }
}
