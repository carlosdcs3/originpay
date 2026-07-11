<?php

namespace App\Services\Finance;

use App\Models\Transaction;
use App\Data\Finance\TransactionDashboardData;
use Illuminate\Support\Carbon;

class TransactionDashboardService
{
    public function getDashboardData(array $filters): TransactionDashboardData
    {
        $query = Transaction::with(['user', 'gateway', 'wallet', 'charge', 'withdraw']);

        // Filtros orientados a negócio
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['operation'])) {
            $query->where('operation', $filters['operation']);
        }
        if (!empty($filters['trx_type'])) {
            $query->where('trx_type', $filters['trx_type']);
        }
        if (!empty($filters['trx_id'])) {
            $query->where('trx_id', $filters['trx_id']);
        }
        if (!empty($filters['start_date'])) {
            $query->where('created_at', '>=', Carbon::parse($filters['start_date'])->startOfDay());
        }
        if (!empty($filters['end_date'])) {
            $query->where('created_at', '<=', Carbon::parse($filters['end_date'])->endOfDay());
        }

        // Query Clonada para KPIs exatos
        $kpiQuery = clone $query;

        $kpis = [
            'total_volume' => (clone $kpiQuery)->sum('amount'),
            'count_success' => (clone $kpiQuery)->where('status', 'completed')->count(),
            'count_failed' => (clone $kpiQuery)->where('status', 'failed')->count(),
            'count_chargeback' => (clone $kpiQuery)->where('status', 'chargeback')->count(),
            'total_count' => (clone $kpiQuery)->count()
        ];
        
        $kpis['success_rate'] = $kpis['total_count'] > 0 
            ? round(($kpis['count_success'] / $kpis['total_count']) * 100, 2) 
            : 0;

        // Paginação
        $transactions = $query->orderBy('created_at', 'desc')->paginate(20)->withQueryString();

        return new TransactionDashboardData($kpis, $transactions, $filters);
    }
}
