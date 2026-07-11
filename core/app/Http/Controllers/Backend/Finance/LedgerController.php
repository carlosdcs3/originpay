<?php

namespace App\Http\Controllers\Backend\Finance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Finance\LedgerDashboardService;
use App\Services\Finance\FinanceAlertService;

class LedgerController extends Controller
{
    public function index(Request $request, LedgerDashboardService $ledgerService, FinanceAlertService $alertService)
    {
        $pageTitle = 'Ledger (Histórico Imutável)';
        
        $filters = $request->only([
            'gateway_id', 'operation', 'trx_type', 'user_id', 
            'provider_reference', 'start_date', 'end_date', 'trx_id'
        ]);

        $dashboardData = $ledgerService->getDashboardData($filters);
        
        // Contextual Alerts for Ledger
        $activeAlerts = $alertService->getActiveAlerts();

        return view('backend.finance.ledger.index', compact('pageTitle', 'dashboardData', 'activeAlerts'));
    }
}
