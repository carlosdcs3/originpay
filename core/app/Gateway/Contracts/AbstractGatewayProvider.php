<?php

namespace App\Gateway\Contracts;

use App\Gateway\Contracts\GatewayProviderInterface;
use App\Gateway\Contracts\Enums\GatewayOperation;
use App\Gateway\Contracts\Data\GatewayResponse;
use App\Gateway\Contracts\Data\GatewayCredentials;
use App\Gateway\Http\GatewayHttpClient;

abstract class AbstractGatewayProvider implements GatewayProviderInterface
{
    protected GatewayCredentials $credentials;

    public function __construct(GatewayCredentials $credentials)
    {
        $this->credentials = $credentials;
    }

    /**
     * Helper semântico para simplificar a criação de PIX na camada de negócio.
     */
    public function createPix(array $data): GatewayResponse
    {
        return $this->sendRequest(GatewayOperation::CHARGE_PIX, $data);
    }

    /**
     * Helper semântico para saque/withdraw.
     */
    public function withdraw(array $data): GatewayResponse
    {
        return $this->sendRequest(GatewayOperation::WITHDRAW_PIX, $data);
    }
    
    /**
     * Utilitário para formatar chamadas HTTP baseadas com timeout via GatewayHttpClient centralizado.
     */
    protected function httpClient(): GatewayHttpClient
    {
        $client = new GatewayHttpClient($this->getIdentifier());
        $client->withHeaders(array_merge([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ], $this->credentials->headers));
        
        $client->withTimeouts(
            $this->credentials->timeouts['connect'] ?? 10,
            $this->credentials->timeouts['timeout'] ?? 30
        );

        return $client;
    }
}
