<?php

namespace App\Services\Webhooks;

class WebhookSignatureService
{
    public function generateSignature(string $payloadJson, string $secret, int $timestamp): string
    {
        $signedPayload = "{$timestamp}.{$payloadJson}";
        return hash_hmac('sha256', $signedPayload, $secret);
    }

    public function generateHeader(string $payloadJson, string $secret): array
    {
        $timestamp = time();
        $signature = $this->generateSignature($payloadJson, $secret, $timestamp);
        
        return [
            'OriginPay-Signature' => "t={$timestamp},v1={$signature}"
        ];
    }
}
