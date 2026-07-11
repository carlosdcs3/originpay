<?php

namespace App\Jobs;

use App\Models\WebhookDelivery;
use App\Models\WebhookEndpoint;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class RetryWebhookDeliveryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $deliveryId;

    public function __construct($deliveryId)
    {
        $this->deliveryId = $deliveryId;
    }

    public function handle()
    {
        $delivery = WebhookDelivery::find($this->deliveryId);
        
        if (!$delivery || $delivery->successful) {
            return;
        }

        $endpoint = WebhookEndpoint::find($delivery->webhook_endpoint_id);
        if (!$endpoint || !$endpoint->status) {
            return;
        }

        $delivery->increment('attempt');

        $eventId = $delivery->payload['id'];
        $timestamp = time();
        $signature = hash_hmac('sha256', $timestamp . '.' . json_encode($delivery->payload), $endpoint->secret);
        
        // Request ID inheritance or generation
        $requestId = 'req_retry_' . uniqid();

        try {
            $response = Http::timeout(5)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Digisynk-Signature' => "t={$timestamp},v1={$signature}",
                    'Digisynk-Event-ID' => $eventId,
                    'Digisynk-Timestamp' => $timestamp,
                    'X-OriginPay-Request-Id' => $requestId,
                    'Digisynk-Request-ID' => $requestId,
                ])
                ->post($endpoint->url, $delivery->payload);

            $successful = $response->successful();

            $delivery->update([
                'status_code' => $response->status(),
                'response_body' => $response->body() ? substr($response->body(), 0, 1000) : null,
                'successful' => $successful,
            ]);

            if (!$successful) {
                $this->scheduleNextRetry($delivery);
            }

        } catch (\Exception $e) {
            $delivery->update([
                'status_code' => 0,
                'response_body' => substr($e->getMessage(), 0, 1000),
                'successful' => false,
            ]);

            $this->scheduleNextRetry($delivery);
        }
    }

    protected function scheduleNextRetry(WebhookDelivery $delivery)
    {
        // 1m, 5m, 15m, 30m, 1h, 6h, 24h
        $delays = [
            2 => 1,
            3 => 5,
            4 => 15,
            5 => 30,
            6 => 60,
            7 => 360,
            8 => 1440
        ];

        $attempt = $delivery->attempt + 1;

        if (array_key_exists($attempt, $delays)) {
            RetryWebhookDeliveryJob::dispatch($delivery->id)
                ->delay(now()->addMinutes($delays[$attempt]));
            
            $delivery->update(['next_retry_at' => now()->addMinutes($delays[$attempt])]);
        }
    }
}
