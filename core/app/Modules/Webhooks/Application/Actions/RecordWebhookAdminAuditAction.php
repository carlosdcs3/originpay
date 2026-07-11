<?php

namespace App\Modules\Webhooks\Application\Actions;

use App\Modules\Webhooks\Domain\Contracts\WebhookAdminAuditRecorder;

class RecordWebhookAdminAuditAction
{
    public function __construct(
        private readonly WebhookAdminAuditRecorder $recorder
    ) {
    }

    public function execute(
        string $action,
        string $targetType,
        int $targetId,
        ?string $batchId = null,
        ?string $reason = null
    ): void {
        $this->recorder->record($action, $targetType, $targetId, $batchId, $reason);
    }
}
