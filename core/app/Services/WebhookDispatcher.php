<?php

namespace App\Services;

use App\Models\WebhookEndpoint;
use App\Models\WebhookDelivery;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class WebhookDispatcher
{
    public function dispatch($userId, $eventType, array $payload, $environment = 'live')
    {
        return $this->dispatchToEndpoints($userId, $eventType, $payload, $environment);
    }

    public function dispatchOnce($userId, $eventType, array $payload, string $idempotencyKey, $environment = 'live')
    {
        return $this->dispatchToEndpoints($userId, $eventType, $payload, $environment, $idempotencyKey);
    }

    private function dispatchToEndpoints($userId, $eventType, array $payload, $environment = 'live', ?string $idempotencyKey = null)
    {
        $endpoints = WebhookEndpoint::where('user_id', $userId)
            ->where('environment', $environment)
            ->where('status', true)
            ->get();

        $results = [];

        foreach ($endpoints as $endpoint) {
            // Check if endpoint is subscribed to this event (if specific events are defined)
            if (!empty($endpoint->events) && !in_array($eventType, $endpoint->events) && !in_array('*', $endpoint->events)) {
                continue;
            }

            $results[] = $idempotencyKey
                ? $this->sendOnce($endpoint, $eventType, $payload, $idempotencyKey)
                : $this->send($endpoint, $eventType, $payload);
        }

        return $results;
    }

    public function send(WebhookEndpoint $endpoint, $eventType, array $payload)
    {
        return $this->sendToEndpoint($endpoint, $eventType, $payload);
    }

    public function sendOnce(WebhookEndpoint $endpoint, $eventType, array $payload, string $idempotencyKey)
    {
        if (WebhookDelivery::where('webhook_endpoint_id', $endpoint->id)
            ->where('idempotency_key', $idempotencyKey)
            ->exists()) {
            return true;
        }

        return $this->sendToEndpoint($endpoint, $eventType, $payload, $idempotencyKey);
    }

    private function sendToEndpoint(WebhookEndpoint $endpoint, $eventType, array $payload, ?string $idempotencyKey = null)
    {
        $eventId = 'evt_' . Str::random(24);
        $timestamp = time();

        $finalPayload = [
            'id' => $eventId,
            'type' => $eventType,
            'created' => now()->toIso8601String(),
            'environment' => $endpoint->environment,
            'data' => $payload,
        ];

        $jsonPayload = json_encode($finalPayload);

        // Generate HMAC SHA-256 Signature
        // The signature combines timestamp and payload to prevent replay attacks
        $delivery = WebhookDelivery::create([
            'webhook_endpoint_id' => $endpoint->id,
            'event_type' => $eventType,
            'idempotency_key' => $idempotencyKey,
            'payload' => $finalPayload,
            'attempt' => 1,
            'successful' => false,
        ]);

        $eventId = $delivery->payload['id'];
        $timestamp = time();
        $payloadStr = $timestamp . '.' . json_encode($delivery->payload);
        
        $signatureV1 = hash_hmac('sha256', $payloadStr, $endpoint->secret);
        
        $signatureHeader = "t={$timestamp},v1={$signatureV1}";

        if ($endpoint->old_secret && $endpoint->old_secret_expires_at && $endpoint->old_secret_expires_at->isFuture()) {
            $signatureV0 = hash_hmac('sha256', $payloadStr, $endpoint->old_secret);
            $signatureHeader .= ",v0={$signatureV0}";
        }

        $requestId = 'req_wbhk_' . uniqid();

        try {
            $response = Http::timeout(5)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Digisynk-Signature' => $signatureHeader,
                    'Digisynk-Event-ID' => $eventId,
                    'Digisynk-Timestamp' => $timestamp,
                    'X-OriginPay-Request-Id' => $requestId,
                    'Digisynk-Request-ID' => $requestId,
                ])
                ->post($endpoint->url, $delivery->payload);

            $delivery->update([
                'status_code' => $response->status(),
                'response_body' => $response->body() ? substr($response->body(), 0, 1000) : null,
                'successful' => $response->successful(),
            ]);

            if (!$response->successful()) {
                $this->scheduleRetry($delivery);
            }

            return $response->successful();

        } catch (\Exception $e) {
            $delivery->update([
                'status_code' => 0,
                'response_body' => substr($e->getMessage(), 0, 1000),
                'successful' => false,
            ]);

            $this->scheduleRetry($delivery);

            return false;
        }
    }

    protected function scheduleRetry(WebhookDelivery $delivery)
    {
        $delivery->update(['next_retry_at' => now()->addMinutes(1)]);
        \App\Jobs\RetryWebhookDeliveryJob::dispatch($delivery->id)
            ->delay(now()->addMinutes(1));
    }
}
