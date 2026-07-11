<?php

namespace App\Services\Compliance;

use App\Models\WithdrawalRequest;
use App\Services\Security\TenantBypass;
use Carbon\Carbon;

class WithdrawalExposureService
{
    /**
     * Get today's exposure metrics.
     */
    public function getDailyMetrics(): array
    {
        return TenantBypass::run(function () {
            $today = now()->startOfDay();

            $requestedToday = WithdrawalRequest::where('created_at', '>=', $today)->sum('amount');
            
            $approvedToday = WithdrawalRequest::where('approved_at', '>=', $today)
                ->whereIn('status', ['APPROVED', 'PROCESSING', 'COMPLETED'])
                ->sum('amount');
                
            $paidToday = WithdrawalRequest::where('processed_at', '>=', $today)
                ->where('status', 'COMPLETED')
                ->sum('amount');

            $waitingApprovalValue = WithdrawalRequest::where('status', 'PENDING')->sum('amount');
            $waitingSecondApprovalValue = WithdrawalRequest::where('status', 'PENDING_SECOND_APPROVAL')->sum('amount');

            $inAnalysisCount = WithdrawalRequest::whereIn('status', ['PENDING', 'PENDING_SECOND_APPROVAL', 'MANUAL_REVIEW'])->count();
            $blockedCount = WithdrawalRequest::where('status', 'BLOCKED')->count();
            $rejectedCount = WithdrawalRequest::where('status', 'REJECTED')->where('created_at', '>=', $today)->count();

            return [
                'requested_today' => $requestedToday,
                'approved_today' => $approvedToday,
                'paid_today' => $paidToday,
                'waiting_approval_value' => $waitingApprovalValue,
                'waiting_second_approval_value' => $waitingSecondApprovalValue,
                'in_analysis_count' => $inAnalysisCount,
                'blocked_count' => $blockedCount,
                'rejected_today_count' => $rejectedCount,
            ];
        });
    }
}
