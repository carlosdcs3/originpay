<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\WithdrawalRequest;
use Illuminate\Http\Request;

class WithdrawalQueueController extends Controller
{
    public function index(Request $request)
    {
        $query = WithdrawalRequest::with('user');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('pix_key')) {
            // Mask the pix key search or just search hash
            $query->where('pix_key_snapshot', 'like', '%' . $request->pix_key . '%');
        }

        $withdrawals = $query->latest()->paginate(20);
        
        $stats = [
            'pending' => WithdrawalRequest::where('status', 'PENDING')->count(),
            'manual_review' => WithdrawalRequest::where('status', 'MANUAL_REVIEW')->count(),
            'dual_approval' => WithdrawalRequest::where('status', 'PENDING_SECOND_APPROVAL')->count(),
            'approved' => WithdrawalRequest::where('status', 'APPROVED')->count(),
            'processing' => WithdrawalRequest::where('status', 'PROCESSING')->count(),
            'completed' => WithdrawalRequest::where('status', 'COMPLETED')->count(),
            'failed' => WithdrawalRequest::where('status', 'FAILED')->count(),
            'total_reserved' => WithdrawalRequest::whereIn('status', ['PENDING', 'PENDING_SECOND_APPROVAL', 'APPROVED', 'PROCESSING', 'MANUAL_REVIEW'])->sum('amount'),
        ];

        return view('backend.withdrawals.index', compact('withdrawals', 'stats'));
    }
}
