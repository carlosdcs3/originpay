<?php

namespace App\Services\Gateways\Adapters;

use App\Contracts\Gateways\GatewayInterface;
use App\Domain\Payments\GatewayAuthorizeRequest;
use App\Domain\Payments\GatewayResult;
use App\Domain\Payments\GatewayRuntimeConfig;
use App\Services\Gateways\Adapters\Efi\EfiHttpClient;
use Illuminate\Support\Str;

class EfiGatewayAdapter implements GatewayInterface
{
    public function __construct(
        private readonly EfiHttpClient $httpClient
    ) {}

    public function authorize(GatewayAuthorizeRequest $request): GatewayResult
    {
        if (!$request->runtimeConfig) {
            throw new \Exception("EFI Gateway configuration missing.");
        }

        $correlationId = 'req_' . Str::random(16);
        $client = $this->httpClient->makeClient($request->runtimeConfig, true, $correlationId);

        $payload = [
            'calendario' => [
                'expiracao' => 3600
            ],
            'valor' => [
                'original' => number_format($request->amount / 100, 2, '.', '')
            ],
            'chave' => $request->runtimeConfig->pixKey,
            'infoAdicionais' => [
                ['nome' => 'Charge ID', 'valor' => $request->chargeId]
            ]
        ];

        $startTime = microtime(true);
        $response = $client->post('/v2/cob', $payload);
        $processingTime = (int) ((microtime(true) - $startTime) * 1000);

        if ($response->status() === 401) {
            app(\App\Services\Gateways\Adapters\Efi\EfiOAuthService::class)->invalidateToken($request->runtimeConfig);
        }

        if ($response->failed()) {
            $isTechnical = $response->serverError() || $response->status() === 429;
            return new GatewayResult(
                success: false,
                status: 'failed',
                failureCode: (string) $response->status(),
                failureMessage: $response->json('mensagem') ?? 'EFI request failed.',
                rawResponse: [],
                processingTime: $processingTime,
                gatewayName: 'efi',
                isTechnicalFailure: $isTechnical
            );
        }

        $data = $response->json();

        return new GatewayResult(
            success: true,
            status: 'pending',
            gatewayReference: $data['txid'] ?? null,
            authorizationCode: null,
            rawResponse: [],
            processingTime: $processingTime,
            gatewayName: 'efi',
            metadata: [
                'txid' => $data['txid'] ?? null,
                'location_id' => $data['loc']['id'] ?? null,
                'pix_copy_paste' => $data['pixCopiaECola'] ?? null,
                'gateway_request_id' => $correlationId
            ],
            isTechnicalFailure: false
        );
    }

    public function capture(string $gatewayReference, int $amount): GatewayResult {
        return new GatewayResult(true, 'captured');
    }
    public function cancel(string $gatewayReference): GatewayResult {
        return new GatewayResult(true, 'cancelled');
    }
    public function refund(string $gatewayReference, int $amount): GatewayResult {
        return new GatewayResult(true, 'refunded');
    }
    public function getStatus(string $gatewayReference, ?GatewayRuntimeConfig $config = null): GatewayResult {
        if (!$config) {
            throw new \Exception("EFI Gateway configuration missing for status check.");
        }

        $client = $this->httpClient->makeClient($config, true);
        $response = $client->get("/v2/cob/{$gatewayReference}");

        if ($response->failed()) {
            return new GatewayResult(
                success: false,
                status: 'failed',
                failureCode: (string) $response->status(),
                failureMessage: 'Failed to fetch status from EFI.',
                rawResponse: [],
                gatewayName: 'efi',
                isTechnicalFailure: true
            );
        }

        $data = $response->json();
        $efiStatus = $data['status'] ?? 'UNKNOWN';

        $mappedStatus = match ($efiStatus) {
            'CONCLUIDA' => 'succeeded',
            'ATIVA' => 'pending',
            'REMOVIDA_PELO_USUARIO_RECEBEDOR', 'REMOVIDA_PELO_PSP' => 'cancelled',
            default => 'failed'
        };

        return new GatewayResult(
            success: $mappedStatus === 'succeeded',
            status: $mappedStatus,
            gatewayReference: $gatewayReference,
            rawResponse: [],
            gatewayName: 'efi',
            metadata: [
                'txid' => $gatewayReference,
                'efi_status' => $efiStatus
            ],
            isTechnicalFailure: false
        );
    }
    
    public function health(): bool {
        return true;
    }

    public function supportsCapture(): bool { return true; }
    public function supportsRefund(): bool { return true; }
    public function supportsPartialRefund(): bool { return true; }
    public function supportsPix(): bool { return true; }
    public function supportsCard(): bool { return true; }
    public function supportsWebhook(): bool { return true; }
    public function supportsRecurring(): bool { return true; }
    public function supportsSplit(): bool { return true; }

    public function canRetryOn(string $failureCode): bool {
        return in_array($failureCode, ['timeout', 'network_error', '429', '500']);
    }
}
