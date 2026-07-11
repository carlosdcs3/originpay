<?php

namespace App\Http\Controllers\Backend\Finance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Finance\TransactionDashboardService;

class TransactionController extends Controller
{
    public function index(Request $request, TransactionDashboardService $service)
    {
        $pageTitle = 'Transações (Gerencial)';
        
        $filters = $request->only([
            'status', 'operation', 'trx_type', 'trx_id', 'start_date', 'end_date'
        ]);

        $dashboardData = $service->getDashboardData($filters);
        
        return view('backend.finance.transactions.index', compact('pageTitle', 'dashboardData'));
    }
}
