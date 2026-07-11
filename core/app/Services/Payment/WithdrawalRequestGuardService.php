<?php

namespace App\Services\Payment;

use App\Exceptions\NotifyErrorException;
use App\Models\AccountRestriction;
use App\Models\FeatureFlag;
use App\Models\User;
use App\Models\WithdrawalSetting;
use App\Services\Treasury\LiquidityProtectionService;
use Illuminate\Support\Facades\Cache;

class WithdrawalRequestGuardService
{
    public function __construct(
        protected LiquidityProtectionService $liquidityProtectionService
    ) {
    }

    /**
     * Validate whether the platform currently allows this user to request a withdrawal.
     *
     * The withdraw schedule is intentionally not checked here because it is used by the
     * admin area as an automatic processing calendar, not as a merchant request gate.
     *
     * @throws NotifyErrorException
     */
    public function ensureUserCanRequest(User $user): void
    {
        if (Cache::get('system_withdrawals_paused', false)) {
            throw new NotifyErrorException('Saques estão temporariamente desativados pela plataforma.');
        }

        $settings = WithdrawalSetting::first();
        if (! $settings || ! $settings->withdraw_enabled) {
            throw new NotifyErrorException('Saques estão temporariamente desativados pela plataforma.');
        }

        $withdrawalsFlag = FeatureFlag::where('key', 'withdrawals_enabled')->first();
        if ($withdrawalsFlag && ! $withdrawalsFlag->is_active) {
            throw new NotifyErrorException('Saques estão temporariamente desativados pela plataforma.');
        }

        if (! $user->isKycVerified()) {
            throw new NotifyErrorException('Conclua sua verificação para sacar.');
        }

        $restriction = AccountRestriction::query()
            ->where('user_id', $user->id)
            ->whereIn('restriction_type', ['WITHDRAW_BLOCK', 'FULL_FREEZE', 'KYC_LIMIT_LOCK'])
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->latest()
            ->first();

        if ($restriction) {
            if ($restriction->restriction_type === 'KYC_LIMIT_LOCK') {
                throw new NotifyErrorException('Conclua sua verificação para sacar.');
            }

            throw new NotifyErrorException('Sua conta está temporariamente impedida de realizar saques.');
        }

        if ($this->liquidityProtectionService->evaluateLiquidity() === 'CRITICAL') {
            throw new NotifyErrorException('Saque indisponível no momento por validação operacional.');
        }
    }
}
