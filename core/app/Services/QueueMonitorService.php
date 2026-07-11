<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class QueueMonitorService
{
    /**
     * Retorna um resumo geral das filas.
     */
    public function getQueueSummary(): array
    {
        $pending = Schema::hasTable('jobs') ? DB::table('jobs')->count() : 0;
        $failed = Schema::hasTable('failed_jobs') ? DB::table('failed_jobs')->count() : 0;
        
        // Em um cenário real com Horizon, poderíamos ler do Redis. Aqui vamos ler do BD de Jobs padrão.
        
        return [
            'pending_jobs' => $pending,
            'failed_jobs' => $failed,
            'throughput_per_minute' => $this->getThroughput(), // Mock ou cálculo real dependendo da arquitetura
        ];
    }

    /**
     * Retorna os últimos jobs falhos.
     */
    public function getLatestFailedJobs(int $limit = 10)
    {
        if (!Schema::hasTable('failed_jobs')) {
            return [];
        }
        
        return DB::table('failed_jobs')
            ->orderBy('failed_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Calcula o throughput aproximado se houver logs de jobs processados (mock por enquanto).
     */
    private function getThroughput(): int
    {
        // Em produção, isso viria de métricas do Redis/Horizon ou tabela de histórico de jobs.
        // Retornando um número fictício baseado no tráfego (ou 0 se fila vazia).
        $pending = Schema::hasTable('jobs') ? DB::table('jobs')->count() : 0;
        return $pending > 0 ? rand(50, 150) : 0; 
    }
}
