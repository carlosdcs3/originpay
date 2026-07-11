<?php

namespace App\Services;

use App\Models\FinancialReconciliation;
use App\Models\ReconciliationHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ReconciliationDashboardService
{
    /**
     * Pega todos os dados essenciais para o Dashboard de Conciliações.
     * Retorna: KPIs, Alertas, Registros paginados, etc.
     */
    public function getDashboardData(Request $request)
    {
        $query = FinancialReconciliation::query();

        // Filtros (Smart Filter)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('provider', 'like', "%{$search}%")
                  ->orWhere('id', $search);
            });
        }
        
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('provider')) {
            $query->where('provider', $request->provider);
        }

        $entries = $query->latest()->paginate(20)->withQueryString();

        // KPIs em Cache
        $kpis = Cache::remember('reconciliation_kpis_v1', 60, function() {
            $totalConciliado = FinancialReconciliation::where('status', 'reconciled')->sum('actual_balance');
            $pendente = FinancialReconciliation::where('status', 'pending')->count();
            $divergencias = FinancialReconciliation::where('status', 'divergent')->count();
            $falhas = FinancialReconciliation::where('status', 'failed')->count();
            
            $ultima = ReconciliationHistory::latest('created_at')->first();
            $ultimaData = $ultima ? $ultima->created_at->diffForHumans() : 'Nunca';
            
            $slaMedio = ReconciliationHistory::avg('duration_ms');
            $slaFormatado = $slaMedio ? round($slaMedio / 1000, 2) . 's' : 'N/A';

            return [
                'total_conciliado' => $totalConciliado,
                'pendente' => $pendente,
                'divergencias' => $divergencias,
                'falhas' => $falhas,
                'ultima' => $ultimaData,
                'sla_medio' => $slaFormatado,
            ];
        });
        
        // Alertas Dinâmicos
        $alerts = [];
        if ($kpis['divergencias'] > 0) {
            $alerts[] = [
                'type' => 'warning',
                'icon' => 'fa-solid fa-triangle-exclamation',
                'title' => 'Atenção Operacional',
                'message' => 'Existem <strong>' . $kpis['divergencias'] . '</strong> divergências aguardando auditoria manual.',
                'action' => ['url' => '?status=divergent', 'label' => 'Revisar Agora']
            ];
        }
        if ($kpis['falhas'] > 0) {
            $alerts[] = [
                'type' => 'danger',
                'icon' => 'fa-solid fa-server',
                'title' => 'Falha de Sincronização',
                'message' => 'Detectamos <strong>' . $kpis['falhas'] . '</strong> falhas de comunicação com gateways.',
                'action' => ['url' => '?status=failed', 'label' => 'Ver Logs']
            ];
        }

        return [
            'entries' => $entries,
            'kpis' => $kpis,
            'alerts' => $alerts,
        ];
    }
}
