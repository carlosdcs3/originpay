<?php

namespace App\Services\Gateway;

use App\Contracts\GatewayProviderInterface;
use App\Contracts\GatewayWebhookValidatorInterface;
use App\Services\Gateway\Providers\EfiProvider;
use App\Services\Gateway\Providers\AsaasProvider;
use App\Services\Gateway\Providers\CoraProvider;
use App\Services\Gateway\Providers\InfinityPayProvider;
use App\Services\Gateway\Providers\EfiWebhookValidator;
use App\Services\Gateway\Providers\AsaasWebhookValidator;
use App\Services\Gateway\Providers\CoraWebhookValidator;
use App\Services\Gateway\Providers\InfinityPayWebhookValidator;
use Exception;

class GatewayManager
{
    /**
     * Retorna a implementação ativa (Active Flow) para comunicação via API.
     */
    public function provider(string $gatewaySlug): GatewayProviderInterface
    {
        return match (strtolower($gatewaySlug)) {
            'efi' => new EfiProvider(),
            'asaas' => new AsaasProvider(),
            'cora' => new CoraProvider(),
            'infinitypay' => new InfinityPayProvider(),
            default => throw new Exception("Provider para gateway [{$gatewaySlug}] não implementado.")
        };
    }

    /**
     * Retorna o validador de webhook (Passive Flow) do Gateway.
     */
    public function webhookValidator(string $gatewaySlug): GatewayWebhookValidatorInterface
    {
        return match (strtolower($gatewaySlug)) {
            'efi' => new EfiWebhookValidator(),
            'asaas' => new AsaasWebhookValidator(),
            'cora' => new CoraWebhookValidator(),
            'infinitypay' => new InfinityPayWebhookValidator(),
            default => throw new Exception("Validador de webhook para [{$gatewaySlug}] não implementado.")
        };
    }
}
