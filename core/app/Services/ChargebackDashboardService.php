<?php

namespace App\Services;

use App\Models\Chargeback;
use App\DTOs\Finance\ChargebackDashboardData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ChargebackDashboardService
{
    public function getDashboardData(Request $request): ChargebackDashboardData
    {
        $query = Chargeback::with(['user', 'charge', 'gateway']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            })->orWhere('provider_reference', $search)
              ->orWhere('id', $search);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $chargebacks = $query->latest()->paginate(20)->withQueryString();

        $kpis = Cache::remember('chargeback_kpis_v1', 60, function() {
            $open = Chargeback::where('status', 'open')->count();
            $disputed = Chargeback::where('status', 'disputed')->count();
            $lost = Chargeback::where('status', 'lost')->count();
            $won = Chargeback::where('status', 'won')->count();
            $expired = Chargeback::where('status', 'expired')->count();
            
            $valueAtRisk = Chargeback::whereIn('status', ['open', 'disputed'])->sum('amount');
            
            return [
                'open' => $open,
                'disputed' => $disputed,
                'lost' => $lost,
                'expired' => $expired,
                'value_at_risk' => $valueAtRisk,
                'blocked_value' => $valueAtRisk, // Idealmente sum('blocked_amount') se existisse
                'cbk_rate' => '0.8%', // Mock - deve vir do total de charges
                'gateway_cbk' => 0 // Mock
            ];
        });

        $alerts = [];
        if ($kpis['cbk_rate'] > '1.0%') {
            $alerts[] = [
                'type' => 'danger',
                'icon' => 'fa-solid fa-triangle-exclamation',
                'title' => 'Risco de Banimento (Bandeiras)',
                'message' => 'Taxa de Chargeback acima do limite de segurança (1%).',
                'action' => ['url' => '#', 'label' => 'Analisar Gatilhos']
            ];
        }

        return new ChargebackDashboardData(
            kpis: $kpis,
            chargebacks: $chargebacks,
            alerts: $alerts
        );
    }
}
