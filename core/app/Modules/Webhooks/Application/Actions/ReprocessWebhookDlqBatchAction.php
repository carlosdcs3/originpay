<?php

namespace App\Modules\Webhooks\Application\Actions;

use App\Jobs\ReplayWebhookJob;
use App\Models\WebhookDlq;
use Illuminate\Support\Collection;

class ReprocessWebhookDlqBatchAction
{
    public function __construct(
        private readonly RecordWebhookAdminAuditAction $recordAudit
    ) {
    }

    /**
     * @param  Collection<int, WebhookDlq>  $dlqs
     */
    public function execute(Collection $dlqs, string $batchId): void
    {
        $this->recordAudit->execute(
            'reprocessed_batch',
            'webhook_dlq',
            0,
            $batchId,
            'Batch reprocessing of ' . $dlqs->count() . ' items'
        );

        $delayIndex = 0;

        foreach ($dlqs as $dlq) {
            $delay = $delayIndex * 2;
            ReplayWebhookJob::dispatch($dlq)->delay(now()->addSeconds($delay))->onQueue('high');
            $delayIndex++;
        }
    }
}
