<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\WalletTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Services\ReconciliationDashboardService;
use App\Services\WalletDashboardService;
use App\Services\WithdrawalDashboardService;
use App\Services\ChargeDashboardService;
use App\Services\TransactionDashboardService;
use App\Services\ChargebackDashboardService;
use App\Services\SettlementDashboardService;
use App\Services\FeeDashboardService;

class FinanceController extends Controller
{
    public function ledger(Request $request)
    {
        $query = WalletTransaction::with(['user', 'wallet', 'reference']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('id', $search)
                  ->orWhere('correlation_id', $search);
            });
        }
        
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $entries = $query->latest('id')->paginate(20)->withQueryString();

        $kpis = Cache::remember('ledger_kpis_v2', 60, function() {
            $totalIn = WalletTransaction::where('amount', '>', 0)->whereNotIn('type', ['fee', 'withdrawal'])->sum('amount');
            $totalOut = WalletTransaction::where('amount', '<', 0)->sum('amount');
            $adjustments = WalletTransaction::where('type', 'adjustment')->count();
            $discrepancies = 0; 

            return [
                'total_in' => $totalIn,
                'total_out' => $totalOut,
                'volume' => $totalIn + abs($totalOut),
                'adjustments' => $adjustments,
                'discrepancies' => $discrepancies,
                'operating_balance' => WalletTransaction::sum('amount')
            ];
        });

        return view('backend.finance.ledger', compact('entries', 'kpis'));
    }

    public function reconciliation(Request $request, ReconciliationDashboardService $service)
    {
        $data = $service->getDashboardData($request);
        return view('backend.finance.reconciliation', $data);
    }

    public function balances(Request $request, WalletDashboardService $service) 
    { 
        $data = $service->getDashboardData($request);
        return view('backend.finance.balances', $data->toArray()); 
    }
    
    public function withdrawals(Request $request, WithdrawalDashboardService $service)
    {
        $data = $service->getDashboardData($request);
        return view('backend.finance.withdrawals', $data->toArray());
    }

    public function charges(Request $request, ChargeDashboardService $service)
    {
        $data = $service->getDashboardData($request);
        return view('backend.finance.charges', $data->toArray());
    }

    public function transactions(Request $request, TransactionDashboardService $service)
    {
        $data = $service->getDashboardData($request);
        return view('backend.finance.transactions', $data->toArray());
    }

    public function chargebacks(Request $request, ChargebackDashboardService $service)
    {
        $data = $service->getDashboardData($request);
        return view('backend.finance.chargebacks', $data->toArray());
    }
    
    public function repasses(Request $request, SettlementDashboardService $service)
    {
        return redirect()->route('admin.gateway.withdrawals.index');
    }

    public function fees(Request $request, FeeDashboardService $service)
    {
        $data = $service->getDashboardData($request);
        return view('backend.finance.fees', $data->toArray());
    }

    public function liquidacao() { return view('backend.finance.liquidacao'); }
    public function refunds() { return view('backend.finance.refunds'); }

    public function webhooks(Request $request)
    {
        $query = \App\Models\WebhookEvent::query();
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        $events = $query->latest('id')->paginate(20)->withQueryString();

        $kpis = [
            'received' => \App\Models\WebhookEvent::where('status', 'received')->count(),
            'processed' => \App\Models\WebhookEvent::where('status', 'processed')->count(),
            'failed' => \App\Models\WebhookEvent::where('status', 'failed')->count(),
            'dead_letter' => \App\Models\WebhookEvent::where('status', 'dead_letter')->count(),
        ];

        return view('backend.finance.webhooks', compact('events', 'kpis'));
    }
}

