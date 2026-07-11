<?php

namespace App\Services\Webhook\Policies;

class EfiWebhookPolicy implements GatewayWebhookPolicyInterface
{
    public function maxRetries(): int { return 5; }
    public function backoffStrategy(): array { return [60, 300, 900, 1800, 3600]; }
    public function timeoutSeconds(): int { return 30; }
}
