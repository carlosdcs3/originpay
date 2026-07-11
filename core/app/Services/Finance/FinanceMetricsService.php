<?php

namespace App\Services\Finance;

use Illuminate\Support\Facades\Cache;
use App\Models\Transaction;
use App\Models\WalletBalance;
use Carbon\Carbon;

class FinanceMetricsService
{
    /**
     * Aggregated metrics (cached)
     */
    public function getAggregatedMetrics(): array
    {
        return Cache::remember('finance_metrics_aggregated', 3600, function () {
            $metrics = [];
            
            // TPV Diário
            $metrics['tpv_daily'] = Transaction::where('created_at', '>=', Carbon::today())
                ->where('trx_type', '+')
                ->where('status', 'completed')
                ->sum('amount');
                
            // TPV Mensal
            $metrics['tpv_monthly'] = Transaction::where('created_at', '>=', Carbon::now()->startOfMonth())
                ->where('trx_type', '+')
                ->where('status', 'completed')
                ->sum('amount');

            // Total Withdrawals
            $metrics['withdrawals_count'] = Transaction::where('trx_type', '-')
                ->where('operation', 'PIX_WITHDRAW') // assuming specific operation or generic type
                ->count();

            // Total Chargebacks
            $metrics['chargebacks_count'] = Transaction::where('status', 'chargeback')->count();
            
            // Total Failures
            $metrics['failures_count'] = Transaction::where('status', 'failed')->count();
            
            // Gateway Volumes
            $metrics['gateway_volumes'] = Transaction::selectRaw('gateway_id, sum(amount) as total')
                ->where('status', 'completed')
                ->where('trx_type', '+')
                ->groupBy('gateway_id')
                ->pluck('total', 'gateway_id')
                ->toArray();

            return $metrics;
        });
    }

    /**
     * Realtime metrics (non-cached)
     */
    public function getRealtimeMetrics(): array
    {
        $metrics = [];
        
        $metrics['gateway_balances'] = WalletBalance::selectRaw('gateway_id, sum(available) as total_available, sum(blocked) as total_blocked')
            ->groupBy('gateway_id')
            ->get()
            ->keyBy('gateway_id')
            ->toArray();
            
        // Average settlement time could be here or aggregated depending on DB size
        
        return $metrics;
    }

    public function invalidateAggregatedCache(): void
    {
        Cache::forget('finance_metrics_aggregated');
    }
}
