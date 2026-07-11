<?php
namespace App\Services\Connect\Delivery;

interface ProviderInterface
{
    public function send(array $payload, array $credentials, array $config): DeliveryResult;
    public function supports(string $messageType): bool;
    public function testConnection(array $credentials, array $config): array;
}
