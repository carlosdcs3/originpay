<?php

namespace App\Console\Commands\Reconciliation;

use Illuminate\Console\Command;
use App\Models\WebhookEvent;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class ReconcileWebhooksCommand extends Command
{
    protected $signature = 'reconcile:webhooks {--days=7}';
    protected $description = 'Reconciles webhook events and detects orphaned or unprocessed events.';

    public function handle()
    {
        $days = (int) $this->option('days');
        $this->info("Starting Webhook Reconciliation for the last {$days} days...");

        $since = Carbon::now()->subDays($days);
        $events = WebhookEvent::where('created_at', '>=', $since)->get();

        $anomalies = [];

        foreach ($events as $event) {
            // Regra 1: Webhook parado em PROCESSING por mais de 1 hora
            if ($event->status === 'PROCESSING' && $event->updated_at->diffInHours(now()) > 1) {
                $anomalies[] = [
                    $event->id, $event->provider, $event->event_id, 'STUCK_IN_PROCESSING', $event->created_at
                ];
            }

            // Regra 2: Webhook FAILED que não gerou DLQ correspondente (hipotético)
            // Aqui poderíamos checar a tabela webhook_dlqs
            
            // Regra 3: Webhook RECEIVED por mais de 3 horas sem processar (Fila travada)
            if ($event->status === 'RECEIVED' && $event->created_at->diffInHours(now()) > 3) {
                $anomalies[] = [
                    $event->id, $event->provider, $event->event_id, 'UNPROCESSED_LONG_TIME', $event->created_at
                ];
            }
        }

        if (count($anomalies) > 0) {
            $this->warn("Found " . count($anomalies) . " anomalies.");
            
            $filename = "reconciliation/webhooks_" . now()->format('Y_m_d_H_i_s') . ".csv";
            $path = storage_path("logs/" . $filename);
            
            @mkdir(dirname($path), 0755, true);
            $file = fopen($path, 'w');
            fputcsv($file, ['ID', 'Provider', 'Event ID', 'Anomaly Type', 'Created At']);
            foreach ($anomalies as $row) {
                fputcsv($file, $row);
            }
            fclose($file);

            $this->info("Report generated at: {$path}");
        } else {
            $this->info("No anomalies found in Webhooks.");
        }
    }
}
