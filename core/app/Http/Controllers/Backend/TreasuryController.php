<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Services\Treasury\LiquidityProtectionService;
use App\Models\Wallet;
use App\Enums\SystemWalletUUID;
use App\Models\WithdrawalRequest;

class TreasuryController extends Controller
{
    public function index(LiquidityProtectionService $lcrService)
    {
        $efiSnapshot = app(\App\Services\Payment\EfiReconciliationService::class)->getLatestEfiBalanceSnapshot();
        $realBalance = $efiSnapshot ? $efiSnapshot->real_balance : 0;

        $ledgerBalance = Wallet::whereNotNull('user_id')->sum('balance');
        $difference = $ledgerBalance - $realBalance;
        
        $lcr = $lcrService->getLCR();
        $lcrStatus = $lcrService->evaluateLiquidity();

        $reservedMoney = Wallet::whereNotNull('user_id')->sum('reserved_balance');
        
        $pendingWithdrawals = WithdrawalRequest::whereIn('status', ['PENDING', 'PENDING_SECOND_APPROVAL', 'APPROVED', 'PROCESSING'])->sum('amount');

        $systemRevenue = Wallet::where('uuid', SystemWalletUUID::SYSTEM_REVENUE->value)->value('balance') ?? 0;
        $gatewayCosts = Wallet::where('uuid', SystemWalletUUID::GATEWAY_EFI_FEE_HOLDING->value)->value('balance') ?? 0;

        return view('backend.treasury.index', compact(
            'realBalance',
            'ledgerBalance',
            'difference',
            'lcr',
            'lcrStatus',
            'reservedMoney',
            'pendingWithdrawals',
            'systemRevenue',
            'gatewayCosts'
        ));
    }
}
