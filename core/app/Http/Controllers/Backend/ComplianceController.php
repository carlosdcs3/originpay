<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Services\Compliance\WithdrawalExposureService;
use App\Models\FinancialAnomaly;
use App\Models\AccountRestriction;
use App\Models\BlacklistedPixKey;
use App\Models\WithdrawalRequest;

class ComplianceController extends Controller
{
    public function index(WithdrawalExposureService $exposureService)
    {
        $exposureMetrics = $exposureService->getDailyMetrics();
        
        $frozenAccounts = AccountRestriction::with('user', 'admin')
            ->where(function($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->latest()
            ->take(10)
            ->get();

        $kycStats = [
            'pending' => \App\Models\KycProfile::where('status', 'PENDING')->count(),
            'rejected' => \App\Models\KycProfile::where('status', 'REJECTED')->count(),
            'locked_users' => \App\Models\AccountRestriction::where('restriction_type', 'KYC_LIMIT_LOCK')
                                    ->where(function($q) {
                                        $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                                    })->count()
        ];
        
        $rollingReserveTotal = \App\Models\Wallet::sum('rolling_reserve_balance');

        $blacklistedKeys = BlacklistedPixKey::with('admin')
            ->latest()
            ->take(10)
            ->get();

        $criticalAnomalies = FinancialAnomaly::whereIn('severity', ['CRITICAL', 'HIGH'])
            ->whereNull('resolved_at')
            ->latest()
            ->take(10)
            ->get();

        $pendingDualApprovals = WithdrawalRequest::with('user')
            ->where('status', 'PENDING_SECOND_APPROVAL')
            ->latest()
            ->get();

        return view('backend.compliance.index', compact(
            'exposureMetrics',
            'frozenAccounts',
            'blacklistedKeys',
            'criticalAnomalies',
            'pendingDualApprovals',
            'kycStats',
            'rollingReserveTotal'
        ));
    }
}
