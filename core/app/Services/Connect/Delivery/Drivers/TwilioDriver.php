<?php
namespace App\Services\Connect\Delivery\Drivers;

use App\Services\Connect\Delivery\ProviderInterface;
use App\Services\Connect\Delivery\DeliveryResult;
use Illuminate\Support\Str;

class TwilioDriver implements ProviderInterface
{
    public function send(array $payload, array $credentials, array $config): DeliveryResult
    {
        usleep(200000);
        return new DeliveryResult(true, 'twilio', 'SM' . Str::uuid(), 'queued', 201, 200);
    }
    public function supports(string $messageType): bool { return true; }
    public function testConnection(array $credentials, array $config): array { return ['success' => true, 'provider_version' => '2010-04-01']; }
}
