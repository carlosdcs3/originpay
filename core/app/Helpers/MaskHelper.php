<?php

namespace App\Helpers;

class MaskHelper
{
    /**
     * Masks sensitive data in arrays recursively (Deep Masking).
     */
    public static function maskSensitiveData(array $data): array
    {
        $sensitiveKeys = [
            'api_key', 'api-key', 'secret', 'password', 'token', 'authorization',
            'cpf', 'document', 'card_number', 'cvv', 'bank_account',
            'client_secret', 'private_key'
        ];

        foreach ($data as $key => $value) {
            foreach ($sensitiveKeys as $sensitive) {
                if (stripos((string)$key, $sensitive) !== false) {
                    $data[$key] = '***MASKED***';
                    continue 2;
                }
            }

            if (is_array($value)) {
                $data[$key] = self::maskSensitiveData($value);
            }
        }

        return $data;
    }

    /**
     * Masks sensitive data in a JSON or raw string.
     */
    public static function maskString(string $payload): string
    {
        $decoded = json_decode($payload, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return json_encode(self::maskSensitiveData($decoded));
        }

        $pattern = '/(?i)(api_key|api-key|secret|password|token|authorization|cpf|document|card_number|cvv|bank_account|private_key)[\s:=]+["\']?([^"\',\s]+)["\']?/';
        return preg_replace($pattern, '$1: "***MASKED***"', $payload);
    }

    /**
     * Smart Masking specifically for the Admin View where we allow partial visibility.
     */
    public static function maskForAdminView(string $payload): string
    {
        $decoded = json_decode($payload, true);
        
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return json_encode(self::applyAdminMaskingArray($decoded), JSON_PRETTY_PRINT);
        }

        return self::maskString($payload); // Fallback to full masking if not JSON
    }

    private static function applyAdminMaskingArray(array $data): array
    {
        $redactedKeys = [
            'authorization', 'api_key', 'secret', 'token', 'password', 'private_key'
        ];

        $partialMaskKeys = [
            'cpf' => function($v) { return '***.***.***-' . substr((string)$v, -2); },
            'email' => function($v) { 
                $parts = explode('@', (string)$v);
                return count($parts) === 2 ? '***@' . $parts[1] : '***MASKED***';
            },
            'card_number' => function($v) { return '****-****-****-' . substr((string)$v, -4); },
            'account_number' => function($v) { return '****' . substr((string)$v, -4); },
            'phone' => function($v) { return '(XX) *****-' . substr((string)$v, -4); },
        ];

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = self::applyAdminMaskingArray($value);
            } else {
                $lowerKey = strtolower((string)$key);
                
                // Full Redaction
                foreach ($redactedKeys as $redact) {
                    if (str_contains($lowerKey, $redact)) {
                        $data[$key] = '[REDACTED]';
                        continue 2;
                    }
                }

                // Partial Masking
                foreach ($partialMaskKeys as $partialKey => $closure) {
                    if (str_contains($lowerKey, $partialKey) && is_string($value)) {
                        // Prevent masking empty or very short strings which might error out
                        if (strlen($value) > 4 || $partialKey === 'email') {
                            $data[$key] = $closure($value);
                        } else {
                            $data[$key] = '***MASKED***';
                        }
                        continue 2;
                    }
                }
            }
        }

        return $data;
    }
}
