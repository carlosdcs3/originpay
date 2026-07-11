<?php

namespace App\Services;

use App\Models\Settlement;
use App\DTOs\Finance\SettlementDashboardData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SettlementDashboardService
{
    public function getDashboardData(Request $request): SettlementDashboardData
    {
        $query = Settlement::with(['user', 'gateway']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            })->orWhere('destination', 'like', "%{$search}%")
              ->orWhere('id', $search);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $settlements = $query->latest()->paginate(20)->withQueryString();

        $kpis = Cache::remember('settlement_kpis_v1', 60, function() {
            $pending = Settlement::where('status', 'pending')->count();
            $settled = Settlement::where('status', 'settled')->count();
            $processing = Settlement::where('status', 'processing')->count();
            $volume = Settlement::where('status', 'settled')->sum('net_amount');
            $failures = Settlement::where('status', 'failed')->count();
            
            return [
                'pending' => $pending,
                'settled' => $settled,
                'volume_settled' => $volume,
                'processing' => $processing,
                'failures' => $failures,
                'sla_avg' => '1 dia útil', // Mock
                'discrepancies' => 0, // Mock Conciliation
                'gateways' => Settlement::distinct('gateway_id')->count('gateway_id')
            ];
        });

        $alerts = [];
        if ($kpis['failures'] > 5) {
            $alerts[] = [
                'type' => 'danger',
                'icon' => 'fa-solid fa-building-columns',
                'title' => 'Liquidação Rejeitada',
                'message' => 'Lotes de repasse estão falhando ao atingir o banco destino. Verificar logs de Gateway.',
                'action' => ['url' => '?status=failed', 'label' => 'Verificar Falhas']
            ];
        }

        return new SettlementDashboardData(
            kpis: $kpis,
            settlements: $settlements,
            alerts: $alerts
        );
    }
}
