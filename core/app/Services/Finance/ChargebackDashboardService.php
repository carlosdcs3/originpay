<?php

namespace App\Services\Finance;

use App\Models\Transaction;
use App\Data\Finance\ChargebackDashboardData;
use App\Enums\Finance\TransactionStatus;
use App\Enums\Finance\TransactionType;
use Illuminate\Support\Carbon;

class ChargebackDashboardService
{
    public function getDashboardData(array $filters): ChargebackDashboardData
    {
        $query = Transaction::with(['user', 'gateway', 'charge'])
            ->where(function($q) {
                // Filtramos todas as transações que tiveram chargeback originado ou status correspondente
                $q->where('status', TransactionStatus::CHARGEBACK->value)
                  ->orWhere('trx_type', TransactionType::CHARGEBACK->value);
            });

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['start_date'])) {
            $query->where('created_at', '>=', Carbon::parse($filters['start_date'])->startOfDay());
        }
        if (!empty($filters['end_date'])) {
            $query->where('created_at', '<=', Carbon::parse($filters['end_date'])->endOfDay());
        }

        $kpiQuery = clone $query;

        $kpis = [
            'total_disputed_volume' => (clone $kpiQuery)->sum('amount'),
            'count_active' => (clone $kpiQuery)->where('status', TransactionStatus::CHARGEBACK->value)->count(),
            'count_won' => (clone $kpiQuery)->where('status', TransactionStatus::WON->value)->count(),
            'count_lost' => (clone $kpiQuery)->where('status', TransactionStatus::LOST->value)->count(),
        ];

        $chargebacks = $query->orderBy('created_at', 'desc')->paginate(20)->withQueryString();

        return new ChargebackDashboardData($kpis, $chargebacks, $filters);
    }
}
