<?php

namespace App\Gateway\Http\Transports;

interface GatewayTransportInterface
{
    /**
     * Envia uma requisição HTTP.
     * Retorna um array normalizado: ['status' => 200, 'headers' => [], 'body' => '']
     */
    public function request(string $method, string $url, array $headers = [], array $body = [], int $timeout = 30, array $options = []): array;
}
