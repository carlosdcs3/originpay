<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Gateway;
use App\Services\Financial\FinancialReconciliationService;
use Illuminate\Support\Facades\Redis;

class OpsDashboardController extends Controller
{
    /**
     * Exibe o painel de Observabilidade (Ops Dashboard)
     */
    public function index()
    {
        $pageTitle = 'Ops Dashboard - Observabilidade';
        
        // 1. Health dos Gateways
        $gateways = Gateway::all()->map(function($gw) {
            $score = Redis::get("gateway:health_score:{$gw->code}") ?? 100;
            return [
                'name' => $gw->name,
                'code' => $gw->code,
                'score' => $score,
                'circuit_status' => $score <= -50 ? 'OPEN' : ($score < 0 ? 'HALF_OPEN' : 'CLOSED')
            ];
        });

        // 2. Horizon Stats (Mock simplificado, em produção usaria Laravel\Horizon\Contracts\MetricsRepository)
        $horizonStats = [
            'recent_jobs' => Redis::get('horizon:recent_jobs') ?? 0,
            'failed_jobs' => Redis::get('horizon:failed_jobs') ?? 0,
            'queue_size' => Redis::llen('queues:default') ?? 0,
        ];

        // 3. Reconciliação (Alerta crítico se houver)
        $reconciliationService = app(FinancialReconciliationService::class);
        $reconciliations = []; // Oculto o rodar pesado aqui, deixo para o CronJob alertar. Mostrarei apenas os últimos logs do canal Audit.

        $withdrawalsPaused = \Illuminate\Support\Facades\Cache::get('system_withdrawals_paused', false);

        return view('admin.ops.dashboard', compact('pageTitle', 'gateways', 'horizonStats', 'withdrawalsPaused'));
    }

    /**
     * Kill Switch: Pausa todos os saques pendentes
     */
    public function toggleWithdrawals(Request $request)
    {
        abort_unless(auth()->guard('admin')->user()->can('ops.withdrawals.pause') || auth()->guard('admin')->user()->id == 1, 403, 'Não autorizado para o Kill Switch.');

        $request->validate(['reason' => 'required|string']);

        $currentState = \Illuminate\Support\Facades\Cache::get('system_withdrawals_paused', false);
        $newState = !$currentState;
        
        \Illuminate\Support\Facades\Cache::forever('system_withdrawals_paused', $newState);

        // Audit Log
        \Illuminate\Support\Facades\Log::channel('audit')->info("Kill Switch de Saques alterado", [
            'admin_id' => auth()->guard('admin')->id(),
            'new_state' => $newState ? 'PAUSED' : 'ACTIVE',
            'reason' => $request->reason,
            'ip' => $request->ip()
        ]);

        if ($newState) {
            \App\Models\PlatformIncident::create([
                'title' => 'Kill Switch de Saques Ativado',
                'severity' => 'critical',
                'status' => 'active',
                'started_at' => now(),
                'root_cause' => $request->reason,
                'created_by' => auth()->guard('admin')->id(),
            ]);
        } else {
            \App\Models\PlatformIncident::where('title', 'Kill Switch de Saques Ativado')
                ->where('status', 'active')
                ->update([
                    'status' => 'resolved',
                    'resolved_at' => now(),
                    'resolution' => $request->reason,
                    'resolved_by' => auth()->guard('admin')->id(),
                ]);
        }

        $msg = $newState ? 'Saques pausados emergencialmente!' : 'Processamento de saques reativado!';
        return back()->with('notify', [['success', $msg]]);
    }

    /**
     * Executar Reconciliação Manual
     */
    public function runReconciliation()
    {
        abort_unless(auth()->guard('admin')->user()->can('ops.reconciliation.run') || auth()->guard('admin')->user()->id == 1, 403, 'Não autorizado.');

        // Envia para fila de processamento pesado
        \Illuminate\Support\Facades\Artisan::queue('ledger:reconcile');
        
        \Illuminate\Support\Facades\Log::channel('audit')->info("Reconciliação Manual iniciada", ['admin_id' => auth()->guard('admin')->id()]);

        return back()->with('notify', [['success', 'Job de Reconciliação enfileirado com sucesso.']]);
    }

    /**
     * Executar Verificação de Integridade
     */
    public function runIntegrityCheck()
    {
        abort_unless(auth()->guard('admin')->user()->can('ops.ledger.verify') || auth()->guard('admin')->user()->id == 1, 403, 'Não autorizado.');

        \Illuminate\Support\Facades\Artisan::queue('ledger:verify-integrity');
        
        \Illuminate\Support\Facades\Log::channel('audit')->info("Verificação de Ledger iniciada", ['admin_id' => auth()->guard('admin')->id()]);

        return back()->with('notify', [['success', 'Job de Verificação do Ledger enfileirado com sucesso.']]);
    }
}
