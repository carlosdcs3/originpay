<?php

namespace App\Http\Controllers\Gateway;

use App\Factories\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Enums\ProviderType;
use App\Payment\Modern\ModernPaymentGatewayFactory;
use App\Services\TransactionService;
use App\Models\WebhookEvent;
use App\Jobs\ProcessWebhookJob;
use App\Enums\WebhookEventStatus;
use Illuminate\Support\Facades\Log;

class ModernWebhookController extends Controller
{
    protected ModernPaymentGatewayFactory $factory;
    protected TransactionService $transactionService;

    public function __construct(ModernPaymentGatewayFactory $factory, TransactionService $transactionService)
    {
        $this->factory = $factory;
        $this->transactionService = $transactionService;
    }

    /**
     * Webhook global unificado para todos os novos provedores.
     */
    public function handle(Request $request, string $providerStr)
    {
        $providerType = ProviderType::tryFrom(strtoupper($providerStr));

        if (!$providerType) {
            Log::channel('webhooks')->warning("Webhook received for unknown provider: {$providerStr}");
            return ApiResponse::validation([]);
        }

        try {
            $gateway = $this->factory->getGateway($providerType);

            if (!$gateway->verifyWebhook($request)) {
                Log::channel('webhooks')->warning("Invalid webhook signature for provider: {$providerType->value}");
                return ApiResponse::unauthorized();
            }

            // Quick extraction of event_id and reference for the event table
            $payloadData = json_decode($request->getContent(), true);
            $eventId = $payloadData['id'] ?? null;
            $reference = $payloadData['reference'] ?? null;
            
            // To handle fallback unique logic
            $finalEventId = $eventId ?: ('ref_' . $reference . '_' . uniqid());

            // Check duplicate to prevent error 500 on unique constraint
            $existing = WebhookEvent::where('provider', $providerType->value)
                                    ->where('event_id', $finalEventId)
                                    ->first();
            if ($existing) {
                app(\App\Contracts\Metrics\OperationalMetricsServiceInterface::class)->increment('webhook_duplicated'); \Illuminate\Support\Facades\Log::channel('webhooks')->info('webhook_descartado_idempotencia', [
                    'provider' => $providerType->value,
                    'event_id' => $finalEventId,
                ]);
                return response()->json(['status' => 'success', 'message' => 'Already received']);
            }

            $metrics = app(\App\Services\GatewayMetricsService::class);
            $metrics->increment('webhook_received_total');
            $startTime = microtime(true);

            $event = WebhookEvent::create([
                'provider' => $providerType->value,
                'event_id' => $finalEventId,
                'external_reference' => $reference,
                'payload' => $request->getContent(),
                'headers' => json_encode($request->headers->all()),
                'status' => WebhookEventStatus::RECEIVED,
            ]);

            ProcessWebhookJob::dispatch($event)->onQueue('high');

            app(\App\Contracts\Metrics\OperationalMetricsServiceInterface::class)->increment('webhook_received'); \Illuminate\Support\Facades\Log::channel('webhooks')->info('webhook_recebido_enfileirado', [
                'provider' => $providerType->value,
                'event_id' => $finalEventId,
            ]);

            $latencyMs = round((microtime(true) - $startTime) * 1000, 2);
            $metrics->recordLatency('webhook_processing_latency_ms', $latencyMs);

            return response()->json(['status' => 'success']);

        } catch (\Exception $e) {
            Log::channel('webhooks')->error("Webhook Controller Error for {$providerType->value}: " . $e->getMessage());
            app(\App\Services\GatewayMetricsService::class)->increment('webhook_failed_total');

            if (str_contains($e->getMessage(), 'adapter not implemented for provider')) {
                return ApiResponse::validation([]);
            }

            return ApiResponse::internal();
        }
    }
}
