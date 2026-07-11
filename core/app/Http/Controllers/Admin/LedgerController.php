<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Charge;
use App\Models\WalletTransaction;
use App\Models\ProcessedEvent;
use Illuminate\Http\Request;

class LedgerController extends Controller
{
    /**
     * Exibe a listagem geral do Ledger (transações)
     */
    public function index(Request $request)
    {
        $transactions = WalletTransaction::with(['user', 'wallet'])->latest()->paginate(20);
        return view('backend.finance.ledger.index', compact('transactions'));
    }

    /**
     * Exibe a Timeline completa de uma cobrança específica.
     */
    public function timeline($chargeId)
    {
        $charge = Charge::with(['user', 'gateway'])->findOrFail($chargeId);
        
        // Buscamos transações no Ledger relativas a esta Charge
        $walletTx = WalletTransaction::where('reference_type', Charge::class)
                        ->where('reference_id', $chargeId)
                        ->orderBy('id', 'asc')
                        ->get();

        // Eventos idempotentes (Webhooks processados)
        $processedEvents = ProcessedEvent::where('correlation_id', $charge->id)->get();

        return view('backend.finance.ledger.timeline', compact('charge', 'walletTx', 'processedEvents'));
    }

    /**
     * Dispara o Job de Exportação Financeira
     */
    public function export(Request $request)
    {
        $filters = $request->only(['start_date', 'end_date']);
        $adminId = auth()->guard('admin')->id();

        \App\Jobs\FinancialExportJob::dispatch($adminId, $filters);

        \Illuminate\Support\Facades\Log::channel('audit')->info("Solicitação de Exportação de Ledger", [
            'admin_id' => $adminId,
            'filters' => $filters,
            'ip' => $request->ip()
        ]);

        return back()->with('notify', [['success', 'Sua exportação foi enfileirada. Você será notificado quando o CSV estiver pronto.']]);
    }
}
