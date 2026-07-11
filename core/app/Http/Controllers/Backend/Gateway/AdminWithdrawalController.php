<?php

namespace App\Http\Controllers\Backend\Gateway;

use App\Http\Controllers\Controller;
use App\Models\WithdrawalRequest;
use Illuminate\Http\Request;

class AdminWithdrawalController extends Controller
{
    public function index(Request $request)
    {
        $query = WithdrawalRequest::with('user')->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->filled('provider')) {
            $query->where('provider', $request->provider);
        }

        if ($request->filled('period')) {
            match ($request->period) {
                'today' => $query->whereDate('created_at', today()),
                '7days' => $query->where('created_at', '>=', now()->subDays(7)),
                '30days' => $query->where('created_at', '>=', now()->subDays(30)),
                default => null,
            };
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('transaction_id', 'like', "%$search%")
                  ->orWhereHas('user', function($u) use ($search) {
                      $u->where('username', 'like', "%$search%")
                        ->orWhere('email', 'like', "%$search%");
                  });
            });
        }

        $withdrawals = $query->paginate(20);

        // KPIs
        $kpiTotal = WithdrawalRequest::count();
        $kpiPending = WithdrawalRequest::where('status', 'PENDING')->count();
        $kpiAnalysis = WithdrawalRequest::where('status', 'PROCESSING')->count();
        $kpiApproved = WithdrawalRequest::where('status', 'SUCCESS')->count();
        $kpiPendingValue = WithdrawalRequest::whereIn('status', ['PENDING', 'PROCESSING'])->sum('amount');
        $providers = WithdrawalRequest::query()
            ->whereNotNull('provider')
            ->where('provider', '!=', '')
            ->distinct()
            ->orderBy('provider')
            ->pluck('provider');

        return view('backend.gateway.withdrawals.index', compact(
            'withdrawals', 
            'kpiTotal', 
            'kpiPending', 
            'kpiAnalysis', 
            'kpiApproved', 
            'kpiPendingValue',
            'providers'
        ));
    }

    public function show($id)
    {
        $withdrawal = WithdrawalRequest::with('user')->findOrFail($id);
        return view('backend.gateway.withdrawals.show', compact('withdrawal'));
    }
}
