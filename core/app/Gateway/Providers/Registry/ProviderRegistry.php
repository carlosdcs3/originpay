<?php

namespace App\Gateway\Providers\Registry;

use App\Gateway\Providers\CoinRemitterProvider;
use App\Gateway\Providers\CustomProvider;
use App\Gateway\Providers\EfiProvider;
use App\Gateway\Providers\GatewayProviderInterface;
use App\Gateway\Providers\MisticPayProvider;
use App\Gateway\Providers\NowPaymentsProvider;
use App\Gateway\Providers\PushinPayProvider;
use InvalidArgumentException;

class ProviderRegistry
{
    /**
     * Returns an array of all registered providers.
     *
     * @return array<string, GatewayProviderInterface>
     */
    public static function getProviders(): array
    {
        return [
            'efi'          => new EfiProvider(),
            'pushinpay'    => new PushinPayProvider(),
            'nowpayments'  => new NowPaymentsProvider(),
            'coinremitter' => new CoinRemitterProvider(),
            'misticpay'    => new MisticPayProvider(),
            'custom'       => new CustomProvider(),
        ];
    }

    /**
     * Get a specific provider by its code.
     *
     * @param string $code
     * @return GatewayProviderInterface
     * @throws InvalidArgumentException
     */
    public static function getProvider(string $code): GatewayProviderInterface
    {
        $providers = self::getProviders();

        if (!array_key_exists($code, $providers)) {
            throw new InvalidArgumentException("Provider not found for code: {$code}");
        }

        return $providers[$code];
    }
}
