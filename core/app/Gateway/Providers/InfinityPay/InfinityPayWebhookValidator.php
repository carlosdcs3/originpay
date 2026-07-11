<?php

namespace App\Gateway\Providers\InfinityPay;

use App\Gateway\Contracts\GatewayWebhookValidatorInterface;
use App\Gateway\Contracts\Data\GatewayWebhookData;
use App\Gateway\Contracts\Data\GatewayCredentials;
use Illuminate\Http\Request;

class InfinityPayWebhookValidator implements GatewayWebhookValidatorInterface
{
    protected GatewayCredentials $credentials;

    public function __construct(GatewayCredentials $credentials)
    {
        $this->credentials = $credentials;
    }

    public function getIdentifier(): string
    {
        return 'infinitypay';
    }

    public function validate(Request $request, ?GatewayCredentials $credentials = null): GatewayWebhookData
    {
        // Stub: Implement signature validation
        return GatewayWebhookData::valid(
            gatewayReference: 'stub_ref',
            status: 'COMPLETED',
            amount: 100.0,
            operation: 'PIX',
            rawPayload: $request->all()
        );
    }
}
