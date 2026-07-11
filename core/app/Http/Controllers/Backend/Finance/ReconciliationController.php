<?php

namespace App\Http\Controllers\Backend\Finance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Finance\ReconciliationDashboardService;

class ReconciliationController extends Controller
{
    public function index(Request $request, ReconciliationDashboardService $reconciliationService)
    {
        $pageTitle = 'Conciliações (Reconciliations)';
        
        $filters = $request->only([
            'provider', 'status', 'start_date', 'end_date'
        ]);

        $dashboardData = $reconciliationService->getDashboardData($filters);
        
        return view('backend.finance.reconciliations.index', compact('pageTitle', 'dashboardData'));
    }
}
