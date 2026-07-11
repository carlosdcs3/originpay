<?php

namespace App\Modules\Webhooks\Application\Actions;

use App\Jobs\ReplayWebhookJob;
use App\Models\WebhookDlq;

class ReprocessWebhookDlqAction
{
    public function __construct(
        private readonly RecordWebhookAdminAuditAction $recordAudit
    ) {
    }

    public function execute(WebhookDlq $dlq, ?string $reason = null): void
    {
        $this->recordAudit->execute('reprocessed_item', 'webhook_dlq', $dlq->id, null, $reason);

        ReplayWebhookJob::dispatch($dlq)->onQueue('high');
    }
}
