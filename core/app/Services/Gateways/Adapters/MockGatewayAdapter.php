<?php

namespace App\Services\Gateways\Adapters;

use App\Contracts\Gateways\GatewayInterface;
use App\Domain\Payments\GatewayAuthorizeRequest;
use App\Domain\Payments\GatewayResult;
use Illuminate\Support\Str;

class MockGatewayAdapter implements GatewayInterface
{
    public function authorize(GatewayAuthorizeRequest $request): GatewayResult
    {
        $mockResultStr = $request->merchantMetadata['mock_result'] ?? 'succeeded';
        
        $isDecline = ($mockResultStr === 'failed' || $request->amount === 999);
        $isTechnical = ($mockResultStr === 'timeout' || $mockResultStr === 'network_error');

        if ($isTechnical) {
            return new GatewayResult(
                success: false,
                status: 'failed',
                failureCode: $mockResultStr,
                failureMessage: 'Simulated technical failure.',
                rawResponse: ['simulated' => true],
                processingTime: rand(10, 50),
                gatewayName: 'mock',
                isTechnicalFailure: true
            );
        }

        if ($isDecline) {
            return new GatewayResult(
                success: false,
                status: 'declined',
                failureCode: 'mock_decline',
                failureMessage: 'Card declined by mock gateway',
                rawResponse: ['simulated' => true, 'decline_reason' => 'insufficient_funds'],
                processingTime: rand(10, 50),
                gatewayName: 'mock',
                isTechnicalFailure: false
            );
        }

        return new GatewayResult(
            success: true,
            status: 'authorized',
            gatewayReference: 'gw_' . Str::random(12),
            authorizationCode: 'auth_' . rand(1000, 9999),
            rawResponse: ['simulated' => true, 'timestamp' => time()],
            processingTime: rand(10, 50),
            gatewayName: 'mock',
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
    public function getStatus(string $gatewayReference, ?\App\Domain\Payments\GatewayRuntimeConfig $config = null): GatewayResult {
        return new GatewayResult(true, 'authorized');
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
