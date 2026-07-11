<?php

namespace App\Http\Controllers\Backend\Finance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Finance\FeeDashboardService;

class FeeController extends Controller
{
    public function index(Request $request, FeeDashboardService $service)
    {
        $pageTitle = 'Taxas & Margens (Fees)';
        
        $filters = $request->only(['status', 'gateway_id', 'start_date', 'end_date']);
        $dashboardData = $service->getDashboardData($filters);
        
        return view('backend.finance.fees.index', compact('pageTitle', 'dashboardData'));
    }
}
