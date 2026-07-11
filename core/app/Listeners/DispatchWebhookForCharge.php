<?php

namespace App\Listeners;

use App\Events\ChargeStatusChanged;
use App\Services\Webhooks\WebhookEventService;

class DispatchWebhookForCharge
{
    public function __construct(
        private readonly WebhookEventService $webhookService
    ) {}

    public function handle(ChargeStatusChanged $event): void
    {
        $charge = $event->charge;
        
        $payload = [
            'charge_id' => $charge->id,
            'charge_number' => $charge->chargeNumber,
            'amount' => $charge->amount,
            'currency' => $charge->currency,
            'status' => $charge->status->value,
            'metadata' => $charge->merchantMetadata,
            'failure_code' => $charge->failureCode,
            'failure_message' => $charge->failureMessage,
            'created_at' => $charge->createdAt,
        ];

        $this->webhookService->dispatchEvent(
            merchantId: (int) $charge->merchantId,
            eventType: $event->eventType,
            payload: $payload,
            environment: $charge->environment
        );
    }
}
