<?php

namespace App\Services\Gateways\Adapters;

use App\Contracts\Gateways\GatewayInterface;
use App\Domain\Payments\GatewayAuthorizeRequest;
use App\Domain\Payments\GatewayResult;
use Illuminate\Support\Str;

class SicoobGatewayAdapter implements GatewayInterface
{
    public function authorize(GatewayAuthorizeRequest $request): GatewayResult
    {
        // Sandbox Mock for Sicoob
        return new GatewayResult(
            success: true,
            status: 'authorized',
            gatewayReference: 'sicoob_' . Str::random(12),
            authorizationCode: 'auth_' . rand(1000, 9999),
            rawResponse: ['simulated' => true, 'provider' => 'sicoob'],
            processingTime: rand(20, 80),
            gatewayName: 'sicoob',
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

    public function supportsCapture(): bool { return false; }
    public function supportsRefund(): bool { return true; }
    public function supportsPartialRefund(): bool { return true; }
    public function supportsPix(): bool { return true; }
    public function supportsCard(): bool { return false; }
    public function supportsWebhook(): bool { return true; }
    public function supportsRecurring(): bool { return false; }
    public function supportsSplit(): bool { return false; }

    public function canRetryOn(string $failureCode): bool {
        return in_array($failureCode, ['timeout', 'network_error', '429', '500']);
    }
}
