<?php

namespace App\Payment\Modern;

use App\Enums\ProviderType;
use Exception;
use Illuminate\Support\Facades\App;

class ModernPaymentGatewayFactory
{
    /**
     * Array mapeando Enum para Classes (Adapters)
     */
    protected array $gateways = [
        // ProviderType::STRIPE->value => StripeModernGatewayAdapter::class,
        // ProviderType::PAYPAL->value => PaypalModernGatewayAdapter::class,
    ];

    /**
     * Resolve a modern payment gateway from ProviderType enum.
     *
     * @throws Exception
     */
    public function getGateway(ProviderType $provider): ModernPaymentGatewayInterface
    {
        $gatewayClass = $this->gateways[$provider->value] ?? null;

        if (!$gatewayClass) {
            throw new Exception(sprintf('Modern payment gateway adapter not implemented for provider: %s', $provider->value));
        }

        return App::make($gatewayClass);
    }

    /**
     * Registers a custom gateway at runtime (useful for testing)
     */
    public function registerGateway(ProviderType $provider, string $className): void
    {
        $this->gateways[$provider->value] = $className;
    }
}
