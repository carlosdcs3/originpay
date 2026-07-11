<?php

namespace App\Http\Controllers\Backend\Gateway;

use App\Http\Controllers\Controller;
use App\Models\GatewayLog;
use Illuminate\Http\Request;

class GatewayLogController extends Controller
{
    public function index(Request $request)
    {
        $pageTitle = 'Logs de Gateway';

        $query = GatewayLog::query()->orderBy('id', 'desc');

        if ($request->has('status') && $request->status != '') {
            $query->where('status', $request->status);
        }

        if ($request->has('gateway') && $request->gateway != '') {
            $query->where('gateway_code', $request->gateway);
        }

        $logs = $query->paginate(20);

        return view('backend.gateway.logs', compact('pageTitle', 'logs'));
    }
}
