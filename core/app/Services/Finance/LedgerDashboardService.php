<?php

namespace App\Services\Finance;

use App\Models\Transaction;
use App\Models\PaymentGateway;
use App\Models\WalletBalance;
use App\Data\Finance\LedgerDashboardData;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class LedgerDashboardService
{
    public function getDashboardData(array $filters): LedgerDashboardData
    {
        $query = Transaction::with(['user', 'gateway', 'wallet']);

        // Apply Smart Filters
        if (!empty($filters['gateway_id'])) {
            $query->where('gateway_id', $filters['gateway_id']);
        }
        if (!empty($filters['operation'])) {
            $query->where('operation', $filters['operation']);
        }
        if (!empty($filters['trx_type'])) {
            $query->where('trx_type', $filters['trx_type']);
        }
        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }
        if (!empty($filters['provider_reference'])) {
            $query->where('provider_reference', 'LIKE', '%' . $filters['provider_reference'] . '%');
        }
        if (!empty($filters['start_date'])) {
            $query->where('created_at', '>=', Carbon::parse($filters['start_date'])->startOfDay());
        }
        if (!empty($filters['end_date'])) {
            $query->where('created_at', '<=', Carbon::parse($filters['end_date'])->endOfDay());
        }
        if (!empty($filters['trx_id'])) {
            $query->where('trx_id', $filters['trx_id']);
        }

        // Clone query for KPIs to ensure they match exactly the filtered data
        $kpiQuery = clone $query;

        // Calculate KPIs dynamically
        $kpis = [
            'total_in' => (clone $kpiQuery)->where('trx_type', '+')->sum('amount'),
            'total_out' => (clone $kpiQuery)->where('trx_type', '-')->sum('amount'),
            'total_fees' => (clone $kpiQuery)->sum('fee'),
            'net_volume' => 0 // computed below
        ];
        $kpis['net_volume'] = $kpis['total_in'] - $kpis['total_out'];
        $kpis['count'] = (clone $kpiQuery)->count();

        // Pagination
        $transactions = $query->orderBy('created_at', 'desc')->paginate(20)->withQueryString();

        // Enqueue additional context for the Drawer (wallet_balances) for each transaction
        // Since doing this row by row in Blade is bad (N+1), we can append it here or fetch dynamically via AJAX.
        // For performance, the drawer context is usually fetched via AJAX, but the user requested:
        // "O Drawer deve sempre mostrar... contexto atual em wallet_balances".
        // Let's attach the current wallet_balances to the paginated items.
        $walletIds = $transactions->pluck('wallet_id')->unique()->filter()->toArray();
        $balances = WalletBalance::whereIn('wallet_id', $walletIds)->get()->groupBy('wallet_id');

        $transactions->getCollection()->transform(function ($trx) use ($balances) {
            // Find specific balance for this gateway
            if ($trx->wallet_id && $trx->gateway_id) {
                $walletBals = $balances->get($trx->wallet_id);
                if ($walletBals) {
                    $trx->current_gateway_balance = $walletBals->firstWhere('gateway_id', $trx->gateway_id);
                }
            }
            return $trx;
        });

        $activeGateways = PaymentGateway::where('status', true)->get(['id', 'name', 'code'])->toArray();

        return new LedgerDashboardData($kpis, $transactions, $activeGateways, $filters);
    }
}
