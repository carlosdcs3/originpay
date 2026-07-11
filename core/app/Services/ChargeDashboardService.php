<?php

namespace App\Services;

use App\Models\Charge;
use App\DTOs\Finance\ChargeDashboardData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ChargeDashboardService
{
    public function getDashboardData(Request $request): ChargeDashboardData
    {
        $query = Charge::with(['user', 'gateway']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            })->orWhere('correlation_id', $search)
              ->orWhere('id', $search);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $charges = $query->latest()->paginate(20)->withQueryString();

        $kpis = Cache::remember('charge_kpis_v1', 60, function() {
            $today = now()->startOfDay();
            
            // Note: Enum casts might require strings depending on how ChargeStatus is defined.
            // Assuming string values: pending, paid, canceled, expired, failed.
            
            $paidToday = Charge::where('status', 'paid')->where('updated_at', '>=', $today)->count();
            $pending = Charge::where('status', 'pending')->count();
            $expired = Charge::where('status', 'expired')->count();
            
            $tpv = Charge::where('status', 'paid')->sum('amount');
            $countPaid = Charge::where('status', 'paid')->count();
            $ticket = $countPaid > 0 ? ($tpv / $countPaid) : 0;
            
            return [
                'issued' => Charge::count(),
                'paid_today' => $paidToday,
                'pending' => $pending,
                'expired' => $expired,
                'tpv' => $tpv,
                'ticket_avg' => $ticket,
                'failures' => 0, // Mock: counts from Webhook errors
                'settlement_time' => '1.5 dias', // Mock SLA
            ];
        });

        $alerts = [];
        // Example: High failure rate alert
        if ($kpis['failures'] > 100) {
            $alerts[] = [
                'type' => 'danger',
                'icon' => 'fa-solid fa-server',
                'title' => 'Anomalia em Recebimentos',
                'message' => 'Alta taxa de falhas nos webhooks de confirmação. Possível instabilidade em Gateways.',
                'action' => null
            ];
        }

        return new ChargeDashboardData(
            kpis: $kpis,
            charges: $charges,
            alerts: $alerts
        );
    }
}
