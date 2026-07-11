<?php

namespace App\Http\Controllers\Backend\Gateway;

use App\Http\Controllers\Controller;
use App\Models\Charge;
use Illuminate\Http\Request;

class AdminChargeController extends Controller
{
    public function index(Request $request)
    {
        $query = Charge::with(['user', 'gateway'])->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('uuid', 'like', "%{$search}%")
                  ->orWhere('gateway_charge_id', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%")
                  ->orWhereHas('user', function($uq) use ($search) {
                      $uq->where('username', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        $charges = $query->paginate(20);
        
        $stats = [
            'total' => \App\Models\Charge::count(),
            'paid' => \App\Models\Charge::where('status', \App\Enums\ChargeStatus::PAID)->count(),
            'pending' => \App\Models\Charge::where('status', \App\Enums\ChargeStatus::PENDING)->count(),
            'refunded' => \App\Models\Charge::where('status', \App\Enums\ChargeStatus::REFUNDED)->count(),
            'gross' => \App\Models\Charge::where('status', \App\Enums\ChargeStatus::PAID)->sum('amount'),
            'net' => \App\Models\Charge::where('status', \App\Enums\ChargeStatus::PAID)->sum('net_amount'),
        ];
        
        return view('backend.gateway.charges.index', compact('charges', 'stats'));
    }

    public function show($id)
    {
        $charge = Charge::with(['user', 'events'])->findOrFail($id);
        return view('backend.gateway.charges.show', compact('charge'));
    }
}
