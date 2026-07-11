<?php

namespace App\Services\Finance;

use App\Models\FinancialReconciliation;
use App\Models\PaymentGateway;
use App\Data\Finance\ReconciliationDashboardData;
use Illuminate\Support\Carbon;

class ReconciliationDashboardService
{
    public function getDashboardData(array $filters): ReconciliationDashboardData
    {
        $query = FinancialReconciliation::query();

        // Apply Smart Filters
        if (!empty($filters['provider'])) {
            $query->where('provider', $filters['provider']);
        }
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['start_date'])) {
            $query->where('created_at', '>=', Carbon::parse($filters['start_date'])->startOfDay());
        }
        if (!empty($filters['end_date'])) {
            $query->where('created_at', '<=', Carbon::parse($filters['end_date'])->endOfDay());
        }

        // Clone query for KPIs to ensure exact match with filtered data
        $kpiQuery = clone $query;

        // Calculate dynamic KPIs
        $kpis = [
            'total_expected' => (clone $kpiQuery)->sum('expected_balance'),
            'total_actual' => (clone $kpiQuery)->sum('actual_balance'),
            'total_difference' => (clone $kpiQuery)->sum('difference'),
            'count_critical' => (clone $kpiQuery)->where('status', 'CRITICAL')->count(),
            'count' => (clone $kpiQuery)->count()
        ];

        // Pagination
        $reconciliations = $query->orderBy('created_at', 'desc')->paginate(20)->withQueryString();

        $activeGateways = PaymentGateway::where('status', true)->get(['id', 'name', 'code'])->toArray();

        return new ReconciliationDashboardData($kpis, $reconciliations, $activeGateways, $filters);
    }
}
