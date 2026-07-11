<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Http\Request;
use App\Models\PaymentGateway;
use App\Gateway\GatewayManager;
use App\Services\ChargeService;
use Exception;
use Illuminate\Support\Facades\Log;

class ProcessGatewayWebhookJob implements ShouldQueue
{
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
        $this->headers = $headers;
        $this->webhookEventId = $webhookEventId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(ChargeService $chargeService)
    {
        $webhookEvent = $this->webhookEventId ? \App\Models\WebhookEvent::find($this->webhookEventId) : null;

        if ($webhookEvent && $webhookEvent->status === \App\Enums\WebhookEventStatus::PROCESSED) {
            Log::info('Gateway webhook job skipped already processed event', [
                'webhook_event_id' => $webhookEvent->id,
                'event_id' => $webhookEvent->event_id,
            ]);
            return;
        }

        if ($webhookEvent) {
            $webhookEvent->update([
                'status' => \App\Enums\WebhookEventStatus::PROCESSING,
                'attempts' => ((int) $webhookEvent->attempts) + 1,
                'last_error' => null,
            ]);
        }

        $gatewayModel = PaymentGateway::where('code', $this->provider)->first();

        if (!$gatewayModel) {
            throw new Exception("Provider not found: {$this->provider}");
        }

        // Recreate the Request object
        $request = Request::create(
            '/api/webhooks/gateway/' . $this->provider, 
            'POST', 
            $this->payload
        );
        foreach ($this->headers as $key => $values) {
            $request->headers->set($key, $values);
        }

        $adapter = GatewayManager::adapter($gatewayModel);
        
        // Let the adapter normalize the webhook
        $normalizedEvent = $adapter->handleWebhook($request);

        if ($normalizedEvent->status === \App\Enums\ChargeStatus::PAID) {
            $charge = $this->findCharge($normalizedEvent->gatewayChargeId);
            if ($charge) {
                $chargeService->markAsPaid($charge, $normalizedEvent->gatewayEventId ?? 'webhook_' . \Illuminate\Support\Str::random(10));
            }
        } elseif ($normalizedEvent->status === \App\Enums\ChargeStatus::EXPIRED) {
            $charge = $this->findCharge($normalizedEvent->gatewayChargeId);
            if ($charge) {
                $chargeService->expire($charge);
            }
        } elseif ($normalizedEvent->status === \App\Enums\ChargeStatus::CANCELLED) {
            $charge = $this->findCharge($normalizedEvent->gatewayChargeId);
            if ($charge && $charge->status !== \App\Enums\ChargeStatus::PAID) {
                $charge->status = \App\Enums\ChargeStatus::CANCELLED;
                $charge->save();
            }
        }

        \App\Models\GatewayLog::logEvent(
            $this->provider, 
            ['action' => 'webhook_received', 'payload' => $this->payload], 
            ['status' => 'processed', 'normalized_status' => $normalizedEvent->status->value],
            200, 
            null, 
            $normalizedEvent->gatewayEventId
        );

        if ($webhookEvent) {
            $webhookEvent->update([
                'status' => \App\Enums\WebhookEventStatus::PROCESSED,
                'processed_at' => now(),
                'last_error' => null,
            ]);

            \App\Models\WebhookDeadLetter::where('webhook_event_id', $webhookEvent->id)
                ->whereIn('status', ['pending', 'failed', 'reprocessing'])
                ->update(['status' => 'reprocessed']);
        }
    }

    private function findCharge(string $gatewayReference): ?\App\Models\Charge
    {
        return \App\Models\Charge::where('gateway_charge_id', $gatewayReference)
            ->orWhere('gateway_reference', $gatewayReference)
            ->first();
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
     * @param  \Throwable  $exception
     * @return void
     */
    public function failed(\Throwable $exception)
    {
        Log::error("ProcessGatewayWebhookJob failed: " . $exception->getMessage());

        \App\Models\GatewayLog::logEvent(
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
            \App\Models\WebhookEvent::whereKey($this->webhookEventId)->update([
                'status' => \App\Enums\WebhookEventStatus::DEAD_LETTER,
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
            \App\Models\WebhookDeadLetter::updateOrCreate(
                ['webhook_event_id' => $this->webhookEventId],
                $deadLetterData
            );
        } else {
            \App\Models\WebhookDeadLetter::create($deadLetterData);
        }
    }
}
