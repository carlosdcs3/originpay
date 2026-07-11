<?php

namespace App\Services\Webhook\Handlers;

use App\DTOs\Gateway\GatewayWebhookData;
use App\Models\WebhookEvent;
use App\Models\Charge;
use App\Services\ChargeActionService;
use Exception;

class ChargeWebhookHandler implements WebhookHandlerInterface
{
    public function __construct(protected ChargeActionService $actionService) {}

    public function supports(string $entityType): bool
    {
        return $entityType === 'charge';
    }

    public function handle(GatewayWebhookData $data, WebhookEvent $event): void
    {
        $charge = Charge::where('trx_id', $data->provider_reference)->lockForUpdate()->first();
        if (!$charge) {
            throw new Exception("Charge not found for reference: {$data->provider_reference}");
        }

        if ($data->status === 'paid' && $charge->status === 'pending') {
            $this->actionService->reprocessWebhook($charge, 0); // System Admin ID
        }
    }
}
