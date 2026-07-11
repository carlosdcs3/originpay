<?php

namespace App\Gateway;

use App\Enums\ChargeStatus;

class NormalizedEvent
{
    public string $gatewayChargeId;
    public ChargeStatus $status;
    public array $rawPayload;
    public ?string $gatewayEventId;

    public function __construct(string $gatewayChargeId, ChargeStatus $status, array $rawPayload, ?string $gatewayEventId = null)
    {
        $this->gatewayChargeId = $gatewayChargeId;
        $this->status = $status;
        $this->rawPayload = $rawPayload;
        $this->gatewayEventId = $gatewayEventId;
    }
}
