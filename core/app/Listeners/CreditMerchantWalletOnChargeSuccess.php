<?php

namespace App\Listeners;

use App\Events\ChargeStatusChanged;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Enums\TrxType;
use App\Enums\TrxStatus;
use App\Enums\AmountFlow;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Enums\MethodType;
use Illuminate\Support\Str;

class CreditMerchantWalletOnChargeSuccess
{
    public function handle(ChargeStatusChanged $event)
    {
        $charge = $event->charge;

        // Ensure we only process when the charge succeeds
        if ($charge->status->value !== 'succeeded') {
            return;
        }

        // Check for idempotency: Do not process if the transaction already exists
        $exists = Transaction::where('trx_reference', $charge->id)
            ->where('trx_type', TrxType::RECEIVE_PAYMENT->value)
            ->exists();

        if ($exists) {
            Log::info("Charge {$charge->id} already processed. Skipping wallet credit.");
            return;
        }

        DB::transaction(function () use ($charge) {
            $amountInCents = $charge->amount;
            $amount = $amountInCents / 100;

            // Fallback Fee: 1.5% + 0.30
            $fee = round(($amount * 0.015) + 0.30, 2);
            $netAmount = round($amount - $fee, 2);

            if ($netAmount <= 0) {
                Log::warning("Charge {$charge->id} has net amount <= 0. Skipping wallet credit.");
                return;
            }

            // Find merchant wallet for BRL
            $wallet = Wallet::where('user_id', $charge->merchantId)
                ->whereHas('currency', function ($q) {
                    $q->where('code', 'BRL');
                })->first();

            if (!$wallet) {
                $wallet = Wallet::where('user_id', $charge->merchantId)->first();
            }

            if (!$wallet) {
                Log::error("Merchant {$charge->merchantId} does not have a wallet.");
                return;
            }

            // Credit the wallet directly
            $wallet->balance += $netAmount;
            $wallet->available_balance += $netAmount;
            $wallet->save();

            // Create Transaction
            Transaction::create([
                'user_id' => $charge->merchantId,
                'trx_type' => TrxType::RECEIVE_PAYMENT,
                'description' => "Pagamento via Checkout (#{$charge->chargeNumber})",
                'provider' => 'efi',
                'processing_type' => MethodType::AUTOMATIC,
                'amount' => $amount,
                'amount_flow' => AmountFlow::INCOMING,
                'fee' => $fee,
                'currency' => $charge->currency,
                'net_amount' => $netAmount,
                'payable_amount' => $amount,
                'payable_currency' => $charge->currency,
                'wallet_reference' => $wallet->uuid,
                'trx_reference' => $charge->id,
                'trx_data' => $charge->merchantMetadata,
                'status' => TrxStatus::COMPLETED,
            ]);
        });
    }
}
