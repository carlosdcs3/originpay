<?php

namespace App\Services\DisasterRecovery;

use App\Models\WebhookEvent;
use App\Models\WebhookAdminAudit;
use App\Jobs\ProcessWebhookJob;
use Illuminate\Support\Str;

class EmergencyReplayService
{
    /**
     * Reprocessa eventos da base de dados e envia para a High Queue
     */
    public function replay(int $adminId, array $filters): array
    {
        $query = WebhookEvent::query();

        if (isset($filters['provider'])) {
            $query->where('provider', $filters['provider']);
        }

        if (isset($filters['event_id'])) {
            $query->where('event_id', $filters['event_id']);
        }

        if (isset($filters['external_reference'])) {
            $query->where('external_reference', $filters['external_reference']);
        }

        if (isset($filters['start_date']) && isset($filters['end_date'])) {
            $query->whereBetween('created_at', [$filters['start_date'], $filters['end_date']]);
        }

        $events = $query->get();

        $batchId = Str::uuid()->toString();
        $dispatched = 0;

        foreach ($events as $event) {
            // Se já não estiver PROCESSING, repõe na fila
            if ($event->status !== 'PROCESSING') {
                $event->status = 'RECEIVED';
                $event->attempts = 0;
                $event->last_error = null;
                $event->save();

                ProcessWebhookJob::dispatch($event)->onQueue('high');
                $dispatched++;
            }
        }

        if ($dispatched > 0) {
            WebhookAdminAudit::create([
                'admin_id' => $adminId,
                'action' => 'emergency_replay',
                'target_type' => 'webhook_events',
                'target_id' => 0,
                'batch_id' => $batchId,
                'reason' => 'Emergency Replay of ' . $dispatched . ' items.',
                'ip_address' => request()->ip() ?? '127.0.0.1',
            ]);
        }

        return [
            'batch_id' => $batchId,
            'dispatched' => $dispatched,
            'total_found' => count($events)
        ];
    }
}
