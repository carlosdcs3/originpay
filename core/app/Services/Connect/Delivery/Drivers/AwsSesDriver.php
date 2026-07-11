<?php
namespace App\Services\Connect\Delivery\Drivers;

use App\Services\Connect\Delivery\ProviderInterface;
use App\Services\Connect\Delivery\DeliveryResult;
use Illuminate\Support\Str;

class AwsSesDriver implements ProviderInterface
{
    public function send(array $payload, array $credentials, array $config): DeliveryResult
    {
        usleep(150000); // Latency mock 150ms
        
        // Simulating standard delivery
        return new DeliveryResult(
            success: true,
            provider: 'aws_ses',
            messageId: 'ses-' . Str::uuid(),
            providerStatus: 'accepted',
            httpCode: 200,
            latencyMs: 150
        );
    }

    public function supports(string $messageType): bool { return true; }
    
    public function testConnection(array $credentials, array $config): array
    {
        return ['success' => true, 'latency' => 120, 'provider_version' => 'v2', 'account_name' => 'SES Production'];
    }
}
