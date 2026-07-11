<?php

namespace App\Modules\Webhooks\Application\Actions;

use App\Enums\WebhookEventStatus;
use App\Models\WebhookDlq;
use App\Models\WebhookEvent;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;

class ResolveWebhookItemManuallyAction
{
    public function __construct(
        private readonly RecordWebhookAdminAuditAction $recordAudit
    ) {
    }

    public function execute(int $id, string $type, string $reason): WebhookDlq|WebhookEvent
    {
        if ($type === 'dlq') {
            $item = WebhookDlq::findOrFail($id);
            $item->resolved_at = now();
        } elseif ($type === 'event') {
            $item = WebhookEvent::findOrFail($id);
            $item->status = WebhookEventStatus::MANUALLY_RESOLVED;
            $item->processed_at = now();
        } else {
            throw new InvalidArgumentException('Unsupported webhook item type.');
        }

        $item->resolution_admin_id = Auth::guard('admin')->id() ?? Auth::id();
        $item->resolution_reason = $reason;
        $item->save();

        $this->recordAudit->execute('marked_resolved', 'webhook_' . $type, $item->id, null, $reason);

        return $item;
    }
}
