<?php

namespace App\Services\Webhooks;

use App\Models\WebhookEvent;
use App\Models\WebhookEndpoint;
use App\Models\WebhookDelivery;
use Illuminate\Support\Str;

class WebhookEventService
{
    public function dispatchEvent(int $merchantId, string $eventType, array $payload, string $environment = 'sandbox', string $apiVersion = 'v1'): void
    {
        // 1. Create the normalized event
        $event = WebhookEvent::create([
            'provider' => 'originpay',
            'merchant_id' => $merchantId,
            'event_id' => 'evt_' . Str::random(24),
            'event_type' => $eventType,
            'api_version' => $apiVersion,
            'environment' => $environment,
            'payload' => $payload,
            'status' => 'RECEIVED',
            'attempts' => 0,
            'metadata' => [
                'source' => 'outbound_webhook',
                'environment' => $environment,
            ],
        ]);

        // 2. Find matching endpoints
        // In a high-volume system, we would query JSON or process this via a queue worker.
        // For the foundation, we'll fetch active endpoints for the merchant/env.
        $endpoints = WebhookEndpoint::where('merchant_id', $merchantId)
            ->where('environment', $environment)
            ->where('status', 'active')
            ->get();

        foreach ($endpoints as $endpoint) {
            // Check if endpoint is subscribed to this event or wildcard '*'
            if (in_array('*', $endpoint->events) || in_array($eventType, $endpoint->events)) {
                WebhookDelivery::create([
                    'delivery_id' => 'wd_' . Str::ulid(),
                    'webhook_endpoint_id' => $endpoint->id,
                    'webhook_event_id' => $event->id,
                    'event_type' => $eventType,
                    'idempotency_key' => 'webhook:' . $endpoint->id . ':' . $event->event_id,
                    'payload' => $payload,
                    'attempt' => 1,
                    'successful' => false,
                    'status' => \App\Enums\WebhookDeliveryStatus::PENDING,
                    'attempt_count' => 0,
                    'next_attempt_at' => now(), // Dispatch immediately
                ]);
            }
        }
    }
}
