<?php

namespace App\Support\Observability;

class LogRedactor
{
    private const REDACTED = '[REDACTED]';

    /**
     * @var array<int, string>
     */
    private array $sensitiveKeys = [
        'authorization',
        'proxy-authorization',
        'cookie',
        'set-cookie',
        'x-api-key',
        'api-key',
        'api_key',
        'apikey',
        'token',
        'access_token',
        'refresh_token',
        'bearer',
        'secret',
        'client_secret',
        'client-secret',
        'password',
        'passwd',
        'senha',
        'pix_key',
        'pix key',
        'certificate',
        'certificado',
        'private_key',
    ];

    public function redact(mixed $value): mixed
    {
        if (is_array($value)) {
            return $this->redactArray($value);
        }

        if (is_string($value)) {
            return $this->redactString($value);
        }

        return $value;
    }

    /**
     * @param  array<mixed>  $payload
     * @return array<mixed>
     */
    private function redactArray(array $payload): array
    {
        $redacted = [];

        foreach ($payload as $key => $value) {
            if (is_string($key) && $this->isSensitiveKey($key)) {
                $redacted[$key] = self::REDACTED;

                continue;
            }

            $redacted[$key] = $this->redact($value);
        }

        return $redacted;
    }

    private function redactString(string $value): string
    {
        $patterns = [
            '/(authorization\s*[:=]\s*)bearer\s+[^\s,;]+/i',
            '/(bearer\s+)[^\s,;]+/i',
            '/(client_secret\s*[:=]\s*)[^\s,;&]+/i',
            '/(api[_-]?key\s*[:=]\s*)[^\s,;&]+/i',
        ];

        foreach ($patterns as $pattern) {
            $value = preg_replace($pattern, '$1'.self::REDACTED, $value) ?? self::REDACTED;
        }

        return $value;
    }

    private function isSensitiveKey(string $key): bool
    {
        $normalized = strtolower($key);

        foreach ($this->sensitiveKeys as $sensitiveKey) {
            if ($normalized === $sensitiveKey) {
                return true;
            }
        }

        return false;
    }
}
