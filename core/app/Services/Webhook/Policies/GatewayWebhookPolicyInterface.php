<?php

namespace App\Services\Webhook\Policies;

interface GatewayWebhookPolicyInterface
{
    /**
     * Define o número máximo de tentativas antes da Dead Letter Queue.
     */
    public function maxRetries(): int;

    /**
     * Define os backoffs (em segundos) por tentativa.
     * Ex: [60, 300, 900, 1800, 3600]
     */
    public function backoffStrategy(): array;

    /**
     * Tempo máximo de timeout que o processing job pode aguardar
     */
    public function timeoutSeconds(): int;
}
