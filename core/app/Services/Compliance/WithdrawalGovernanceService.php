<?php

namespace App\Services\Compliance;

use App\Models\User;
use App\Models\WithdrawalRequest;
use App\Models\WithdrawalApprovalRule;
use App\Models\WithdrawalApproval;
use App\Models\FinancialAnomaly;
use App\Models\FinancialComplianceAudit;
use BackedEnum;

class WithdrawalGovernanceService
{
    /**
     * Determines the approval mode and requires the appropriate governance.
     */
    public function getApprovalMode(float $amount): string
    {
        $rule = WithdrawalApprovalRule::where('is_active', true)
            ->where('min_amount', '<=', $amount)
            ->where(function ($query) use ($amount) {
                $query->whereNull('max_amount')
                      ->orWhere('max_amount', '>=', $amount);
            })
            ->first();

        return $rule ? $rule->approval_mode : 'ADMIN'; // Default to ADMIN if no rule matches
    }

    /**
     * Process dual approval logic.
     */
    public function approveLevel(WithdrawalRequest $request, User $admin)
    {
        $adminRole = $admin->role instanceof BackedEnum ? $admin->role->value : $admin->role;

        // Require role validation
        if (!in_array($adminRole, ['FINANCE_ADMIN', 'SUPER_ADMIN'])) {
            throw new \Exception("Admin does not have the required role to approve withdrawals.");
        }

        $existingApprovals = $request->approvals()->get();

        if ($existingApprovals->where('admin_id', $admin->id)->count() > 0) {
            $this->registerBypassAnomaly($request, $admin);
            throw new \Exception("Admin cannot approve the same request twice.");
        }

        $currentLevel = $existingApprovals->count() + 1;

        $approval = WithdrawalApproval::create([
            'withdrawal_request_id' => $request->id,
            'admin_id' => $admin->id,
            'approval_level' => $currentLevel,
            'role_at_approval' => $adminRole
        ]);

        FinancialComplianceAudit::create([
            'admin_id' => $admin->id,
            'user_id' => $request->user_id,
            'action' => "approve_withdrawal_level_{$currentLevel}",
            'before' => ['status' => $request->status],
            'after' => ['status' => 'PENDING_NEXT_LEVEL', 'approval_id' => $approval->id],
            'reason' => "Admin approved level {$currentLevel}",
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent()
        ]);

        return $currentLevel;
    }

    private function registerBypassAnomaly(WithdrawalRequest $request, User $admin)
    {
        FinancialAnomaly::create([
            'type' => 'dual_approval_bypass_attempt',
            'severity' => 'CRITICAL',
            'entity_type' => 'withdrawal_request',
            'entity_id' => $request->id,
            'fingerprint' => "bypass_attempt_{$request->id}_{$admin->id}_" . now()->timestamp,
            'description' => "Admin {$admin->id} attempted to approve a withdrawal twice or bypass dual approval.",
            'metadata' => ['admin_id' => $admin->id],
            'suggested_actions' => ['audit_admin_activity', 'revoke_permissions'],
            'detected_at' => now(),
        ]);
    }
}
