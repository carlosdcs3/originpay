<?php

namespace App\Gateway\Contracts;

use Illuminate\Http\Request;
use App\Gateway\Contracts\Data\GatewayWebhookData;
use App\Gateway\Contracts\Data\GatewayCredentials;

interface GatewayWebhookValidatorInterface
{
    /**
     * Define the unique identifier of the provider.
     */
    public function getIdentifier(): string;

    /**
     * Validate the webhook payload and signature.
     */
    public function validate(Request $request, GatewayCredentials $credentials): GatewayWebhookData;
}
