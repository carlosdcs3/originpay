<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\GatewayFeeConfig;
use App\Services\Payment\GatewayFeeService;

class GatewayFeeController extends Controller
{
    public function index()
    {
        $configs = GatewayFeeConfig::orderBy('provider', 'asc')->get();
        return view('backend.gateway_fees.index', compact('configs'));
    }

    public function edit($id)
    {
        $config = GatewayFeeConfig::findOrFail($id);
        return view('backend.gateway_fees.edit', compact('config'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'transaction_fee_type' => 'required|in:fixed,percent,fixed_plus_percent',
            'transaction_fixed_fee' => 'required|numeric|min:0',
            'transaction_percent_fee' => 'required|numeric|min:0|max:100',
            'withdraw_fee_type' => 'required|in:fixed,percent,fixed_plus_percent',
            'withdraw_fixed_fee' => 'required|numeric|min:0',
            'withdraw_percent_fee' => 'required|numeric|min:0|max:100',
            'provider_fee_mode' => 'required|in:estimated,real,manual',
            'provider_fixed_fee' => 'required|numeric|min:0',
            'provider_percent_fee' => 'required|numeric|min:0|max:100',
            'is_active' => 'required|boolean',
        ]);

        $config = GatewayFeeConfig::findOrFail($id);
        $config->update(array_merge($request->all(), ['updated_by' => auth()->id()]));

        return redirect()->route('admin.gateway-fees.index')->with('success', 'Gateway Fees updated successfully.');
    }

    public function simulate(Request $request)
    {
        $request->validate([
            'provider' => 'required|string',
            'amount' => 'required|numeric|min:0.01',
            'type' => 'required|in:deposit,withdraw'
        ]);

        $service = app(GatewayFeeService::class);
        
        try {
            $result = $request->type === 'deposit'
                ? $service->calculateForDeposit((float) $request->amount, $request->provider)
                : $service->calculateForWithdraw((float) $request->amount, $request->provider);
                
            return response()->json([
                'success' => true,
                'gross_amount' => $result->gross_amount,
                'net_amount' => $result->net_amount,
                'platform_fee' => $result->platform_fee_amount,
                'provider_fee' => $result->provider_fee_amount
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
}
