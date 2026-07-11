<?php

namespace App\Services;

use App\Models\WebhookDeadLetter;
use Illuminate\Support\Facades\DB;

class DlqMonitorService
{
    /**
     * Retorna o total de webhooks na DLQ agrupados por status.
     */
    public function getStatusSummary(): array
    {
        return WebhookDeadLetter::select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();
    }

    /**
     * Retorna estatísticas por Gateway.
     */
    public function getGatewaySummary(): array
    {
        return WebhookDeadLetter::where('status', 'pending')
            ->select('gateway_code', DB::raw('count(*) as pending_count'))
            ->groupBy('gateway_code')
            ->get()
            ->toArray();
    }

    /**
     * Retorna a idade média das mensagens na DLQ (em horas).
     */
    public function getAverageAgeInHours(): float
    {
        $createdAtValues = WebhookDeadLetter::where('status', 'pending')
            ->pluck('created_at')
            ->filter();

        if ($createdAtValues->isEmpty()) {
            return 0.0;
        }

        $avgMinutes = $createdAtValues
            ->map(fn ($createdAt) => now()->diffInMinutes($createdAt))
            ->avg();
            
        return round(($avgMinutes ?? 0) / 60, 2);
    }
    
    /**
     * Tenta reprocessar um item específico da DLQ.
     */
    public function reprocess(int $id): bool
    {
        $dlq = WebhookDeadLetter::find($id);
        if (!$dlq) return false;
        
        // Simulação do reprocessamento, pois a lógica de dispatch dependeria do WebhookDispatcher/GatewayWebhookController
        $dlq->status = 'processed';
        $dlq->save();
        
        return true;
    }
}
