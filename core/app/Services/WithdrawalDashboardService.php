<?php

namespace App\Services;

use App\Models\WithdrawalRequest;
use App\DTOs\Finance\WithdrawalDashboardData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class WithdrawalDashboardService
{
    public function getDashboardData(Request $request): WithdrawalDashboardData
    {
        $query = WithdrawalRequest::with(['user', 'wallet.balances.gateway', 'audits']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            })->orWhere('transaction_id', $search)
              ->orWhere('id', $search);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $withdrawals = $query->latest()->paginate(20)->withQueryString();

        $kpis = Cache::remember('withdrawal_kpis_v1', 60, function() {
            $today = now()->startOfDay();
            
            return [
                'pending' => WithdrawalRequest::where('status', 'pending')->count(),
                'processing' => WithdrawalRequest::where('status', 'processing')->count(),
                'approved_today' => WithdrawalRequest::where('status', 'approved')->where('approved_at', '>=', $today)->count(),
                'rejected' => WithdrawalRequest::where('status', 'rejected')->count(),
                'volume_requested' => WithdrawalRequest::whereIn('status', ['pending', 'processing'])->sum('amount'),
                'blocked' => WithdrawalRequest::where('status', 'blocked')->count(),
                'sla_avg' => '12m', // mock or calculate based on created_at and approved_at
                'gateway_failures' => 0 // mock or calculate based on failed provider logs
            ];
        });

        $alerts = [];
        if ($kpis['pending'] > 50) {
            $alerts[] = [
                'type' => 'danger',
                'icon' => 'fa-solid fa-hourglass-half',
                'title' => 'Fila de Saques Crítica',
                'message' => 'Existem <strong>' . $kpis['pending'] . '</strong> saques aguardando aprovação. Risco de quebra de SLA.',
                'action' => ['url' => '?status=pending', 'label' => 'Analisar Fila']
            ];
        }

        return new WithdrawalDashboardData(
            kpis: $kpis,
            withdrawals: $withdrawals,
            alerts: $alerts
        );
    }
}
