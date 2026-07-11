<?php

namespace App\Payment\Modern\Providers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Payment\Modern\ModernPaymentGatewayInterface;
use App\Payment\Modern\DTO\DepositDTO;
use App\Payment\Modern\DTO\WebhookDTO;
use App\Payment\Modern\DTO\RefundDTO;
use App\Payment\Modern\DTO\WithdrawDTO;
use App\Payment\Modern\DTO\GatewayResponseDTO;
use App\Payment\Modern\DTO\GatewayTransactionDTO;
use App\Helpers\MaskHelper;
use Exception;

class NewProviderGateway implements ModernPaymentGatewayInterface
{
    private string $secret;
    private string $apiKey;
    private string $baseUrl;
    private \App\Services\GatewayRolloutService $rolloutService;
    private \App\Services\GatewayMetricsService $metricsService;

    public function __construct()
    {
        $this->secret = config('services.new_provider.webhook_secret', 'sandbox_secret');
        $this->apiKey = config('services.new_provider.api_key', 'sandbox_key');
        $this->baseUrl = config('services.new_provider.base_url', 'https://api.sandbox.newprovider.com');
        $this->rolloutService = app(\App\Services\GatewayRolloutService::class);
        $this->metricsService = app(\App\Services\GatewayMetricsService::class);
    }

    private function checkOutboundLocks(): void
    {
        if ($this->rolloutService->isKillSwitchActive()) {
            throw new Exception("Kill Switch: Outbound calls for NEW_PROVIDER are blocked.");
        }
        
        if (Cache::get('new_provider_circuit_breaker') === 'OFFLINE') {
            $this->metricsService->alertIncident('CIRCUIT_BREAKER_OFFLINE', 'Circuit breaker is blocking outbound call');
            throw new Exception("Circuit Breaker: Provider NEW_PROVIDER is currently OFFLINE.");
        }
    }

    private function getHttpClient()
    {
        return Http::withToken($this->apiKey)
            ->timeout(5)
            ->connectTimeout(2)
            ->retry(3, 100, function ($exception, $request) {
                if ($exception instanceof \Illuminate\Http\Client\RequestException) {
                    $status = $exception->response->status();
                    // Retry only on 429 and 5xx
                    if ($status === 429 || $status >= 500) {
                        return true;
                    }
                }
                if ($exception instanceof \Illuminate\Http\Client\ConnectionException) {
                    return true;
                }
                return false;
            });
    }

    private function logAction(string $action, string $internalId, ?\Illuminate\Http\Client\Response $response = null, array $extra = [])
    {
        $logData = array_merge([
            'action' => $action,
            'provider' => 'NEW_PROVIDER',
            'internal_id' => $internalId,
        ], $extra);

        if ($response) {
            $logData['status'] = $response->status();
            // Assuming response is fast enough. Latency logic would need start time.
            if ($response->successful()) {
                Log::channel('gateway')->info('Gateway Action Success', MaskHelper::maskSensitiveData($logData));
            } else {
                Log::channel('gateway')->error('Gateway Action Failed', MaskHelper::maskSensitiveData($logData));
            }
        } else {
            Log::channel('gateway')->info('Gateway Action Initiated', MaskHelper::maskSensitiveData($logData));
        }
    }

    public function createDeposit(DepositDTO $dto): GatewayResponseDTO
    {
        $this->checkOutboundLocks();
        $this->logAction('createDeposit', $dto->internalTrxId);
        $this->metricsService->logMetric('deposit_created', ['internal_trx_id' => $dto->internalTrxId]);

        try {
            $response = $this->getHttpClient()
                ->withHeaders(['Idempotency-Key' => hash('sha256', $dto->internalTrxId . 'DEPOSIT')])
                ->post($this->baseUrl . '/v1/deposits', [
                    'amount' => $dto->amount,
                    'currency' => $dto->currency,
                    'reference' => $dto->internalTrxId,
                ]);

            $this->logAction('createDeposit_done', $dto->internalTrxId, $response);

            if ($response->successful()) {
                return new GatewayResponseDTO(true, $response->json('id'), $response->json('redirect_url'));
            }
            return new GatewayResponseDTO(false, null, null, null, $response->body());
        } catch (Exception $e) {
            Log::channel('gateway')->error("createDeposit Exception", ['message' => $e->getMessage()]);
            // Safe failure
            return new GatewayResponseDTO(false, null, null, null, $e->getMessage());
        }
    }

    public function createPix(DepositDTO $dto): GatewayResponseDTO
    {
        $this->checkOutboundLocks();
        $this->metricsService->logMetric('deposit_created', ['internal_trx_id' => $dto->internalTrxId]);
        return new GatewayResponseDTO(true, 'SANDBOX_PIX_' . uniqid(), null, 'qr_code_string');
    }

    public function createCheckout(DepositDTO $dto): GatewayResponseDTO
    {
        $this->checkOutboundLocks();
        $this->metricsService->logMetric('deposit_created', ['internal_trx_id' => $dto->internalTrxId]);
        return new GatewayResponseDTO(true, 'SANDBOX_CHK_' . uniqid(), 'https://sandbox.newprovider.com/checkout/' . uniqid());
    }

    public function verifyWebhook(Request $request): bool
    {
        // Webhooks don't check circuit breaker (allow inbound)
        $signature = $request->header('X-NewProvider-Signature');
        $timestamp = $request->header('X-NewProvider-Timestamp');

        if (!$signature || !$timestamp) {
            return false;
        }

        if (abs(time() - (int)$timestamp) > 300) {
            return false;
        }

        $payload = $request->getContent();
        $signedPayload = $timestamp . '.' . $payload;
        
        $expectedSignature = hash_hmac('sha256', $signedPayload, $this->secret);

        return hash_equals($expectedSignature, $signature);
    }

    public function parseWebhook(Request $request): WebhookDTO
    {
        $data = json_decode($request->getContent(), true);

        $mappedStatus = match($data['status'] ?? '') {
            'PAID' => 'PAID',
            'PROCESSING' => 'PROCESSING',
            'FAILED' => 'FAILED',
            'REFUNDED' => 'REFUNDED',
            'CHARGEBACK' => 'CHARGEBACK',
            default => 'UNKNOWN'
        };

        return new WebhookDTO(
            providerTransactionId: $data['id'] ?? '',
            externalReference: $data['reference'] ?? null,
            status: $mappedStatus,
            amount: (float) ($data['amount'] ?? 0),
            currency: $data['currency'] ?? 'USD',
            metadata: $data['metadata'] ?? null,
            rawPayload: $data
        );
    }

    public function refund(RefundDTO $dto): GatewayResponseDTO
    {
        $this->checkOutboundLocks();
        $this->logAction('refund', $dto->providerTransactionId);
        $this->metricsService->logMetric('refund_count', ['provider_trx_id' => $dto->providerTransactionId]);

        try {
            $response = $this->getHttpClient()
                ->withHeaders(['Idempotency-Key' => hash('sha256', $dto->providerTransactionId . 'REFUND')])
                ->post($this->baseUrl . '/v1/refunds', [
                    'transaction_id' => $dto->providerTransactionId,
                    'amount' => $dto->amount,
                ]);

            $this->logAction('refund_done', $dto->providerTransactionId, $response);

            if ($response->successful()) {
                return new GatewayResponseDTO(true, $response->json('id'));
            }
            return new GatewayResponseDTO(false, null, null, null, $response->body());
        } catch (Exception $e) {
            return new GatewayResponseDTO(false, null, null, null, $e->getMessage());
        }
    }

    public function withdraw(WithdrawDTO $dto): GatewayResponseDTO
    {
        $this->checkOutboundLocks();
        $this->logAction('withdraw', $dto->internalTrxId);
        $this->metricsService->logMetric('withdraw_count', ['internal_trx_id' => $dto->internalTrxId]);

        try {
            $response = $this->getHttpClient()
                ->withHeaders(['Idempotency-Key' => hash('sha256', $dto->internalTrxId . 'WITHDRAW')])
                ->post($this->baseUrl . '/v1/withdraws', [
                    'amount' => $dto->amount,
                    'account' => MaskHelper::maskSensitiveData(['account' => $dto->destinationAccount])['account'] ?? $dto->destinationAccount, // just mask it
                ]);

            $this->logAction('withdraw_done', $dto->internalTrxId, $response);

            if ($response->successful()) {
                return new GatewayResponseDTO(true, $response->json('id'));
            }
            return new GatewayResponseDTO(false, null, null, null, $response->body());
        } catch (Exception $e) {
            return new GatewayResponseDTO(false, null, null, null, $e->getMessage());
        }
    }

    public function getTransaction(string $providerTrxId): GatewayTransactionDTO
    {
        return new GatewayTransactionDTO($providerTrxId, 'PAID', 100.00, 'USD');
    }

    public function healthCheck(): string
    {
        try {
            $response = Http::timeout(3)->get($this->baseUrl . '/health');
            if ($response->successful()) {
                Cache::forget('new_provider_circuit_breaker');
                return 'CONNECTED';
            }
            Cache::put('new_provider_circuit_breaker', 'OFFLINE', now()->addMinutes(2));
            return 'OFFLINE';
        } catch (Exception $e) {
            Cache::put('new_provider_circuit_breaker', 'OFFLINE', now()->addMinutes(2));
            return 'OFFLINE';
        }
    }
}
