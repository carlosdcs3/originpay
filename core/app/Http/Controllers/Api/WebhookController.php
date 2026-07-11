<?php

namespace App\Http\Controllers\Api;

use App\Enums\ApiErrorCode;
use App\Enums\ApiErrorType;
use App\Enums\WebhookEventStatus;
use App\Factories\ApiResponse;
use App\Http\Controllers\Controller;
use App\Jobs\ProcessWebhookJob;
use App\Models\WebhookEvent;
use App\Services\Gateway\GatewayManager;
use Exception;
use Illuminate\Http\Request;
use LogicException;

class WebhookController extends Controller
{
    protected $gatewayManager;

    public function __construct(GatewayManager $gatewayManager)
    {
        $this->gatewayManager = $gatewayManager;
    }

    public function handle(Request $request, string $gateway)
    {
        try {
            $validator = $this->gatewayManager->webhookValidator($gateway);

            if (! $validator->validate($request->all(), $request->headers->all())) {
                return ApiResponse::unauthorized();
            }

            $webhookData = $validator->normalize($request->all());
            $payload = json_encode($request->all());
            $headers = json_encode($request->headers->all());

            try {
                $webhookEvent = WebhookEvent::firstOrCreate(
                    [
                        'provider' => strtoupper($gateway),
                        'event_id' => $webhookData->event_id,
                    ],
                    [
                        'external_reference' => $webhookData->provider_reference,
                        'event_type' => $webhookData->event_type,
                        'payload' => $payload,
                        'headers' => $headers,
                        'status' => WebhookEventStatus::RECEIVED,
                    ]
                );

                if (! $webhookEvent->wasRecentlyCreated && $webhookEvent->status !== WebhookEventStatus::PROCESSED) {
                    $webhookEvent->fill([
                        'external_reference' => $webhookData->provider_reference,
                        'event_type' => $webhookData->event_type,
                        'payload' => $payload,
                        'headers' => $headers,
                        'status' => WebhookEventStatus::RECEIVED,
                        'last_error' => null,
                    ]);
                    $webhookEvent->save();
                }

                ProcessWebhookJob::dispatch($webhookEvent)->onQueue('high');

                return response()->json(['message' => 'Event received successfully'], 200);
            } catch (\Illuminate\Database\QueryException $error) {
                if (($error->errorInfo[1] ?? null) == 1062) {
                    return response()->json(['message' => 'Event already processed.'], 200);
                }

                throw $error;
            }
        } catch (LogicException $error) {
            return ApiResponse::error(ApiErrorType::INVALID_REQUEST, ApiErrorCode::INVALID_PARAMETERS, 'The request is invalid.', 400);
        } catch (Exception $error) {
            $message = strtolower($error->getMessage());

            if (str_contains($message, 'not implemented') || str_contains($message, 'não implementado') || str_contains($message, 'nao implementado')) {
                return ApiResponse::error(ApiErrorType::INVALID_REQUEST, ApiErrorCode::INVALID_PARAMETERS, 'The request is invalid.', 400);
            }

            return ApiResponse::internal();
        }
    }
}
