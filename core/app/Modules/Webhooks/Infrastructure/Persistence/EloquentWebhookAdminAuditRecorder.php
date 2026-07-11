<?php

namespace App\Modules\Webhooks\Infrastructure\Persistence;

use App\Models\WebhookAdminAudit;
use App\Modules\Webhooks\Domain\Contracts\WebhookAdminAuditRecorder;
use Illuminate\Support\Facades\Auth;

class EloquentWebhookAdminAuditRecorder implements WebhookAdminAuditRecorder
{
    public function record(
        string $action,
        string $targetType,
        int $targetId,
        ?string $batchId = null,
        ?string $reason = null
    ): void {
        WebhookAdminAudit::create([
            'admin_id' => Auth::guard('admin')->id() ?? Auth::id() ?? 1,
            'action' => $action,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'batch_id' => $batchId,
            'reason' => $reason,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
