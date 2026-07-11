<?php

namespace App\Services\Gateway\Providers;

use App\Contracts\GatewayWebhookValidatorInterface;
use App\DTOs\Gateway\GatewayWebhookData;
use LogicException;

class EfiWebhookValidator implements GatewayWebhookValidatorInterface
{
    public function validate(array $payload, array $headers): bool
    {
        throw new LogicException('Webhook validator not implemented for this provider.');
    }

    public function normalize(array $payload): GatewayWebhookData
    {
        throw new LogicException('Webhook normalization not implemented for this provider.');
    }
}
