<?php

namespace App\Services\Finance;

use App\Models\FeeRecord;
use App\Data\Finance\FeeDashboardData;
use Illuminate\Support\Carbon;

class FeeDashboardService
{
    public function getDashboardData(array $filters): FeeDashboardData
    {
        $query = FeeRecord::with(['user', 'gateway']);

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
            'total_gross' => (clone $kpiQuery)->sum('gross_amount'),
            'total_merchant_fee' => (clone $kpiQuery)->sum('merchant_fee'),
            'total_gateway_cost' => (clone $kpiQuery)->sum('gateway_cost'),
            'total_margin' => (clone $kpiQuery)->sum('margin'),
            'count_divergent' => (clone $kpiQuery)->where('status', 'divergent')->count(),
        ];

        $fees = $query->orderBy('created_at', 'desc')->paginate(20)->withQueryString();

        return new FeeDashboardData($kpis, $fees, $filters);
    }
}
