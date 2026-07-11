<?php

namespace App\Http\Controllers\Backend\Finance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Finance\SettlementDashboardService;
use App\Services\Finance\SettlementActionService;
use App\Models\Settlement;

class SettlementController extends Controller
{
    public function index(Request $request, SettlementDashboardService $service)
    {
        $pageTitle = 'Repasses (Settlements)';
        
        $filters = $request->only(['status', 'gateway_id', 'start_date', 'end_date']);
        $dashboardData = $service->getDashboardData($filters);
        
        return view('backend.finance.settlements.index', compact('pageTitle', 'dashboardData'));
    }

    public function pay(Request $request, Settlement $settlement, SettlementActionService $actionService)
    {
        try {
            $actionService->pay($settlement);
            $notify[] = ['success', 'Repasse liquidado com sucesso.'];
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::channel('payments')->error('Settlement payment failed', [
                'settlement_id' => $settlement->id ?? null,
                'exception' => get_class($e),
                'message'   => $e->getMessage(),
                'code'      => $e->getCode(),
                'file'      => $e->getFile(),
                'line'      => $e->getLine(),
            ]);
            $notify[] = ['error', $e->getMessage()];
        }
        
        return back()->withNotify($notify);
    }
}
