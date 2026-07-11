<?php

namespace App\Services\Webhooks;

use App\Models\WebhookDelivery;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Crypt;

class WebhookDeliveryService
{
    public function __construct(
        private readonly WebhookSignatureService $signatureService
    ) {
    }

    public function attemptDelivery(WebhookDelivery $delivery): void
    {
        $endpoint = $delivery->endpoint;
        $event = $delivery->event;

        $payloadJson = json_encode([
            'id' => $event->event_id,
            'type' => $event->event_type,
            'api_version' => $event->api_version,
            'created_at' => $event->created_at->toIso8601String(),
            'data' => $event->payload,
        ]);

        // Decrypt the secret for signing
        try {
            $secret = Crypt::decryptString($endpoint->secret_encrypted);
        } catch (\Exception $e) {
            $this->markFailed($delivery, 'Failed to decrypt webhook secret.');
            return;
        }

        $headers = array_merge(
            ['Content-Type' => 'application/json'],
            $this->signatureService->generateHeader($payloadJson, $secret)
        );

        $delivery->increment('attempt_count');
        $delivery->update(['last_attempt_at' => now()]);

        try {
            // 5 second timeout to prevent hanging
            $response = Http::timeout(5)->withHeaders($headers)->post($endpoint->url, json_decode($payloadJson, true));

            $delivery->update([
                'response_status' => $response->status(),
                'response_body' => Str::limit($response->body(), 1000), // Limit size just in case
            ]);

            if ($response->successful()) {
                $delivery->update(['status' => \App\Enums\WebhookDeliveryStatus::DELIVERED, 'next_attempt_at' => null]);
                $endpoint->update(['last_used_at' => now()]);
            } else {
                $this->scheduleRetry($delivery, 'Non-2xx response: ' . $response->status());
            }

        } catch (\Exception $e) {
            $delivery->update([
                'response_status' => null,
                'response_body' => null,
            ]);
            $this->scheduleRetry($delivery, $e->getMessage());
        }
    }

    private function scheduleRetry(WebhookDelivery $delivery, string $errorMessage): void
    {
        $maxAttempts = 5;

        if ($delivery->attempt_count >= $maxAttempts) {
            $this->markFailed($delivery, "Max attempts reached. Last error: " . $errorMessage);
            return;
        }

        // Exponential backoff: 1m, 5m, 30m, 2h...
        $backoffMinutes = [1, 5, 30, 120, 360]; 
        $delay = $backoffMinutes[$delivery->attempt_count - 1] ?? 60;

        $delivery->update([
            'status' => \App\Enums\WebhookDeliveryStatus::RETRYING,
            'error_message' => substr($errorMessage, 0, 500),
            'next_attempt_at' => now()->addMinutes($delay),
        ]);
    }

    private function markFailed(WebhookDelivery $delivery, string $errorMessage): void
    {
        $delivery->update([
            'status' => \App\Enums\WebhookDeliveryStatus::DEAD_LETTER,
            'error_message' => substr($errorMessage, 0, 500),
            'next_attempt_at' => null,
        ]);
    }
}
