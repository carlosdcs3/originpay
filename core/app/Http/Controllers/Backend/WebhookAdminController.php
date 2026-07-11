<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\WebhookEvent;
use App\Models\WebhookDlq;
use App\Helpers\MaskHelper;
use App\Enums\WebhookEventStatus;
use App\Modules\Webhooks\Application\Actions\RecordWebhookAdminAuditAction;
use App\Modules\Webhooks\Application\Actions\ReprocessWebhookDlqAction;
use App\Modules\Webhooks\Application\Actions\ReprocessWebhookDlqBatchAction;
use App\Modules\Webhooks\Application\Actions\ResolveWebhookItemManuallyAction;

class WebhookAdminController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->get('tab', 'received');
        $query = WebhookEvent::query();
        $dlqQuery = WebhookDlq::query();

        // Filters
        if ($request->filled('provider')) {
            $query->where('provider', $request->provider);
            $dlqQuery->where('provider', $request->provider);
        }
        if ($request->filled('event_id')) {
            $query->where('event_id', $request->event_id);
        }

        // Tab routing
        if ($tab === 'dlq') {
            $items = $dlqQuery->orderBy('id', 'desc')->paginate(20);
        } else {
            $statusMap = [
                'received' => WebhookEventStatus::RECEIVED->value,
                'processed' => WebhookEventStatus::PROCESSED->value,
                'failed' => WebhookEventStatus::FAILED->value,
                'reprocessed' => WebhookEventStatus::MANUALLY_RESOLVED->value,
            ];
            $status = $statusMap[$tab] ?? WebhookEventStatus::RECEIVED->value;
            
            if ($tab === 'reprocessed') {
                $query->whereNotNull('resolution_admin_id');
            } else {
                $query->where('status', $status);
            }
            $items = $query->orderBy('id', 'desc')->paginate(20);
        }

        return view('backend.webhooks.index', compact('items', 'tab'));
    }

    public function showEvent($id, RecordWebhookAdminAuditAction $recordAudit)
    {
        $event = WebhookEvent::findOrFail($id);
        $payloadMasked = MaskHelper::maskForAdminView($event->payload);
        $headersMasked = MaskHelper::maskForAdminView($event->headers ?? '{}');

        $recordAudit->execute('viewed_payload', 'webhook_event', $event->id);

        return view('backend.webhooks.show', compact('event', 'payloadMasked', 'headersMasked'));
    }

    public function showDlq($id, RecordWebhookAdminAuditAction $recordAudit)
    {
        $dlq = WebhookDlq::findOrFail($id);
        $payloadMasked = MaskHelper::maskForAdminView($dlq->payload);
        $headersMasked = MaskHelper::maskForAdminView($dlq->headers ?? '{}');

        $recordAudit->execute('viewed_payload', 'webhook_dlq', $dlq->id);

        return view('backend.webhooks.show_dlq', compact('dlq', 'payloadMasked', 'headersMasked'));
    }

    public function reprocessSingle(Request $request, $id, ReprocessWebhookDlqAction $reprocessWebhookDlq)
    {
        $dlq = WebhookDlq::findOrFail($id);
        
        if ($dlq->resolved_at) {
            if (!$request->filled('reason')) {
                return back()->with('error', 'Reason is required to reprocess a resolved item.');
            }
        }

        $reprocessWebhookDlq->execute($dlq, $request->reason);

        return back()->with('success', 'Item dispatched for reprocessing.');
    }

    public function reprocessBatch(Request $request, ReprocessWebhookDlqBatchAction $reprocessWebhookDlqBatch)
    {
        $ids = $request->input('ids', []);
        
        if (count($ids) > 50) {
            return back()->with('error', 'Maximum 50 items per batch allowed.');
        }

        if (empty($ids)) {
            return back()->with('error', 'No items selected.');
        }

        $batchId = uniqid('batch_');
        $dlqs = WebhookDlq::whereIn('id', $ids)->get();

        $reprocessWebhookDlqBatch->execute($dlqs, $batchId);

        return back()->with('success', 'Batch dispatched. Batch ID: ' . $batchId);
    }

    public function resolveManual(Request $request, $id, ResolveWebhookItemManuallyAction $resolveWebhookItemManually)
    {
        $request->validate([
            'reason' => 'required|string|min:5'
        ]);

        $type = $request->input('type', 'dlq'); // event or dlq

        $resolveWebhookItemManually->execute($id, $type, $request->reason);

        return back()->with('success', 'Item resolved manually.');
    }
}
