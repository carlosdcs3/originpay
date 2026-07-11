<?php

namespace App\Gateway\Config;

use App\Exceptions\Gateway\GatewayConfigurationException;

class EndpointCollection
{
    protected array $endpoints = [];

    public function __construct(array $endpoints = [])
    {
        foreach ($endpoints as $key => $url) {
            $this->set($key, $url);
        }
    }

    public function get(string $key): string
    {
        if (!isset($this->endpoints[$key])) {
            throw new GatewayConfigurationException("Endpoint '{$key}' não registrado no GatewayConfiguration.");
        }
        
        return $this->endpoints[$key];
    }

    public function set(string $key, string $url): self
    {
        if (isset($this->endpoints[$key])) {
            throw new GatewayConfigurationException("Chave de endpoint '{$key}' duplicada.");
        }

        // Validação básica de string não vazia
        if (empty(trim($url))) {
            throw new GatewayConfigurationException("A URL para o endpoint '{$key}' não pode ser vazia.");
        }

        $this->endpoints[$key] = $url;
        return $this;
    }
}
