<?php

namespace App\Services;

use App\Data\TransactionData;
use App\Enums\MethodType;
use App\Enums\TrxStatus;
use App\Enums\TrxType;
use App\Events\TransactionUpdated;
use App\Models\Merchant;
use App\Models\Transaction;
use App\Services\Handlers\DepositHandler;
use App\Services\Handlers\Interfaces\FailHandlerInterface;
use App\Services\Handlers\Interfaces\SuccessHandlerInterface;
use App\Services\Handlers\PaymentHandler;
use App\Services\Handlers\RequestMoneyHandler;
use App\Services\Handlers\WithdrawHandler;
use App\Services\Financial\WalletBalanceService;
use App\Services\Security\TenantBypass;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Wallet;

class TransactionService
{
    /*
    |--------------------------------------------------------------------------
    | Public Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Create a new transaction record.
     */
    public function create(TransactionData $data): Transaction
    {
        if (in_array($data->trx_type, [\App\Enums\TrxType::DEPOSIT, \App\Enums\TrxType::WITHDRAW])) {
            $provider = $data->provider ?? 'EFI'; // fallback
            $feeService = app(\App\Services\Payment\GatewayFeeService::class);
            
            $result = $data->trx_type === \App\Enums\TrxType::DEPOSIT 
                ? $feeService->calculateForDeposit($data->amount, $provider)
                : $feeService->calculateForWithdraw($data->amount, $provider);

            // Update Fee
            $data->fee = $data->trx_type === \App\Enums\TrxType::DEPOSIT 
                ? $result->platform_fee_amount 
                : $result->withdraw_fee_amount;

            $data->net_amount = $result->net_amount;

            // Merge snapshot into trx_data
            $trxData = $data->trx_data ?? [];
            $trxData['fee_snapshot'] = $result->snapshot;
            $data->trx_data = $trxData;
        }

        return Transaction::create($this->prepareTransactionData($data));
    }

    /**
     * Fetch transactions based on filters.
     */
    public function getTransactions(
        ?int $user_id = null,
        TrxType|string|null $trx_type = null,
        ?string $provider = null,
        TrxStatus|string|null $status = null,
        int $per_page = 10,
        string $sort_by = 'created_at',
        string $order = 'desc',
        ?string $search = null,
        ?string $dateRange = null,
        ?MethodType $processing_type = null
    ): LengthAwarePaginator {
        $filters = compact('user_id', 'trx_type', 'provider', 'status', 'search', 'dateRange', 'processing_type');

        return Transaction::with('user')
            ->applyFilters($filters)
            ->orderBy($sort_by, $order)
            ->paginate($per_page)
            ->withQueryString();
    }

    /**
     * Calculate current and previous stats for a specific transaction type.
     */
    public function calculateTransactionTypeStatistics(TrxType $trxType, $trxStatuses, ?int $userId = null, int $days = 7): array
    {
        $trxStatuses  = (array) $trxStatuses;
        $statusValues = array_map(fn ($s) => $s instanceof TrxStatus ? $s->value : $s, $trxStatuses);

        $range         = now();
        $currentRange  = [$range->copy()->subDays($days), $range];
        $previousRange = [$range->copy()->subDays($days * 2), $range->copy()->subDays($days)];

        $baseQuery = Transaction::where('trx_type', $trxType->value);
        if ($userId) {
            $baseQuery->where('user_id', $userId);
        }

        $current  = (clone $baseQuery)->whereBetween('created_at', $currentRange)->whereIn('status', $statusValues)->sum('amount');
        $previous = (clone $baseQuery)->whereBetween('created_at', $previousRange)->whereIn('status', $statusValues)->sum('amount');

        return [
            'current_value'   => $current,
            'previous_value'  => $previous,
            'current_percent' => $previous === 0 ? 0 : ($current / $previous) * 100,
        ];
    }

    /**
     * Complete a transaction and trigger success handler.
     */
    public function completeTransaction(string $trxId, ?string $remarks = null, ?string $description = null): void
    {
        DB::transaction(function () use ($trxId, $remarks, $description) {
            $transaction = TenantBypass::run(
                fn () => Transaction::where('trx_id', $trxId)->lockForUpdate()->first()
            );
            if (! $transaction) {
                throw new \Exception("Transaction not found for ID: {$trxId}");
            }

            if (in_array($transaction->status, [TrxStatus::COMPLETED, TrxStatus::FAILED, TrxStatus::CANCELED])) {
                Log::warning('Webhook duplicado ou transação já finalizada.', [
                    'trx_id' => $trxId,
                    'status_atual' => $transaction->status->value ?? $transaction->status
                ]);
                return;
            }

            $this->updateTransactionStatusWithRemarks($transaction, TrxStatus::COMPLETED, $remarks, $description);

            if (($handler = $this->resolveHandler($transaction)) instanceof SuccessHandlerInterface) {
                $handler->handleSuccess($transaction);
            }

            $this->sendMerchantPaymentIPN($transaction, __('Payment Completed'), TrxStatus::COMPLETED);
            event(new TransactionUpdated($transaction->user));
        });
    }

    /**
     * Fail a transaction and trigger failure handler.
     */
    public function failTransaction(string $trxId, ?string $remarks = null, ?string $description = null): void
    {
        DB::transaction(function () use ($trxId, $remarks, $description) {
            $transaction = TenantBypass::run(
                fn () => Transaction::where('trx_id', $trxId)->lockForUpdate()->first()
            );
            if (! $transaction) {
                throw new \Exception("Transaction not found for ID: {$trxId}");
            }

            if (in_array($transaction->status, [TrxStatus::COMPLETED, TrxStatus::FAILED, TrxStatus::CANCELED])) {
                return;
            }

            $this->updateTransactionStatusWithRemarks($transaction, TrxStatus::FAILED, $remarks, $description);
            $this->sendMerchantPaymentIPN($transaction, 'Payment Failed', TrxStatus::FAILED);

            if (($handler = $this->resolveHandler($transaction)) instanceof FailHandlerInterface) {
                $handler->handleFail($transaction);
            }
        });
    }

    public function cancelTransaction(string $trxId, ?string $remarks = null, bool $refund = false): void
    {
        DB::transaction(function () use ($trxId, $remarks, $refund) {
            $transaction = TenantBypass::run(
                fn () => Transaction::where('trx_id', $trxId)->lockForUpdate()->first()
            );
            if (! $transaction) {
                throw new \Exception("Transaction not found for ID: {$trxId}");
            }

            if (in_array($transaction->status, [TrxStatus::COMPLETED, TrxStatus::FAILED, TrxStatus::CANCELED])) {
                throw new \Exception("Cannot cancel a finalized transaction.");
            }

            $trxData = $transaction->trx_data ?? [];
            $trxData['is_cancelled'] = true;
            $transaction->trx_data = $trxData;
            $this->updateTransactionStatusWithRemarks($transaction, TrxStatus::FAILED, $remarks);
            $transaction->status = TrxStatus::FAILED;

            // Refund logic for Withdrawals
            if ($refund && $transaction->trx_type === TrxType::WITHDRAW) {
                $wallet = TenantBypass::run(
                    fn () => \App\Models\Wallet::where('uuid', $transaction->wallet_reference)->first()
                );
                if ($wallet) {
                    $selectedGatewayId = $trxData['gateway_id'] ?? null;
                    if ($selectedGatewayId) {
                        app(WalletBalanceService::class)->releaseWithdrawalFunds(
                            $wallet->id,
                            (int) $selectedGatewayId,
                            (float) $transaction->payable_amount,
                            [
                                'transaction_type' => 'legacy_withdraw_release',
                                'description' => 'Legacy withdrawal cancellation release',
                                'correlation_id' => $transaction->trx_id,
                                'idempotency_key' => 'legacy-withdraw-release:'.$transaction->trx_id,
                                'reference_type' => Transaction::class,
                                'reference_id' => $transaction->id,
                            ]
                        );
                    } else {
                        $wallet->reserved_balance = max(0, (float) ($wallet->reserved_balance ?? 0) - (float) $transaction->payable_amount);
                        $wallet->available_balance += $transaction->payable_amount;
                        $wallet->save();
                    }
                }
            }

            // Criar a transação CANCELLATION
            Transaction::create([
                'user_id'          => $transaction->user_id,
                'trx_type'         => TrxType::CANCELLATION,
                'amount'           => $transaction->amount,
                'fee'              => 0,
                'currency'         => $transaction->currency,
                'provider'         => $transaction->provider,
                'processing_type'  => MethodType::AUTOMATIC,
                'net_amount'       => $transaction->amount,
                'payable_amount'   => $transaction->payable_amount,
                'payable_currency' => $transaction->payable_currency,
                'wallet_reference' => $transaction->wallet_reference,
                'trx_reference'    => $transaction->trx_id, // link back
                'trx_data'         => ['original_trx_id' => $transaction->id],
                'remarks'          => $remarks ?? 'Transaction cancelled',
                'description'      => 'Cancellation of ' . $transaction->trx_id,
                'status'           => TrxStatus::COMPLETED,
            ]);

            if (($handler = $this->resolveHandler($transaction)) instanceof FailHandlerInterface) {
                $handler->handleFail($transaction);
            }
        });
    }

    /**
     * Helper to get the correct source wallet based on FinancialSourceType
     */
    protected function getSourceWallet(\App\Enums\FinancialSourceType $sourceType, ?string $sourceWalletUuid, Transaction $transaction): \App\Models\Wallet
    {
        if ($sourceType === \App\Enums\FinancialSourceType::SYSTEM) {
            return TenantBypass::run(
                fn () => \App\Models\Wallet::where('uuid', \App\Enums\SystemWalletUUID::SYSTEM_REFUND->value)->firstOrFail()
            );
        }

        if ($sourceType === \App\Enums\FinancialSourceType::GATEWAY) {
            if (!$sourceWalletUuid) {
                throw new \Exception("Gateway holding wallet UUID must be provided for GATEWAY source.");
            }
            return TenantBypass::run(
                fn () => \App\Models\Wallet::where('uuid', $sourceWalletUuid)->firstOrFail()
            );
        }

        if ($sourceType === \App\Enums\FinancialSourceType::MERCHANT) {
            // Se for MERCHANT, achamos quem recebeu o dinheiro no Ledger
            $creditEntry = TenantBypass::run(fn () => \App\Models\LedgerEntry::where('transaction_id', $transaction->id)
                ->where('direction', 'credit')
                ->whereHas('wallet', function ($q) {
                    $q->where('uuid', '!=', \App\Enums\SystemWalletUUID::SYSTEM_REVENUE->value)
                      ->where('uuid', '!=', \App\Enums\SystemWalletUUID::SYSTEM_REVENUE_FX->value);
                })->first());

            if (!$creditEntry) {
                throw new \Exception("Could not identify the merchant's wallet from the original ledger entry.");
            }

            return TenantBypass::run(
                fn () => \App\Models\Wallet::where('id', $creditEntry->wallet_id)->firstOrFail()
            );
        }

        throw new \Exception("Invalid source type.");
    }

    /**
     * Refund a completed transaction (Full or Partial).
     */
    public function refundTransaction(string $trxId, float $refundAmount, string $reason, \App\Enums\FinancialSourceType $sourceType, ?string $sourceWalletUuid = null, ?string $gatewayRefundId = null, ?int $adminId = null): void
    {
        DB::transaction(function () use ($trxId, $refundAmount, $reason, $sourceType, $sourceWalletUuid, $gatewayRefundId, $adminId) {
            $transaction = TenantBypass::run(
                fn () => Transaction::where('trx_id', $trxId)->lockForUpdate()->first()
            );
            if (! $transaction) {
                throw new \Exception("Transaction not found for ID: {$trxId}");
            }

            if ($transaction->status !== TrxStatus::COMPLETED) {
                throw new \Exception("Only completed transactions can be refunded.");
            }

            if ($refundAmount <= 0) {
                throw new \Exception("Refund amount must be greater than zero.");
            }

            $alreadyRefunded = $transaction->trx_data['refund_amount'] ?? 0;
            if (($alreadyRefunded + $refundAmount) > $transaction->amount) {
                throw new \Exception("Refund amount exceeds the original transaction amount.");
            }

            if ($gatewayRefundId) {
                $duplicateExists = TenantBypass::run(fn () => Transaction::where('trx_type', TrxType::REFUND)
                    ->where('trx_data->refund_id', $gatewayRefundId)
                    ->exists());

                if ($duplicateExists) {
                    throw new \Exception("Duplicate refund detected.");
                }
            }

            $buyerWallet = TenantBypass::run(
                fn () => \App\Models\Wallet::where('uuid', $transaction->wallet_reference)->first()
            );
            if (!$buyerWallet) {
                throw new \Exception("Buyer wallet not found.");
            }

            $sourceWallet = $this->getSourceWallet($sourceType, $sourceWalletUuid, $transaction);

            // Ledger transfer (this will inherently throw NotifyErrorException if Merchant doesn't have balance)
            app(\App\Services\LedgerService::class)->transfer(
                $sourceWallet,
                $buyerWallet,
                $refundAmount,
                null,
                "Refund for {$trxId}",
                ['original_trx_id' => $transaction->id]
            );

            // Create REFUND Transaction
            Transaction::create([
                'user_id'          => $transaction->user_id,
                'trx_type'         => TrxType::REFUND,
                'amount'           => $refundAmount,
                'fee'              => 0,
                'currency'         => $transaction->currency,
                'provider'         => $transaction->provider,
                'processing_type'  => MethodType::AUTOMATIC,
                'net_amount'       => $refundAmount,
                'payable_amount'   => $refundAmount,
                'payable_currency' => $transaction->payable_currency,
                'wallet_reference' => $buyerWallet->uuid,
                'trx_reference'    => $gatewayRefundId ?: $transaction->trx_id . ':refund:' . \Illuminate\Support\Str::uuid()->toString(),
                'trx_data'         => [
                    'original_trx_id' => $transaction->id,
                    'refund_id' => $gatewayRefundId,
                    'refunded_by' => $adminId,
                    'reason' => $reason,
                    'refund_source_type' => $sourceType->value,
                    'source_wallet_id' => $sourceWallet->id,
                    'destination_wallet_id' => $buyerWallet->id
                ],
                'remarks'          => $reason,
                'description'      => 'Refund for ' . $transaction->trx_id,
                'status'           => TrxStatus::COMPLETED,
            ]);

            $trxData = $transaction->trx_data ?? [];
            $trxData['refund_amount'] = $alreadyRefunded + $refundAmount;
            
            DB::table('transactions')->where('id', $transaction->id)->update([
                'trx_data' => json_encode($trxData)
            ]);
        });
    }

    /**
     * Chargeback a completed transaction.
     */
    public function chargebackTransaction(string $trxId, float $chargebackAmount, string $reason, \App\Enums\FinancialSourceType $sourceType, ?string $sourceWalletUuid = null, ?string $gatewayDisputeId = null, ?int $adminId = null): void
    {
        if ($sourceType === \App\Enums\FinancialSourceType::SYSTEM) {
            throw new \Exception("Chargeback source cannot be SYSTEM.");
        }

        DB::transaction(function () use ($trxId, $chargebackAmount, $reason, $sourceType, $sourceWalletUuid, $gatewayDisputeId, $adminId) {
            $transaction = TenantBypass::run(
                fn () => Transaction::where('trx_id', $trxId)->lockForUpdate()->first()
            );
            if (! $transaction) {
                throw new \Exception("Transaction not found for ID: {$trxId}");
            }

            if ($transaction->status !== TrxStatus::COMPLETED) {
                throw new \Exception("Only completed transactions can be charged back.");
            }

            if ($chargebackAmount <= 0) {
                throw new \Exception("Chargeback amount must be greater than zero.");
            }

            $duplicateCheck = Transaction::where('trx_type', TrxType::CHARGEBACK)
                ->where('trx_reference', $transaction->trx_id);
                
            if ($gatewayDisputeId) {
                $duplicateCheck->where('trx_data->dispute_id', $gatewayDisputeId);
            }

            if ($duplicateCheck->exists()) {
                throw new \Exception("Duplicate chargeback detected.");
            }

            $systemChargebackWallet = TenantBypass::run(
                fn () => \App\Models\Wallet::where('uuid', \App\Enums\SystemWalletUUID::SYSTEM_CHARGEBACK->value)->firstOrFail()
            );
            $sourceWallet = $this->getSourceWallet($sourceType, $sourceWalletUuid, $transaction);

            // Ledger transfer
            app(\App\Services\LedgerService::class)->transfer(
                $sourceWallet,
                $systemChargebackWallet,
                $chargebackAmount,
                null,
                "Chargeback for {$trxId}",
                ['original_trx_id' => $transaction->id]
            );

            // Create CHARGEBACK Transaction
            Transaction::create([
                'user_id'          => $transaction->user_id,
                'trx_type'         => TrxType::CHARGEBACK,
                'amount'           => $chargebackAmount,
                'fee'              => 0,
                'currency'         => $transaction->currency,
                'provider'         => $transaction->provider,
                'processing_type'  => MethodType::AUTOMATIC,
                'net_amount'       => $chargebackAmount,
                'payable_amount'   => $chargebackAmount,
                'payable_currency' => $transaction->payable_currency,
                'wallet_reference' => $sourceWallet->uuid,
                'trx_reference'    => $transaction->trx_id,
                'trx_data'         => [
                    'original_trx_id' => $transaction->id,
                    'dispute_id' => $gatewayDisputeId,
                    'charged_back_by' => $adminId,
                    'gateway_reason' => $reason,
                    'chargeback_source_type' => $sourceType->value,
                    'source_wallet_id' => $sourceWallet->id,
                    'destination_wallet_id' => $systemChargebackWallet->id
                ],
                'remarks'          => $reason,
                'description'      => 'Chargeback for ' . $transaction->trx_id,
                'status'           => TrxStatus::COMPLETED,
            ]);

            $trxData = $transaction->trx_data ?? [];
            $trxData['is_charged_back'] = true;
            
            DB::table('transactions')->where('id', $transaction->id)->update([
                'trx_data' => json_encode($trxData)
            ]);
        });
    }

    /**
     * Retrieve statistics for different transaction groups.
     */
    public function getTransactionStatistics(?int $userId = null): \Illuminate\Support\Collection
    {
        $trxGroups = [
            'deposit'        => [TrxType::DEPOSIT],
            'send_money'     => [TrxType::SEND_MONEY],
            'request_money'  => [TrxType::REQUEST_MONEY],
            'exchange_money' => [TrxType::EXCHANGE_MONEY],
            'payment'        => [TrxType::PAYMENT],
            'withdraw'       => [TrxType::WITHDRAW],
            'voucher'        => [TrxType::VOUCHER],
            'rewards'        => [TrxType::REWARD, TrxType::REFERRAL_REWARD],
        ];

        $transactions = Transaction::select('trx_type', 'amount', 'currency')
            ->where('status', TrxStatus::COMPLETED)
            ->when($userId, fn ($q) => $q->where('user_id', $userId))
            ->get();

        $defaultCurrency = siteCurrency();
        $converter       = app(CurrencyConversionService::class);

        $converted = $transactions->map(function ($trx) use ($converter, $defaultCurrency) {
            $amount = $trx->currency === $defaultCurrency
                ? $trx->amount
                : $converter->convertCurrency($trx->amount, $trx->currency, $defaultCurrency);

            return [
                'trx_type' => $trx->trx_type->value,
                'amount'   => round($amount ?? 0, 2),
            ];
        });

        return collect($trxGroups)->mapWithKeys(function ($types, $key) use ($converted) {
            $sum  = $converted->whereIn('trx_type', array_map(fn ($t) => $t->value, $types))->sum('amount');
            $type = $types[0];

            return [$key => [
                'title'       => $type->label(),
                'value'       => formatCurrency($sum),
                'icon'        => $type->icon(),
                'color_class' => $type->kebabCase(),
                'link'        => $key == 'rewards' || $key == 'voucher' ? null : route('admin.transaction', ['type' => $key]),
            ]];
        });
    }

    /**
     * Find transaction by trx ID.
     */
    public function findTransaction(string $trxId): ?Transaction
    {
        return Transaction::where('trx_id', $trxId)->first();
    }

    /*
    |--------------------------------------------------------------------------
    | Protected Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Prepare transaction data array from DTO.
     */
    protected function prepareTransactionData(TransactionData $data): array
    {
        return [
            'user_id'          => $data->user_id,
            'trx_type'         => $data->trx_type->value,
            'amount'           => $data->amount,
            'amount_flow'      => $data->amount_flow?->value,
            'fee'              => $data->fee,
            'currency'         => $data->currency ?? siteCurrency(),
            'provider'         => $data->provider,
            'processing_type'  => $data->processing_type->value,
            'net_amount'       => $data->net_amount,
            'payable_amount'   => $data->payable_amount,
            'payable_currency' => $data->payable_currency,
            'wallet_reference' => $data->wallet_reference,
            'trx_data'         => $data->trx_data,
            'remarks'          => $data->remarks,
            'description'      => ucfirst($data->description),
            'trx_reference'    => $data->trx_reference,
            'status'           => $data->status->value,
            'trx_token'        => $data->trx_token,
            'expires_at'       => $data->expires_at,
        ];
    }

    /**
     * Update transaction status and metadata.
     */
    protected function updateTransactionStatusWithRemarks(Transaction $transaction, TrxStatus $status, ?string $remarks = null, ?string $description = null): void
    {
        $transaction->update(array_filter([
            'status'      => $status,
            'remarks'     => $remarks,
            'description' => $description,
        ]));

        // 🟢 Sync local object with updated values
        $transaction->status      = $status;
        $transaction->remarks     = $remarks;
        $transaction->description = $description;
    }

    /**
     * Dispatch merchant IPN notification.
     */
    protected function sendMerchantPaymentIPN(Transaction $transaction, string $message, TrxStatus $status): void
    {
        if ($transaction->trx_type !== TrxType::RECEIVE_PAYMENT) {
            return;
        }

        try {
            $trxData      = $transaction->trx_data;
            $merchant     = Merchant::findOrFail($trxData['merchant_id']);
            $clientSecret = $merchant->getRawOriginal('api_secret');

            if (empty($clientSecret)) {
                Log::warning('IPN skipped because merchant has no legacy signing secret', [
                    'merchant_id' => $merchant->id,
                    'transaction_id' => $transaction->id,
                ]);
                return;
            }

            unset($trxData['merchant_id']);

            $payload = [
                'data'      => $trxData,
                'message'   => $message,
                'status'    => $status->value,
                'timestamp' => now()->timestamp,
            ];

            $signature = hash_hmac('sha256', json_encode($payload), $clientSecret);

            $response = Http::withHeaders([
                'X-Signature'  => $signature,
                'Content-Type' => 'application/json',
            ])->post($trxData['ipn_url'], $payload);

            if (! $response->successful()) {
                Log::error('IPN failed. Status Code: '.$response->status());
            }
        } catch (\Exception $e) {
            Log::error('IPN error: '.$e->getMessage());
        }
    }

    /**
     * Resolve transaction handler based on type.
     */
    protected function resolveHandler(Transaction $transaction): mixed
    {
        return match ($transaction->trx_type) {
            TrxType::DEPOSIT         => app(DepositHandler::class),
            TrxType::RECEIVE_PAYMENT => app(PaymentHandler::class),
            TrxType::REQUEST_MONEY   => app(RequestMoneyHandler::class),
            TrxType::WITHDRAW        => app(WithdrawHandler::class),
            default                  => null,
        };
    }

    /**
     * Process modern webhook DTO centrally inside DB::transaction to ensure atomicity.
     * Incorporates strong idempotency checks.
     */
    public function processModernWebhook(\App\Payment\Modern\DTO\WebhookDTO $dto, \App\Enums\ProviderType $provider): void
    {
        $lockKey = "webhook:{$provider->value}:" . ($dto->externalReference ?: $dto->providerTransactionId);
        $payloadHash = md5(json_encode($dto->rawPayload ?? []));

        // Verificação prévia de idempotência
        $processedEvent = \App\Models\ProcessedEvent::where('idempotency_key', $lockKey)->first();
        if ($processedEvent) {
            if ($processedEvent->payload_hash !== $payloadHash) {
                // Alerta de Replay com payload modificado
                app(\App\Console\Commands\ScanAnomaliesCommand::class)->registerAnomaly(
                    'idempotency_violation', 'CRITICAL', 'webhook', null, $lockKey,
                    "Payload mismatch on idempotent key {$lockKey}. Possible replay attack.",
                    ['expected_hash' => $processedEvent->payload_hash, 'received_hash' => $payloadHash],
                    []
                );

                \App\Models\PlatformIncident::create([
                    'title' => 'Security: Replay Attack Detected',
                    'severity' => \App\Models\PlatformIncident::SEVERITY_CRITICAL,
                    'status' => \App\Models\PlatformIncident::STATUS_OPEN,
                    'started_at' => now(),
                    'root_cause' => "Idempotency key {$lockKey} reused with modified payload.",
                ]);
                throw new \Exception("Suspicious webhook event. Payload mismatch on existing idempotent key.");
            }
            // Idempotent return se o payload bater
            return;
        }

        \Illuminate\Support\Facades\Cache::lock($lockKey, 30)->block(5, function () use ($dto, $provider, $lockKey, $payloadHash) {
            DB::transaction(function () use ($dto, $provider, $lockKey, $payloadHash) {
            $transaction = TenantBypass::run(fn () => Transaction::where('trx_id', $dto->providerTransactionId)->lockForUpdate()->first());
            
            if (!$transaction) {
                // For SANDBOX PIX/DEP, we may need to look up by external Reference.
                if ($dto->externalReference) {
                    $transaction = TenantBypass::run(fn () => Transaction::where('trx_id', $dto->externalReference)->lockForUpdate()->first());
                }
                
                if (!$transaction) {
                    throw new \Exception("Transaction not found for Webhook: {$dto->providerTransactionId}");
                }
            }

            // Status Map Handler
            if ($dto->status === 'PAID') {
                if ($transaction->status === TrxStatus::COMPLETED) {
                    // Idempotent success (already completed)
                    return;
                }
                
                if ($transaction->status !== TrxStatus::PENDING) {
                    throw new \Exception("Cannot complete a transaction that is not PENDING.");
                }

                $wallet = TenantBypass::run(fn () => \App\Models\Wallet::where('uuid', $transaction->wallet_reference)->firstOrFail());
                $systemRevenue = TenantBypass::run(fn () => \App\Models\Wallet::where('uuid', \App\Enums\SystemWalletUUID::SYSTEM_REVENUE->value)->firstOrFail());

                // If it's a deposit, money comes from GATEWAY_HOLDING to User Wallet
                $gatewayHoldingUuid = 'GATEWAY_' . $provider->value . '_HOLDING';
                $gatewayWallet = TenantBypass::run(fn () => \App\Models\Wallet::where('uuid', $gatewayHoldingUuid)->first());
                if (!$gatewayWallet) {
                    // Create dummy holding for sandbox/tests if it doesn't exist
                    $systemUser = TenantBypass::run(fn () => \App\Models\User::where('email', 'system_revenue@ledger.internal')->first());
                    $fallbackUser = TenantBypass::run(fn () => \App\Models\User::first());
                    $gatewayWallet = TenantBypass::run(fn () => \App\Models\Wallet::factory()->create([
                        'user_id' => $systemUser->id ?? $fallbackUser->id,
                        'uuid' => $gatewayHoldingUuid,
                        'balance' => 9999999 // Virtual huge pool
                    ]));
                }

                // Ler taxas do Snapshot (congeladas na criação)
                $trxData = $transaction->trx_data ?? [];
                $snapshot = $trxData['fee_snapshot'] ?? null;
                
                $platformFee = 0;
                $gatewayFee = 0;
                $netAmount = $transaction->amount;

                if ($snapshot) {
                    $platformFee = (float) ($snapshot['platform_fee_amount'] ?? 0);
                    $gatewayFee = (float) ($snapshot['provider_fee_amount'] ?? 0);
                    $netAmount = (float) ($snapshot['net_amount'] ?? $transaction->amount);
                    
                    // Se o webhook informou taxa real diferente da estimada
                    if (isset($dto->providerFee) && $dto->providerFee !== null) {
                        $realFee = (float) $dto->providerFee;
                        if ($realFee != $gatewayFee) {
                            $trxData['provider_fee_estimated_amount'] = $gatewayFee;
                            $trxData['provider_fee_real_amount'] = $realFee;
                            $trxData['provider_fee_adjustment_amount'] = $realFee - $gatewayFee;
                            
                            // Registra anomalia para análise humana, mas segue com a liquidação via snapshot
                            app(\App\Console\Commands\ScanAnomaliesCommand::class)->registerAnomaly(
                                'fee_mismatch', 'MEDIUM', 'transaction', $transaction->id, "fee_mismatch:{$transaction->id}",
                                "Taxa real do provider difere do snapshot. Estimado: {$gatewayFee}, Real: {$realFee}",
                                [], ['reconcile_fee_divergence']
                            );
                        }
                    }
                } else {
                    // Fallback para transações antigas sem snapshot
                    $platformFee = $transaction->fee;
                    $gatewayFee = $dto->providerFee ?? 0;
                    $netAmount = $transaction->amount - $platformFee - $gatewayFee;
                }

                if ($netAmount < 0) {
                    throw new \Exception("Split failed: Net amount is negative.");
                }

                // 1. Credit Merchant/User Wallet (Net Amount)
                app(\App\Services\LedgerService::class)->transfer(
                    $gatewayWallet,
                    $wallet,
                    $netAmount,
                    $transaction,
                    "Deposit approved via {$provider->value} (Net)",
                    ['transaction_id' => $transaction->id]
                );

                // 2. Transfer Platform Fee to SYSTEM_REVENUE
                if ($platformFee > 0) {
                    app(\App\Services\LedgerService::class)->transfer(
                        $gatewayWallet,
                        $systemRevenue,
                        $platformFee,
                        $transaction,
                        "Platform Fee deduction for {$transaction->trx_id}",
                        ['transaction_id' => $transaction->id]
                    );
                }

                // 3. Transfer Gateway Fee to GATEWAY_FEE_HOLDING
                if ($gatewayFee > 0) {
                    $gatewayFeeUuid = 'GATEWAY_' . $provider->value . '_FEE_HOLDING';
                    $gatewayFeeWallet = TenantBypass::run(fn () => \App\Models\Wallet::firstOrCreate(
                        ['uuid' => $gatewayFeeUuid],
                        ['user_id' => $systemRevenue->user_id, 'balance' => 0]
                    ));

                    app(\App\Services\LedgerService::class)->transfer(
                        $gatewayWallet,
                        $gatewayFeeWallet,
                        $gatewayFee,
                        $transaction,
                        "Gateway Fee for {$transaction->trx_id}",
                        ['transaction_id' => $transaction->id]
                    );
                }

                // Update Status
                $this->updateTransactionStatusWithRemarks($transaction, TrxStatus::COMPLETED, "Webhook PAID");
                $transaction->status = TrxStatus::COMPLETED;
                $transaction->save();

                // Modern webhooks settle funds through LedgerService above. Legacy handlers
                // also mutate balances, so they must not run here or deposits are credited twice.
            } 
            elseif ($dto->status === 'FAILED') {
                if ($transaction->status === TrxStatus::FAILED || $transaction->status === TrxStatus::COMPLETED) {
                    return; // idempotent
                }

                $this->updateTransactionStatusWithRemarks($transaction, TrxStatus::FAILED, "Webhook FAILED");
                $transaction->status = TrxStatus::FAILED;
                $transaction->save();
            }
            elseif ($dto->status === 'REFUNDED') {
                // Dynamic refund via Webhook
                // Use Gateway source since it's a webhook from Provider
                try {
                    $this->refundTransaction(
                        $transaction->trx_id, 
                        $dto->amount, 
                        'Webhook Refund', 
                        \App\Enums\FinancialSourceType::GATEWAY, 
                        'GATEWAY_' . $provider->value . '_HOLDING', 
                        $dto->providerTransactionId
                    );
                } catch (\Exception $e) {
                    if (str_contains($e->getMessage(), 'Duplicate refund')) {
                        return; // idempotent
                    }
                    throw $e;
                }
            }
            elseif ($dto->status === 'CHARGEBACK') {
                try {
                    $this->chargebackTransaction(
                        $transaction->trx_id,
                        $dto->amount,
                        'Webhook Chargeback',
                        \App\Enums\FinancialSourceType::GATEWAY,
                        'GATEWAY_' . $provider->value . '_HOLDING',
                        $dto->providerTransactionId
                    );
                } catch (\Exception $e) {
                    if (str_contains($e->getMessage(), 'Duplicate chargeback')) {
                        return; // idempotent
                    }
                    throw $e;
                }
            }
            // PROCESSING simply keeps it PENDING
            
            \App\Models\ProcessedEvent::updateOrCreate(
                ['idempotency_key' => $lockKey],
                [
                    'event_type' => 'webhook',
                    'source' => $provider->value,
                    'source_id' => $dto->providerTransactionId,
                    'status' => 'processed',
                    'payload_hash' => $payloadHash,
                    'processed_at' => now(),
                ]
            );

            });
        });
    }
}
