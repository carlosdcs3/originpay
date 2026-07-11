<?php

namespace App\Services;

use App\Models\FeeRecord;
use App\DTOs\Finance\FeeDashboardData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class FeeDashboardService
{
    public function getDashboardData(Request $request): FeeDashboardData
    {
        $query = FeeRecord::with(['user', 'gateway']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            })->orWhere('reference_id', $search)
              ->orWhere('id', $search);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $fees = $query->latest()->paginate(20)->withQueryString();

        $kpis = Cache::remember('fee_kpis_v1', 60, function() {
            $merchantFees = FeeRecord::sum('merchant_fee');
            $gatewayCosts = FeeRecord::sum('gateway_cost');
            $margin = FeeRecord::sum('margin');
            
            $count = FeeRecord::count();
            $avgMargin = $count > 0 ? ($margin / $count) : 0;
            
            $divergent = FeeRecord::where('status', 'divergent')->count();
            
            return [
                'total_revenue' => $merchantFees,
                'total_cost' => $gatewayCosts,
                'net_margin' => $margin,
                'avg_margin' => $avgMargin,
                'divergent' => $divergent,
                'gateway_fees' => $gatewayCosts,
                'ops_fees' => $merchantFees, // Receita
                'top_merchants' => 0 // Mock
            ];
        });

        $alerts = [];
        if ($kpis['divergent'] > 10) {
            $alerts[] = [
                'type' => 'warning',
                'icon' => 'fa-solid fa-magnifying-glass-dollar',
                'title' => 'Divergência de Custos',
                'message' => 'Existem <strong>' . $kpis['divergent'] . '</strong> taxas cobradas pelos gateways que diferem do valor projetado na plataforma.',
                'action' => ['url' => '?status=divergent', 'label' => 'Analisar Divergências']
            ];
        }

        return new FeeDashboardData(
            kpis: $kpis,
            fees: $fees,
            alerts: $alerts
        );
    }
}
