<?php

namespace App\Http\Controllers\Frontend;

use App\Enums\TrxStatus;
use App\Enums\TrxType;
use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\Request;

class TransferController extends Controller
{
    public function index(Request $request)
    {
        $types = [TrxType::SEND_MONEY->value, TrxType::RECEIVE_MONEY->value, TrxType::WITHDRAW->value];

        $baseQuery = Transaction::query()
            ->where('user_id', $request->user()->id)
            ->whereIn('trx_type', $types);

        $query = (clone $baseQuery)->latest();

        if ($request->filled('direction') && $request->direction !== 'all') {
            if ($request->direction === 'sent') {
                $query->where('trx_type', TrxType::SEND_MONEY->value);
            } elseif ($request->direction === 'received') {
                $query->where('trx_type', TrxType::RECEIVE_MONEY->value);
            } elseif ($request->direction === 'withdraw') {
                $query->where('trx_type', TrxType::WITHDRAW->value);
            }
        }

        if ($request->filled('status') && $request->status !== 'all') {
            if ($request->status === TrxStatus::FAILED->value) {
                $query->whereIn('status', [TrxStatus::FAILED->value, TrxStatus::CANCELED->value]);
            } else {
                $query->where('status', $request->status);
            }
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('trx_id', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('remarks', 'like', "%{$search}%")
                    ->orWhere('trx_reference', 'like', "%{$search}%");
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $transfers = $query->paginate(7)->withQueryString();

        $last30Days = (clone $baseQuery)->where('created_at', '>=', now()->subDays(30));
        $totalCount = (clone $baseQuery)->count();
        $completedCount = (clone $baseQuery)->where('status', TrxStatus::COMPLETED->value)->count();

        $stats = [
            'total_volume' => (clone $last30Days)->sum('amount'),
            'sent_volume' => (clone $last30Days)->where('trx_type', TrxType::SEND_MONEY->value)->sum('amount'),
            'received_volume' => (clone $last30Days)->where('trx_type', TrxType::RECEIVE_MONEY->value)->sum('amount'),
            'withdraw_volume' => (clone $last30Days)->where('trx_type', TrxType::WITHDRAW->value)->sum('amount'),
            'success_rate' => $totalCount > 0 ? round(($completedCount / $totalCount) * 100, 1) : 0,
            'total_count' => $totalCount,
        ];

        $directionCounts = [
            'all' => $totalCount,
            'sent' => (clone $baseQuery)->where('trx_type', TrxType::SEND_MONEY->value)->count(),
            'received' => (clone $baseQuery)->where('trx_type', TrxType::RECEIVE_MONEY->value)->count(),
            'withdraw' => (clone $baseQuery)->where('trx_type', TrxType::WITHDRAW->value)->count(),
            'pending' => (clone $baseQuery)->where('status', TrxStatus::PENDING->value)->count(),
            'failed' => (clone $baseQuery)->whereIn('status', [TrxStatus::FAILED->value, TrxStatus::CANCELED->value])->count(),
        ];

        // Fetch PIX keys for withdrawal form
        $pixKeys = auth()->user()->pixKeys()->orderBy('is_primary', 'desc')->get();
        $pixMethod = \App\Models\WithdrawMethod::where('name', 'like', '%PIX%')->orWhere('name', 'like', '%Pix%')->first();

        return view('frontend.user.transfer.index', compact('transfers', 'stats', 'directionCounts', 'pixKeys', 'pixMethod'));
    }
}
