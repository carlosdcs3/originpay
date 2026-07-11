<?php

namespace App\Http\Controllers\Backend\Finance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Finance\ChargebackDashboardService;

class ChargebackController extends Controller
{
    public function index(Request $request, ChargebackDashboardService $service)
    {
        $pageTitle = 'Central de Disputas & Chargebacks';
        
        $filters = $request->only(['status', 'start_date', 'end_date']);
        $dashboardData = $service->getDashboardData($filters);
        
        return view('backend.finance.chargebacks.index', compact('pageTitle', 'dashboardData'));
    }
}
