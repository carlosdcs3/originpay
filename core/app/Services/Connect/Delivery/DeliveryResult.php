<?php
namespace App\Services\Connect\Delivery;

class DeliveryResult
{
    public readonly bool $success;
    public readonly string $provider;
    public readonly ?string $messageId;
    public readonly string $providerStatus;
    public readonly int $httpCode;
    public readonly int $latencyMs;
    public readonly ?string $errorCode;
    public readonly ?string $errorMessage;
    public readonly bool $isTransient;
    public readonly ?int $retryAfter;
    public readonly array $metadata;

    public function __construct(
        bool $success,
        string $provider,
        ?string $messageId,
        string $providerStatus,
        int $httpCode,
        int $latencyMs,
        ?string $errorCode = null,
        ?string $errorMessage = null,
        bool $isTransient = false,
        ?int $retryAfter = null,
        array $metadata = []
    ) {
        $this->success = $success;
        $this->provider = $provider;
        $this->messageId = $messageId;
        $this->providerStatus = $providerStatus;
        $this->httpCode = $httpCode;
        $this->latencyMs = $latencyMs;
        $this->errorCode = $errorCode;
        $this->errorMessage = $errorMessage;
        $this->isTransient = $isTransient;
        $this->retryAfter = $retryAfter;
        $this->metadata = $metadata;
    }
}
