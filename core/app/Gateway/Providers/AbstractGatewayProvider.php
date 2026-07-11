<?php

namespace App\Gateway\Providers;

use App\Contracts\GatewayProviderInterface;
use App\Gateway\Pipeline\GatewayPipelineFactory;
use App\Gateway\Config\GatewayConfiguration;
use App\Gateway\Config\GatewayCredentials;
use App\DTOs\Gateway\GatewayCapability;
use App\Gateway\Policies\GatewayRetryPolicy;
use App\DTOs\Gateway\GatewayResponse;

abstract class AbstractGatewayProvider implements GatewayProviderInterface
{
    public function __construct(
        protected GatewayPipelineFactory $pipelineFactory,
        protected GatewayConfiguration $config,
        protected GatewayCredentials $credentials
    ) {}

    /**
     * Helper para disparar requisições HTTP via Pipeline corporativa imutável
     */
    protected function request(string $method, string $endpointKey, array $body = [], array $extraHeaders = [], array $options = []): GatewayResponse
    {
        $url = $this->config->endpoints->get($endpointKey); // Ja lanca exception se nao existir
        
        $requestData = [
            'gatewaySlug' => $this->getGatewaySlug(),
            'method' => $method,
            'url' => $url,
            'headers' => array_merge($this->config->defaultHeaders, $extraHeaders),
            'body' => $body,
            'options' => $options,
            'timeout' => $this->config->timeout,
            'retry_policy' => new GatewayRetryPolicy(),
            'credentials' => $this->credentials // Repassado para o AuthMiddleware via RequestData
        ];

        $client = $this->pipelineFactory->createClient();

        return $client->send($requestData);
    }

    abstract protected function getGatewaySlug(): string;

    abstract public function capabilities(): GatewayCapability;
}
