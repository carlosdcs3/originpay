<?php

namespace App\Services;

use App\Models\Wallet;
use App\Models\WalletBalance;
use App\DTOs\Finance\WalletDashboardData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class WalletDashboardService
{
    public function getDashboardData(Request $request): WalletDashboardData
    {
        $query = Wallet::with(['user', 'currency', 'balances.gateway']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            })->orWhere('uuid', $search)
              ->orWhere('id', $search);
        }

        $wallets = $query->latest('updated_at')->paginate(20)->withQueryString();

        $kpis = Cache::remember('wallet_dashboard_kpis_v1', 60, function() {
            $totalBalance = Wallet::sum('balance');
            
            // Getting aggregate from WalletBalances
            $aggregates = WalletBalance::selectRaw('SUM(available) as available, SUM(pending) as pending, SUM(blocked) as blocked')->first();
            
            $activeWallets = Wallet::where('status', 1)->count();
            
            // Encontrar divergências onde balance != sum(available + pending + blocked) na WalletBalance
            // Para simplificar no MVP e não causar lentidão:
            $divergent = 0; 

            // Gateway com maior custódia
            $topGateway = WalletBalance::with('gateway')
                ->selectRaw('gateway_id, SUM(available) as total')
                ->groupBy('gateway_id')
                ->orderByDesc('total')
                ->first();

            return [
                'total' => $totalBalance,
                'available' => $aggregates->available ?? 0,
                'pending' => $aggregates->pending ?? 0,
                'blocked' => $aggregates->blocked ?? 0,
                'active_count' => $activeWallets,
                'divergent_count' => $divergent,
                'top_gateway' => $topGateway ? ($topGateway->gateway->name ?? 'Desconhecido') : 'N/A',
                'top_gateway_amount' => $topGateway ? $topGateway->total : 0,
            ];
        });

        // Distribution Data for Native Multi-Progress Bar
        $distributionData = Cache::remember('wallet_gateway_distribution_v1', 60, function() use ($kpis) {
            if ($kpis['total'] <= 0) return [];
            
            $gateways = WalletBalance::with('gateway')
                ->selectRaw('gateway_id, SUM(available + pending + blocked) as total_volume')
                ->groupBy('gateway_id')
                ->orderByDesc('total_volume')
                ->get();
                
            $dist = [];
            $colors = ['bg-primary', 'bg-info', 'bg-success', 'bg-warning', 'bg-danger'];
            foreach($gateways as $index => $g) {
                $percentage = ($g->total_volume / max(1, $kpis['total'])) * 100;
                $dist[] = [
                    'name' => $g->gateway->name ?? 'Unknown',
                    'volume' => $g->total_volume,
                    'percentage' => round($percentage, 2),
                    'colorClass' => $colors[$index % count($colors)]
                ];
            }
            return $dist;
        });

        $alerts = [];
        if ($kpis['blocked'] > ($kpis['total'] * 0.10)) { // se > 10% do dinheiro estiver bloqueado
            $alerts[] = [
                'type' => 'warning',
                'icon' => 'fa-solid fa-lock',
                'title' => 'Risco Operacional de Liquidez',
                'message' => 'Um valor significativo (<strong>' . number_format($kpis['blocked'], 2, ',', '.') . '</strong>) encontra-se bloqueado nos provedores.',
                'action' => null
            ];
        }

        return new WalletDashboardData(
            kpis: $kpis,
            wallets: $wallets,
            alerts: $alerts,
            charts: [], 
            distributionData: $distributionData
        );
    }
}
