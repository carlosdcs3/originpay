<?php

namespace App\Http\Controllers\Webhook;

use App\Enums\ApiErrorCode;
use App\Enums\ApiErrorType;
use App\Enums\WebhookEventStatus;
use App\Exceptions\GatewayWebhookValidationException;
use App\Factories\ApiResponse;
use App\Http\Controllers\Controller;
use App\Jobs\ProcessGatewayWebhookJob;
use App\Models\PaymentGateway;
use App\Models\WebhookDeadLetter;
use App\Models\WebhookEvent;
use App\Services\ChargeService;
use App\Services\GatewayMetricsService;
use App\Services\GatewayWebhookValidationService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GatewayWebhookController extends Controller
{
    protected ChargeService $chargeService;

    protected GatewayWebhookValidationService $webhookValidation;

    public function __construct(ChargeService $chargeService, GatewayWebhookValidationService $webhookValidation)
    {
        $this->chargeService = $chargeService;
        $this->webhookValidation = $webhookValidation;
    }

    public function handle(Request $request, string $provider)
    {
        $gatewayModel = PaymentGateway::where('code', $provider)->first();
        $requestId = $request->header('X-Request-ID') ?: (string) Str::uuid();
        $correlationId = $request->header('X-Correlation-ID') ?: $requestId;

        if (! $gatewayModel) {
            $this->logRejection($provider, $requestId, $correlationId, 'provider_not_found');

            return ApiResponse::notFound();
        }

        $metricsService = app(GatewayMetricsService::class);
        $startTime = microtime(true);

        try {
            $payload = $this->webhookValidation->validate($gatewayModel, $request);
            $rawPayload = $request->getContent();
            $headers = $request->headers->all();
            $eventId = $this->extractEventId($payload, $rawPayload);
            $payloadHash = hash('sha256', $rawPayload);
            $webhookEvent = WebhookEvent::firstOrCreate(
                ['provider' => strtoupper($provider), 'event_id' => $eventId],
                [
                    'external_reference' => $payload['txid'] ?? $payload['id'] ?? $payload['charge_id'] ?? null,
                    'event_type' => $payload['event'] ?? $payload['type'] ?? 'gateway.webhook',
                    'payload' => $rawPayload,
                    'headers' => json_encode($headers),
                    'payload_hash' => $payloadHash,
                    'correlation_id' => $correlationId,
                    'status' => WebhookEventStatus::RECEIVED,
                    'metadata' => [
                        'source' => 'gateway_webhook_controller',
                        'request_id' => $requestId,
                    ],
                ]
            );

            if (! $webhookEvent->wasRecentlyCreated) {
                $metricsService->increment('webhook_duplicate_blocked');
                Log::channel('webhooks')->info('Gateway webhook replay/duplicate blocked', [
                    'provider' => $provider,
                    'event_id' => $eventId,
                    'correlation_id' => $webhookEvent->correlation_id ?? $correlationId,
                ]);

                return response()->json(['status' => 'received', 'duplicate' => true]);
            }

            ProcessGatewayWebhookJob::dispatch(
                $provider,
                $payload,
                $headers,
                $webhookEvent->id
            )->onQueue('webhooks_ingestion');

            $enqueueTime = (int) round((microtime(true) - $startTime) * 1000);
            $metricsService->increment('webhook_enqueued');
            $metricsService->recordLatency('webhook_enqueue_latency', $enqueueTime);

            return response()->json(['status' => 'received']);

        } catch (GatewayWebhookValidationException $e) {
            $metricsService->increment('webhook_rejected');
            $this->logRejection($provider, $requestId, $correlationId, $e->getMessage());

            return ApiResponse::error(
                ApiErrorType::INVALID_REQUEST,
                ApiErrorCode::INVALID_PARAMETERS,
                'The request is invalid.',
                $e->statusCode()
            );

        } catch (Exception $e) {
            Log::channel('webhooks')->error('Webhook ingestion failed', [
                'provider' => $provider,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            // If dispatching fails (e.g. Redis is down)
            $metricsService->increment('webhook_enqueue_failed');

            return ApiResponse::internal();
        }
    }

    /**
     * Reprocessa um webhook que caiu na Dead Letter Queue (Admin).
     */
    public function reprocess(Request $request, $id)
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        $deadLetter = WebhookDeadLetter::findOrFail($id);
        $before = $deadLetter->only(['status', 'error_message', 'webhook_event_id', 'gateway_code']);

        try {
            $headers = $deadLetter->headers ?? [];

            ProcessGatewayWebhookJob::dispatch(
                $deadLetter->gateway_code,
                $deadLetter->payload,
                $headers,
                $deadLetter->webhook_event_id
            )->onQueue('webhooks_ingestion');

            $deadLetter->update(['status' => 'reprocessing', 'error_message' => null]);

            Log::channel('audit')->info('Sensitive admin action', [
                'action' => 'webhooks.dlq.reprocess',
                'user' => optional($request->user('admin'))->only(['id', 'name', 'email']),
                'timestamp' => now()->toIso8601String(),
                'reason' => $validated['reason'],
                'resource' => 'webhook_dead_letters:'.$deadLetter->id,
                'change' => [
                    'before' => $before,
                    'after' => $deadLetter->fresh()->only(['status', 'error_message', 'webhook_event_id', 'gateway_code']),
                ],
                'ip' => $request->ip(),
                'correlation_id' => $request->header('X-Correlation-ID') ?: $request->attributes->get('correlation_id') ?: $request->header('X-Request-ID'),
            ]);

            return response()->json(['message' => 'Webhook enviado para reprocessamento.']);

        } catch (Exception $e) {
            Log::channel('webhooks')->error('Webhook reprocess failed', [
                'dead_letter_id' => $id,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            $deadLetter->update([
                'status' => 'failed',
                'error_message' => 'Falha no Reprocessamento: '.$e->getMessage(),
            ]);

            return ApiResponse::error(
                ApiErrorType::INVALID_REQUEST,
                ApiErrorCode::INVALID_PARAMETERS,
                'The request is invalid.',
                400
            );
        }
    }

    private function extractEventId(array $payload, string $rawPayload): string
    {
        foreach (['event_id', 'id', 'webhook_id', 'txid', 'charge_id', 'endToEndId'] as $key) {
            if (! empty($payload[$key]) && is_scalar($payload[$key])) {
                return (string) $payload[$key];
            }
        }

        if (isset($payload['pix'][0]) && is_array($payload['pix'][0])) {
            foreach (['txid', 'endToEndId', 'id'] as $key) {
                if (! empty($payload['pix'][0][$key]) && is_scalar($payload['pix'][0][$key])) {
                    return (string) $payload['pix'][0][$key];
                }
            }
        }

        return 'payload_'.hash('sha256', $rawPayload);
    }

    private function logRejection(string $provider, string $requestId, string $correlationId, string $reason): void
    {
        Log::channel('webhooks')->warning('Gateway webhook rejected', [
            'provider' => $provider,
            'request_id' => $requestId,
            'correlation_id' => $correlationId,
            'reason' => $reason,
        ]);
    }
}
