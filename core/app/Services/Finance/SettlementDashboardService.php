<?php

namespace App\Services\Finance;

use App\Models\Settlement;
use App\Data\Finance\SettlementDashboardData;
use App\Enums\Finance\TransactionStatus;
use Illuminate\Support\Carbon;

class SettlementDashboardService
{
    public function getDashboardData(array $filters): SettlementDashboardData
    {
        $query = Settlement::with(['user', 'gateway']);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['gateway_id'])) {
            $query->where('gateway_id', $filters['gateway_id']);
        }
        if (!empty($filters['start_date'])) {
            $query->where('created_at', '>=', Carbon::parse($filters['start_date'])->startOfDay());
        }
        if (!empty($filters['end_date'])) {
            $query->where('created_at', '<=', Carbon::parse($filters['end_date'])->endOfDay());
        }

        $kpiQuery = clone $query;

        $kpis = [
            'total_net' => (clone $kpiQuery)->sum('net_amount'),
            'total_pending' => (clone $kpiQuery)->where('status', TransactionStatus::PENDING->value)->sum('net_amount'),
            'total_paid' => (clone $kpiQuery)->where('status', TransactionStatus::SUCCEEDED->value)->sum('net_amount'),
            'count_pending' => (clone $kpiQuery)->where('status', TransactionStatus::PENDING->value)->count(),
        ];

        $settlements = $query->orderBy('created_at', 'desc')->paginate(20)->withQueryString();

        return new SettlementDashboardData($kpis, $settlements, $filters);
    }
}
