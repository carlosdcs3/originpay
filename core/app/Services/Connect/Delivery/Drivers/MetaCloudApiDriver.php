<?php
namespace App\Services\Connect\Delivery\Drivers;

use App\Services\Connect\Delivery\ProviderInterface;
use App\Services\Connect\Delivery\DeliveryResult;
use Illuminate\Support\Str;

class MetaCloudApiDriver implements ProviderInterface
{
    public function send(array $payload, array $credentials, array $config): DeliveryResult
    {
        usleep(300000); // 300ms latency meta
        
        // Simulating transient failure (e.g., rate limit or network glitch) 5% of time
        if (rand(1,100) > 95) {
            return new DeliveryResult(
                success: false,
                provider: 'meta_cloud',
                messageId: null,
                providerStatus: 'error',
                httpCode: 429,
                latencyMs: 300,
                errorCode: 'RATE_LIMIT',
                errorMessage: 'Too many messages',
                isTransient: true,
                retryAfter: 60
            );
        }

        return new DeliveryResult(
            success: true,
            provider: 'meta_cloud',
            messageId: 'wamid.' . Str::uuid(),
            providerStatus: 'sent',
            httpCode: 200,
            latencyMs: 300
        );
    }

    public function supports(string $messageType): bool { return true; }
    public function testConnection(array $credentials, array $config): array { return ['success' => true, 'provider_version' => 'v19.0']; }
}
