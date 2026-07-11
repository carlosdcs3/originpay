<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\WebhookDelivery;
use App\Services\Webhooks\WebhookDeliveryService;

class DeliverWebhooksCommand extends Command
{
    protected $signature = 'originpay:webhooks:deliver';

    protected $description = 'Deliver pending and failed webhooks that are due for a retry';

    public function handle(WebhookDeliveryService $deliveryService)
    {
        $this->info('Finding webhooks to deliver...');

        // Fetch deliveries that are due
        $deliveries = WebhookDelivery::whereIn('status', [
                \App\Enums\WebhookDeliveryStatus::PENDING, 
                \App\Enums\WebhookDeliveryStatus::RETRYING
            ])
            ->where(function($q) {
                $q->whereNull('next_attempt_at')
                  ->orWhere('next_attempt_at', '<=', now());
            })
            ->with(['endpoint', 'event'])
            ->get();

        $this->info('Found ' . $deliveries->count() . ' deliveries to process.');

        foreach ($deliveries as $delivery) {
            $this->info("Attempting delivery ID: {$delivery->id}");
            $deliveryService->attemptDelivery($delivery);
        }

        $this->info('Webhook delivery process completed.');
    }
}
