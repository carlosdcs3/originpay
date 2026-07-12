<?php

namespace App\Jobs;

use App\Enums\ChargeStatus;
use App\Enums\WebhookEventStatus;
use App\Gateway\GatewayManager;
use App\Models\Charge;
use App\Models\GatewayLog;
use App\Models\PaymentGateway;
use App\Models\WebhookDeadLetter;
use App\Models\WebhookEvent;
use App\Services\ChargeService;
use App\Support\Observability\CarriesOperationalContext;
use App\Support\Observability\Metrics\LocalMetricsCollector;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Request;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProcessGatewayWebhookJob implements ShouldQueue
{
    use CarriesOperationalContext;
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public $backoff = [10, 30, 60]; // Retry after 10s, then 30s, then 60s

    protected string $provider;

    protected array $payload;

    protected array $headers;

    protected ?int $webhookEventId;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(string $provider, array $payload, array $headers, ?int $webhookEventId = null)
    {
        $this->provider = $provider;
        $this->payload = $payload;
        $this->headers = $this->safeHeaders($headers);
        $this->webhookEventId = $webhookEventId;
        $this->captureOperationalContext([
            'gateway' => $provider,
            'webhook_event_id' => $webhookEventId,
            'payment_id' => $payload['payment_id'] ?? $payload['charge_id'] ?? null,
            'merchant_id' => $payload['merchant_id'] ?? null,
            'tenant_id' => $payload['tenant_id'] ?? null,
        ]);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(ChargeService $chargeService)
    {
        $startedAt = hrtime(true);
        $webhookEvent = $this->webhookEventId ? WebhookEvent::find($this->webhookEventId) : null;

        if ($webhookEvent && $webhookEvent->status === WebhookEventStatus::PROCESSED) {
            Log::info('Gateway webhook job skipped already processed event', [
                'webhook_event_id' => $webhookEvent->id,
                'event_id' => $webhookEvent->event_id,
            ]);

            return;
        }

        if ($webhookEvent) {
            $webhookEvent->update([
                'status' => WebhookEventStatus::PROCESSING,
                'attempts' => ((int) $webhookEvent->attempts) + 1,
                'last_error' => null,
            ]);
        }

        $gatewayModel = PaymentGateway::where('code', $this->provider)->first();

        if (! $gatewayModel) {
            throw new Exception("Provider not found: {$this->provider}");
        }

        // Recreate the Request object
        $request = Request::create(
            '/api/webhooks/gateway/'.$this->provider,
            'POST',
            $this->payload
        );
        foreach ($this->headers as $key => $values) {
            $request->headers->set($key, $values);
        }

        $adapter = GatewayManager::adapter($gatewayModel);

        // Let the adapter normalize the webhook
        $normalizedEvent = $adapter->handleWebhook($request);

        if ($normalizedEvent->status === ChargeStatus::PAID) {
            $charge = $this->findCharge($normalizedEvent->gatewayChargeId);
            if ($charge) {
                $chargeService->markAsPaid($charge, $normalizedEvent->gatewayEventId ?? 'webhook_'.Str::random(10));
            }
        } elseif ($normalizedEvent->status === ChargeStatus::EXPIRED) {
            $charge = $this->findCharge($normalizedEvent->gatewayChargeId);
            if ($charge) {
                $chargeService->expire($charge);
            }
        } elseif ($normalizedEvent->status === ChargeStatus::CANCELLED) {
            $charge = $this->findCharge($normalizedEvent->gatewayChargeId);
            if ($charge && $charge->status !== ChargeStatus::PAID) {
                $charge->status = ChargeStatus::CANCELLED;
                $charge->save();
            }
        }

        GatewayLog::logEvent(
            $this->provider,
            ['action' => 'webhook_received', 'payload' => $this->payload],
            ['status' => 'processed', 'normalized_status' => $normalizedEvent->status->value],
            200,
            null,
            $normalizedEvent->gatewayEventId
        );

        if ($webhookEvent) {
            $webhookEvent->update([
                'status' => WebhookEventStatus::PROCESSED,
                'processed_at' => now(),
                'last_error' => null,
            ]);

            WebhookDeadLetter::where('webhook_event_id', $webhookEvent->id)
                ->whereIn('status', ['pending', 'failed', 'reprocessing'])
                ->update(['status' => 'reprocessed']);
        }

        $labels = ['operation' => 'webhook_processing', 'gateway' => $this->provider, 'result' => 'success'];
        $this->recordMetrics($labels, $startedAt);
    }

    private function findCharge(string $gatewayReference): ?Charge
    {
        return Charge::where('gateway_charge_id', $gatewayReference)
            ->orWhere('gateway_reference', $gatewayReference)
            ->first();
    }

    /**
     * @param  array<string, mixed>  $headers
     * @return array<string, mixed>
     */
    private function safeHeaders(array $headers): array
    {
        $safe = [];
        $blocked = ['authorization', 'proxy-authorization', 'x-api-key', 'api-key', 'cookie', 'set-cookie', 'client_secret', 'client-secret'];

        foreach ($headers as $key => $value) {
            if (in_array(strtolower((string) $key), $blocked, true)) {
                continue;
            }

            $safe[$key] = $value;
        }

        return $safe;
    }

    private function headerValue(array $names): ?string
    {
        foreach ($this->headers as $key => $value) {
            if (! in_array(strtolower((string) $key), $names, true)) {
                continue;
            }

            if (is_array($value)) {
                return isset($value[0]) ? (string) $value[0] : null;
            }

            return is_scalar($value) ? (string) $value : null;
        }

        return null;
    }

    /**
     * Handle a job failure.
     *
     * @return void
     */
    public function failed(\Throwable $exception)
    {
        $this->recordMetrics([
            'operation' => 'webhook_processing',
            'gateway' => $this->provider,
            'result' => 'failure',
        ]);
        Log::error('ProcessGatewayWebhookJob failed: '.$exception->getMessage());

        GatewayLog::logEvent(
            $this->provider,
            ['action' => 'webhook_failed', 'payload' => $this->payload],
            ['error' => $exception->getMessage()],
            400,
            null,
            null
        );

        $signature = $this->headerValue(['x-webhook-signature', 'x-gateway-signature', 'x-hub-signature-256']);
        $timestamp = $this->headerValue(['x-webhook-timestamp', 'x-gateway-timestamp']);

        if ($this->webhookEventId) {
            WebhookEvent::whereKey($this->webhookEventId)->update([
                'status' => WebhookEventStatus::DEAD_LETTER,
                'failed_at' => now(),
                'last_error' => $exception->getMessage(),
            ]);
        }

        $deadLetterData = [
            'gateway_code' => $this->provider,
            'payload' => $this->payload,
            'headers' => $this->headers,
            'signature' => $signature,
            'provider_timestamp' => $timestamp,
            'received_at' => now(),
            'error_message' => $exception->getMessage(),
            'status' => 'pending',
        ];

        // Canonical DLQ for inbound gateway webhooks: webhook_dead_letters.
        if ($this->webhookEventId) {
            WebhookDeadLetter::updateOrCreate(
                ['webhook_event_id' => $this->webhookEventId],
                $deadLetterData
            );
        } else {
            WebhookDeadLetter::create($deadLetterData);
        }
    }

    /** @param array<string, string> $labels */
    private function recordMetrics(array $labels, ?int $startedAt = null): void
    {
        try {
            $metrics = app(LocalMetricsCollector::class);
            $metrics->increment('webhook_jobs_total', $labels);
            if ($startedAt !== null) {
                $metrics->observe('webhook_job_duration_ms', $labels, (hrtime(true) - $startedAt) / 1_000_000);
            }
        } catch (\Throwable) {
            // Metrics must never affect webhook processing.
        }
    }
}
