<?php

namespace App\Http\Controllers\Frontend;

use App\Enums\ChargeStatus;
use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Gateway\GatewayManager;
use App\Gateway\GatewayResolver;
use App\Models\Charge;
use Illuminate\Http\Request;

class BoletoController extends Controller
{
    public function index(Request $request)
    {
        $baseQuery = Charge::query()
            ->where('user_id', $request->user()->id)
            ->where('payment_method', PaymentMethod::BOLETO->value);

        $query = (clone $baseQuery)->latest();

        if ($request->filled('status') && $request->status !== 'all') {
            if ($request->status === ChargeStatus::PENDING->value) {
                $query->whereIn('status', [
                    ChargeStatus::PENDING->value,
                    ChargeStatus::WAITING_PAYMENT->value,
                ]);
            } else {
                $query->where('status', $request->status);
            }
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('gateway_charge_id', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_email', 'like', "%{$search}%")
                    ->orWhere('customer_document', 'like', "%{$search}%");
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $charges = $query->paginate(7)->withQueryString();

        $lastSevenDays = (clone $baseQuery)->where('created_at', '>=', now()->subDays(7));
        $totalCount = (clone $baseQuery)->count();
        $paidCount = (clone $baseQuery)->where('status', ChargeStatus::PAID->value)->count();
        $pendingCount = (clone $baseQuery)->whereIn('status', [
            ChargeStatus::PENDING->value,
            ChargeStatus::WAITING_PAYMENT->value,
        ])->count();

        $statusCounts = [
            'all' => $totalCount,
            'pending' => $pendingCount,
            'paid' => $paidCount,
            'expired' => (clone $baseQuery)->where('status', ChargeStatus::EXPIRED->value)->count(),
            'cancelled' => (clone $baseQuery)->where('status', ChargeStatus::CANCELLED->value)->count(),
        ];

        $stats = [
            'total_volume' => (clone $lastSevenDays)->sum('amount'),
            'paid_volume' => (clone $lastSevenDays)->where('status', ChargeStatus::PAID->value)->sum('amount'),
            'pending_volume' => (clone $lastSevenDays)->whereIn('status', [
                ChargeStatus::PENDING->value,
                ChargeStatus::WAITING_PAYMENT->value,
            ])->sum('amount'),
            'receive_rate' => $totalCount > 0 ? round(($paidCount / $totalCount) * 100, 1) : 0,
            'total_count' => $totalCount,
        ];

        return view('frontend.user.boleto.index', compact('charges', 'stats', 'statusCounts'));
    }

    public function secondCopy(Request $request, Charge $charge)
    {
        abort_unless($charge->user_id === $request->user()->id, 404);
        abort_unless(($charge->payment_method->value ?? $charge->payment_method) === PaymentMethod::BOLETO->value, 404);

        $gateway = $charge->gateway;
        if (!$gateway) {
            $gateway = GatewayResolver::resolveAllForCharge($request->user(), PaymentMethod::BOLETO)->first();
        }

        $adapter = GatewayManager::adapter($gateway);
        $adapter->refreshBoleto($charge);
        $charge->save();

        return back()->with('success', 'Segunda via do boleto atualizada com sucesso.');
    }
}
