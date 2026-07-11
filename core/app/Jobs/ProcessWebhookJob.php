<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Models\WebhookEvent;
use App\Models\WebhookDlq;
use App\Payment\Modern\ModernPaymentGatewayFactory;
use App\Services\TransactionService;
use App\Enums\ProviderType;
use App\Enums\WebhookEventStatus;
use App\Helpers\MaskHelper;
use Illuminate\Http\Request;

class ProcessWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public WebhookEvent $event;

    // Retry 3 times
    public $tries = 3;

    // Backoff in seconds: 5s, 10s, 20s
    public function backoff()
    {
        return [5, 10, 20];
    }

    public function __construct(WebhookEvent $event)
    {
        $this->event = $event;
    }

    public function handle(ModernPaymentGatewayFactory $factory, TransactionService $transactionService)
    {
        // 1. Check if already PROCESSED
        if ($this->event->status === WebhookEventStatus::PROCESSED) {
            return;
        }

        $lockKey = 'webhook_lock_' . $this->event->provider . '_' . $this->event->event_id;

        // Acquire lock for 30 seconds
        $lock = Cache::lock($lockKey, 30);

        if (!$lock->get()) {
            // Cannot acquire lock, release back to queue with delay
            $this->release(5);
            return;
        }

        try {
            $startTime = microtime(true);
            $this->event->status = WebhookEventStatus::PROCESSING;
            $this->event->attempts += 1;
            $this->event->save();

            $providerType = ProviderType::tryFrom(strtoupper($this->event->provider));
            if (!$providerType) {
                throw new \RuntimeException("Provider not implemented: {$this->event->provider}");
            }

            $gateway = $factory->getGateway($providerType);

            // Reconstruct Request for parseWebhook
            $request = Request::create('/webhook', 'POST', [], [], [], [], $this->event->payload);
            
            $webhookDTO = $gateway->parseWebhook($request);

            // Process financial logic
            $transactionService->processModernWebhook($webhookDTO, $providerType);

            // Mark as PROCESSED
            $this->event->status = WebhookEventStatus::PROCESSED;
            $this->event->processed_at = now();
            $this->event->last_error = null;
            $this->event->save();

            // Resolve DLQ if this was a Replay
            if (!empty($this->event->metadata['original_dlq_id'])) {
                $dlq = WebhookDlq::find($this->event->metadata['original_dlq_id']);
                if ($dlq) {
                    $dlq->resolved_at = now();
                    $dlq->save();
                }
            }

            $latencyMs = round((microtime(true) - $startTime) * 1000, 2);
            $metrics = app(\App\Services\GatewayMetricsService::class);
            $metrics->recordLatency('process_webhook_job_duration_ms', $latencyMs);
            $metrics->increment('webhook_processed_total');

        } catch (\Exception $e) {
            $this->event->last_error = $e->getMessage();
            $this->event->save();
            throw $e; // Will trigger retry mechanism
        } finally {
            $lock->release();
        }
    }

    public function failed(\Throwable $exception)
    {
        // Max retries exhausted, send to DLQ
        $this->event->status = WebhookEventStatus::FAILED;
        $this->event->last_error = $exception->getMessage();
        $this->event->save();

        WebhookDlq::create([
            'provider' => $this->event->provider,
            'event_id' => $this->event->event_id,
            'external_reference' => $this->event->external_reference,
            'payload' => MaskHelper::maskString($this->event->payload),
            'headers' => MaskHelper::maskString($this->event->headers ?? '{}'),
            'error_message' => $exception->getMessage(),
            'error_class' => get_class($exception),
            'attempts' => $this->event->attempts,
        ]);
        
        app(\App\Services\GatewayMetricsService::class)->increment('webhook_dlq_total');
        Log::channel('gateway')->error("Webhook event {$this->event->id} moved to DLQ.");
    }
}
