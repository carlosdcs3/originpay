<?php

namespace App\Services;

use App\Models\Transaction;
use App\DTOs\Finance\TransactionDashboardData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class TransactionDashboardService
{
    public function getDashboardData(Request $request): TransactionDashboardData
    {
        $query = Transaction::with(['user']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            })->orWhere('trx', $search)
              ->orWhere('id', $search);
        }

        if ($request->filled('type')) {
            $query->where('trx_type', $request->type);
        }

        $transactions = $query->latest()->paginate(20)->withQueryString();

        $kpis = Cache::remember('transaction_kpis_v1', 60, function() {
            $totalIn = Transaction::where('trx_type', '+')->sum('amount');
            $totalOut = Transaction::where('trx_type', '-')->sum('amount');
            $fees = Transaction::sum('charge');
            
            return [
                'total_volume' => $totalIn + $totalOut,
                'in' => $totalIn,
                'out' => $totalOut,
                'fees' => $fees,
                'adjustments' => 0, // Mock
                'refunds' => 0,     // Mock
                'failures' => 0,    // Mock
            ];
        });

        $alerts = [];
        // Nenhum alerta operacional para "Transações" pois é uma tela consolidada,
        // a não ser que os estornos (refunds) explodam.

        return new TransactionDashboardData(
            kpis: $kpis,
            transactions: $transactions,
            alerts: $alerts
        );
    }
}
