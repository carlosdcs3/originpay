<?php

namespace App\Services\Compliance;

use App\Models\User;
use App\Models\AccountRestriction;
use Carbon\Carbon;

class AccountRestrictionService
{
    /**
     * Checks if a user has a specific restriction.
     */
    public function hasRestriction(User $user, string $type): bool
    {
        $blockingTypes = [$type, 'FULL_FREEZE'];
        if ($type === 'WITHDRAW_BLOCK') {
            $blockingTypes[] = 'KYC_LIMIT_LOCK';
        }

        return AccountRestriction::where('user_id', $user->id)
            ->whereIn('restriction_type', array_unique($blockingTypes))
            ->where(function ($query) {
                $query->whereNull('expires_at')
                      ->orWhere('expires_at', '>', now());
            })
            ->exists();
    }

    /**
     * Throws an exception if the action is blocked.
     */
    public function checkRestrictionOrThrow(User $user, string $type): void
    {
        if ($this->hasRestriction($user, $type)) {
            $blockingTypes = [$type, 'FULL_FREEZE'];
            if ($type === 'WITHDRAW_BLOCK') {
                $blockingTypes[] = 'KYC_LIMIT_LOCK';
            }

            $reason = AccountRestriction::where('user_id', $user->id)
                ->whereIn('restriction_type', array_unique($blockingTypes))
                ->where(function ($query) {
                    $query->whereNull('expires_at')
                          ->orWhere('expires_at', '>', now());
                })
                ->latest()
                ->value('reason') ?? 'Policy Violation';

            throw new \Exception("Action blocked due to account restriction: {$reason}");
        }
    }

    public function freezeAccount(int $userId, string $reason, ?int $adminId = null, ?Carbon $expiresAt = null)
    {
        AccountRestriction::create([
            'user_id' => $userId,
            'restriction_type' => 'FULL_FREEZE',
            'reason' => $reason,
            'admin_id' => $adminId,
            'expires_at' => $expiresAt
        ]);
        
        // Dispara anomalia para alertar o compliance
        \App\Models\FinancialAnomaly::create([
            'type' => 'account_frozen',
            'severity' => 'HIGH',
            'entity_type' => 'user',
            'entity_id' => $userId,
            'fingerprint' => "account_frozen:{$userId}_" . now()->timestamp,
            'description' => "Account {$userId} was manually frozen by Admin {$adminId}.",
            'metadata' => ['reason' => $reason],
            'suggested_actions' => ['review_user_activity'],
            'detected_at' => now(),
        ]);
    }
}
