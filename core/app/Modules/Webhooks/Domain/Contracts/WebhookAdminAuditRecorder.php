<?php

namespace App\Modules\Webhooks\Domain\Contracts;

interface WebhookAdminAuditRecorder
{
    public function record(
        string $action,
        string $targetType,
        int $targetId,
        ?string $batchId = null,
        ?string $reason = null
    ): void;
}
