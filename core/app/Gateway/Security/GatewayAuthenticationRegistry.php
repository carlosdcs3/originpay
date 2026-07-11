<?php

namespace App\Gateway\Security;

use App\Gateway\Security\Drivers\AuthenticationDriverInterface;
use App\Exceptions\Gateway\GatewayConfigurationException;

class GatewayAuthenticationRegistry
{
    /** @var array<string, AuthenticationDriverInterface> */
    protected array $drivers = [];

    public function register(string $gatewaySlug, AuthenticationDriverInterface $driver): self
    {
        $this->drivers[strtolower($gatewaySlug)] = $driver;
        return $this;
    }

    public function get(string $gatewaySlug): AuthenticationDriverInterface
    {
        $slug = strtolower($gatewaySlug);
        if (!isset($this->drivers[$slug])) {
            throw new GatewayConfigurationException("Nenhum driver de autenticacao registrado para o gateway: {$slug}");
        }

        return $this->drivers[$slug];
    }
}
