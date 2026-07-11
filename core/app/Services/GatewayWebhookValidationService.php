<?php

namespace App\Services;

use App\Exceptions\GatewayWebhookValidationException;
use App\Models\PaymentGateway;
use Illuminate\Http\Request;

class GatewayWebhookValidationService
{
    private const SIGNATURE_HEADERS = [
        'X-Webhook-Signature',
        'X-Gateway-Signature',
        'X-Hub-Signature-256',
    ];

    private const TIMESTAMP_HEADERS = [
        'X-Webhook-Timestamp',
        'X-Gateway-Timestamp',
    ];

    public function validate(PaymentGateway $gateway, Request $request): array
    {
        $signature = $this->signature($request);

        if (!$signature) {
            throw new GatewayWebhookValidationException('Webhook signature is required.', 401);
        }

        $secret = $this->webhookSecret($gateway);

        if (!$secret) {
            throw new GatewayWebhookValidationException('Provider does not support webhook signature validation.', 400);
        }

        $rawPayload = $request->getContent();
        $timestamp = $this->timestamp($request);

        if ($this->requiresTimestamp($gateway) && !$timestamp) {
            throw new GatewayWebhookValidationException('Webhook timestamp is required.', 401);
        }

        if ($timestamp) {
            $this->validateTimestamp($timestamp);
        }

        if (!$this->signatureMatches($signature, $secret, $rawPayload, $timestamp)) {
            throw new GatewayWebhookValidationException('Invalid webhook signature.', 401);
        }

        $payload = json_decode($rawPayload, true);

        if (!is_array($payload) || json_last_error() !== JSON_ERROR_NONE) {
            throw new GatewayWebhookValidationException('Invalid webhook payload.', 422);
        }

        $this->validatePayloadShape($gateway, $payload);

        return $payload;
    }

    public function safeHeaders(Request $request): array
    {
        $headers = $request->headers->all();

        foreach (array_keys($headers) as $key) {
            if ($this->isSensitiveHeader($key)) {
                unset($headers[$key]);
            }
        }

        return $headers;
    }

    private function signature(Request $request): ?string
    {
        foreach (self::SIGNATURE_HEADERS as $header) {
            $value = $request->header($header);

            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        return null;
    }

    private function timestamp(Request $request): ?string
    {
        foreach (self::TIMESTAMP_HEADERS as $header) {
            $value = $request->header($header);

            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        return null;
    }

    private function webhookSecret(PaymentGateway $gateway): ?string
    {
        $credentials = is_array($gateway->credentials)
            ? $gateway->credentials
            : json_decode((string) $gateway->credentials, true);

        if (!is_array($credentials)) {
            return null;
        }

        foreach (['webhook_secret', 'webhookSecret', 'secret'] as $key) {
            if (!empty($credentials[$key]) && is_string($credentials[$key])) {
                return $credentials[$key];
            }
        }

        return null;
    }

    private function requiresTimestamp(PaymentGateway $gateway): bool
    {
        $credentials = is_array($gateway->credentials)
            ? $gateway->credentials
            : json_decode((string) $gateway->credentials, true);

        return is_array($credentials) && (bool) ($credentials['webhook_requires_timestamp'] ?? false);
    }

    private function validateTimestamp(string $timestamp): void
    {
        if (!ctype_digit($timestamp)) {
            throw new GatewayWebhookValidationException('Invalid webhook timestamp.', 401);
        }

        if (abs(time() - (int) $timestamp) > 300) {
            throw new GatewayWebhookValidationException('Webhook timestamp is outside the accepted window.', 401);
        }
    }

    private function signatureMatches(string $signature, string $secret, string $rawPayload, ?string $timestamp): bool
    {
        $normalizedSignature = str_starts_with($signature, 'sha256=')
            ? substr($signature, 7)
            : $signature;

        $candidates = [
            hash_hmac('sha256', $rawPayload, $secret),
        ];

        if ($timestamp) {
            $candidates[] = hash_hmac('sha256', $timestamp . '.' . $rawPayload, $secret);
        }

        foreach ($candidates as $candidate) {
            if (hash_equals($candidate, $normalizedSignature)) {
                return true;
            }
        }

        return false;
    }

    private function validatePayloadShape(PaymentGateway $gateway, array $payload): void
    {
        if (strtolower($gateway->code) === 'efi') {
            $pix = $payload['pix'] ?? null;
            $boleto = $payload['boleto'] ?? $payload['billet'] ?? $payload['charge'] ?? null;

            $hasPix = is_array($pix) && $pix !== [] && is_array($pix[0] ?? null);
            $hasBoleto = is_array($boleto) && $boleto !== [];

            if (!$hasPix && !$hasBoleto) {
                throw new GatewayWebhookValidationException('Invalid EFI webhook payload.', 422);
            }

            return;
        }

        if ($payload === []) {
            throw new GatewayWebhookValidationException('Invalid webhook payload.', 422);
        }
    }

    private function isSensitiveHeader(string $header): bool
    {
        $header = strtolower($header);

        return str_contains($header, 'signature')
            || str_contains($header, 'authorization')
            || str_contains($header, 'token')
            || $header === 'cookie'
            || $header === 'set-cookie';
    }
}
