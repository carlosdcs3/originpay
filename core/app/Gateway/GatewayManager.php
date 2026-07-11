<?php

namespace App\Gateway;

use App\Models\PaymentGateway;
use App\Gateway\Contracts\GatewayProviderInterface;
use App\Gateway\Contracts\GatewayWebhookValidatorInterface;
use App\Gateway\Contracts\Data\GatewayCredentials;
use App\Gateway\Providers\Efi\EfiProvider;
use App\Gateway\Providers\Efi\EfiWebhookValidator;
use App\Gateway\Providers\Asaas\AsaasProvider;
use App\Gateway\Providers\Asaas\AsaasWebhookValidator;
use App\Gateway\Providers\Cora\CoraProvider;
use App\Gateway\Providers\Cora\CoraWebhookValidator;
use App\Gateway\Providers\InfinityPay\InfinityPayProvider;
use App\Gateway\Providers\InfinityPay\InfinityPayWebhookValidator;
use Exception;

class GatewayManager
{
    /**
     * Resolve the legacy charge adapter used by ChargeService.
     */
    public static function adapter(PaymentGateway $gateway): GatewayAdapter
    {
        $code = strtolower((string) $gateway->code);
        $credentials = is_array($gateway->credentials)
            ? $gateway->credentials
            : (json_decode((string) $gateway->credentials, true) ?: []);

        return match ($code) {
            'mock' => new MockGatewayAdapter($gateway),
            'efi' => new EfiGatewayAdapter($gateway, $credentials),
            default => throw new Exception("Gateway adapter not implemented: {$gateway->code}"),
        };
    }

    /**
     * Resolve a Provider instance based on the gateway ID, injecting DB credentials.
     */
    public function resolveProvider(int $gatewayId): GatewayProviderInterface
    {
        $gateway = $this->getGatewayModel($gatewayId);
        $credentials = $this->extractCredentials($gateway);

        return match(strtolower($gateway->code)) {
            'efi' => new EfiProvider($credentials),
            'asaas' => new AsaasProvider($credentials),
            'cora' => new CoraProvider($credentials),
            'infinitypay' => new InfinityPayProvider($credentials),
            default => throw new Exception("Provider não suportado para o gateway: {$gateway->name}")
        };
    }

    /**
     * Resolve a Webhook Validator based on the gateway ID.
     */
    public function resolveValidator(int $gatewayId): GatewayWebhookValidatorInterface
    {
        $gateway = $this->getGatewayModel($gatewayId);
        $credentials = $this->extractCredentials($gateway);

        return match(strtolower($gateway->code)) {
            'efi' => new EfiWebhookValidator($credentials),
            'asaas' => new AsaasWebhookValidator($credentials),
            'cora' => new CoraWebhookValidator($credentials),
            'infinitypay' => new InfinityPayWebhookValidator($credentials),
            default => throw new Exception("Webhook Validator não suportado para o gateway: {$gateway->name}")
        };
    }

    private function getGatewayModel(int $gatewayId): PaymentGateway
    {
        $gateway = PaymentGateway::find($gatewayId);
        if (!$gateway) {
            throw new Exception("Gateway não encontrado no banco de dados.");
        }
        return $gateway;
    }

    private function extractCredentials(PaymentGateway $gateway): GatewayCredentials
    {
        $raw = is_array($gateway->parameter) ? $gateway->parameter : json_decode($gateway->parameter, true) ?? [];
        return GatewayCredentials::fromArray($raw);
    }
}
