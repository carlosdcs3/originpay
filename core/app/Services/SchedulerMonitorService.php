<?php

namespace App\Services;

use App\Models\ScheduledTaskLog;
use Illuminate\Support\Facades\DB;

class SchedulerMonitorService
{
    /**
     * Resumo das tarefas agendadas recentes.
     */
    public function getSummary(): array
    {
        $last24h = ScheduledTaskLog::where('created_at', '>=', now()->subHours(24))->get();
        
        $total = $last24h->count();
        $failed = $last24h->where('status', 'failed')->count();
        $avgDuration = $total > 0 ? round($last24h->avg('duration_ms'), 2) : 0;
        
        return [
            'total_executions_24h' => $total,
            'failed_executions_24h' => $failed,
            'avg_duration_ms' => $avgDuration,
        ];
    }
    
    /**
     * Retorna a lista das últimas tarefas agendadas que falharam.
     */
    public function getLatestFailures(int $limit = 5)
    {
        return ScheduledTaskLog::where('status', 'failed')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
}
