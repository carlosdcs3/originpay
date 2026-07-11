<?php

namespace App\Contracts\Gateways;

use App\Domain\Payments\GatewayResult;
use App\Domain\Payments\GatewayAuthorizeRequest;
use App\Domain\Payments\GatewayRuntimeConfig;

interface GatewayInterface
{
    public function authorize(GatewayAuthorizeRequest $request): GatewayResult;
    public function capture(string $gatewayReference, int $amount): GatewayResult;
    public function cancel(string $gatewayReference): GatewayResult;
    public function refund(string $gatewayReference, int $amount): GatewayResult;
    public function getStatus(string $gatewayReference, ?GatewayRuntimeConfig $config = null): GatewayResult;
    
    public function health(): bool;
    
    // Capabilities
    public function supportsCapture(): bool;
    public function supportsRefund(): bool;
    public function supportsPartialRefund(): bool;
    public function supportsPix(): bool;
    public function supportsCard(): bool;
    public function supportsWebhook(): bool;
    public function supportsRecurring(): bool;
    public function supportsSplit(): bool;

    // Retry Policy
    public function canRetryOn(string $failureCode): bool;
}
