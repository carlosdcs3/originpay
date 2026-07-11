<?php

namespace App\Services;

use App\Constants\FixPctType;
use App\Data\TransactionData;
use App\Enums\AmountFlow;
use App\Enums\MethodType;
use App\Enums\TrxStatus;
use App\Enums\TrxType;
use App\Exceptions\NotifyErrorException;
use App\Models\DepositMethod;
use App\Models\Wallet as WalletModel;
use App\Payment\PaymentGatewayFactory;
use App\Services\Financial\WalletBalanceService;
use App\Services\Handlers\WithdrawHandler;
use App\Traits\FileManageTrait;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Log;
use Throwable;
use Transaction;
use Wallet;

class PaymentService
{
    use FileManageTrait;

    protected PaymentGatewayFactory $paymentFactory;
    protected WalletBalanceService $walletBalanceService;

    public function __construct(PaymentGatewayFactory $paymentFactory, WalletBalanceService $walletBalanceService)
    {
        $this->paymentFactory = $paymentFactory;
        $this->walletBalanceService = $walletBalanceService;
    }

    /**
     * Handle deposit via payment method.
     *
     * @throws Throwable
     */
    public function depositWithPaymentMethod($paymentMethodId, $amount, $walletId): array
    {
        DB::beginTransaction();

        try {
            $wallet = WalletModel::findOrFail($walletId);
            $depositMethod = DepositMethod::findOrFail($paymentMethodId);

            if ($amount <= 0) {
                throw new NotifyErrorException(__('Amount must be greater than zero.'));
            }

            if ($depositMethod->min_deposit > $amount || $depositMethod->max_deposit < $amount) {
                throw new NotifyErrorException(__('Amount must be between :min and :max.', ['min' => $depositMethod->min_deposit, 'max' => $depositMethod->max_deposit]));
            }

            $details = $this->calculateTransactionDetails($amount, $depositMethod);

            if ($depositMethod->type === MethodType::MANUAL) {
                $credentials = collect($depositMethod->fields)->map(function ($field) {
                    $credentials = request('credentials');

                    if (isset($credentials[$field['name']]) && is_file($credentials[$field['name']])) {
                        $field['value'] = self::uploadImage($credentials[$field['name']]);
                    } else {
                        $field['value'] = $credentials[$field['name']] ?? null;
                    }

                    return $field;
                });

                $details['trxData'] = $credentials->toArray();
            }

            $data = $this->createTransactionData($details, $depositMethod, $wallet, TrxType::DEPOSIT);

            $transaction = Transaction::create($data);
            $paymentGateway = $this->paymentFactory->getGateway($depositMethod->paymentGateway->code ?? $depositMethod->type->value);

            $redirectUrl = $paymentGateway->deposit($details['payableAmount'], $depositMethod->currency, $transaction->trx_id);

            DB::commit();

            return [$transaction, $redirectUrl];
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Deposit failed', [
                'error' => substr($e->getMessage(), 0, 100),
                'amount' => $amount ?? null,
                'wallet_id' => $walletId ?? null,
            ]);
            throw new Exception($e->getMessage());
        }
    }

    /**
     * Handle withdrawal process.
     *
     * @throws Throwable
     */
    public function withdrawMoney($withdrawAccount, $wallet, $amount)
    {
        DB::beginTransaction();

        try {
            $withdrawMethod = $withdrawAccount->withdrawMethod;

            if ($amount <= 0) {
                throw new NotifyErrorException(__('Amount must be greater than zero.'));
            }

            $details = $this->calculateTransactionDetails($amount, $withdrawMethod);
            $details['trxData'] = $withdrawAccount->credentials;

            $wallet = WalletModel::where('id', $wallet->id)->lockForUpdate()->firstOrFail();

            if ($wallet->available_balance < $details['payableAmount']) {
                throw new NotifyErrorException(__('Insufficient available balance to process this payment.'));
            }

            $amountToDebit = $details['payableAmount'];
            $operation = 'PIX_WITHDRAW';

            if (stripos($withdrawMethod->name, 'crypto') !== false) {
                $operation = 'CRYPTO_WITHDRAW';
            }

            $balances = \App\Models\WalletBalance::where('wallet_id', $wallet->id)
                ->where('available', '>=', $amountToDebit)
                ->with(['gateway.withdrawMethods'])
                ->get();

            $selectedGatewayId = null;

            foreach ($balances as $balance) {
                $gateway = $balance->gateway;

                if (! $gateway || ! $gateway->status) {
                    continue;
                }

                $operations = $gateway->operations ?? [];
                $supportsOperation = in_array($operation, $operations, true);

                if (! $supportsOperation && $operation === 'PIX_WITHDRAW') {
                    $supportsOperation = ((bool) $gateway->is_withdraw || (bool) $gateway->supports_withdrawal)
                        && $gateway->withdrawMethods->contains(fn ($method) => (bool) $method->status);
                }

                if ($supportsOperation) {
                    $selectedGatewayId = $gateway->id;
                    break;
                }
            }

            if (! $selectedGatewayId) {
                throw new NotifyErrorException(__('Seu saldo disponível não está alocado em um gateway com PIX Saque ativo para este valor.'));
            }

            $data = $this->createTransactionData($details, $withdrawMethod, $wallet, TrxType::WITHDRAW);
            $reservationKey = 'legacy-withdraw-reserve:'.Str::uuid()->toString();
            
            // gateway_id and operation are not direct columns on transactions table, they go into trx_data
            $trxDataArray = $data->trx_data ?? [];
            $trxDataArray['gateway_id'] = $selectedGatewayId;
            $trxDataArray['operation'] = $operation;
            $trxDataArray['wallet_reservation_key'] = $reservationKey;
            $data->trx_data = $trxDataArray;

            $this->walletBalanceService->reserveWithdrawalFunds($wallet->id, $selectedGatewayId, $amountToDebit, [
                'transaction_type' => 'legacy_withdraw_reserve',
                'description' => 'Legacy withdrawal request reservation',
                'correlation_id' => $reservationKey,
                'idempotency_key' => $reservationKey,
            ]);

            $transaction = Transaction::create($data);

            Log::info('Saque debitado da carteira com sucesso.', [
                'transaction_id' => $data->trx_id ?? null,
                'status' => 'PENDING_ADMIN',
                'amount' => $details['payableAmount'],
                'user_id' => $wallet->user_id,
            ]);

            if ($withdrawMethod->type === MethodType::AUTOMATIC) {
                $withdrawCredential = collect($details['trxData'])->pluck('value')->first();
                
                // Forced manual for Sprint 12 - Auditoria de Saques Manuais
                $shouldAutoWithdraw = false;

                if ($shouldAutoWithdraw) {
                    $paymentGateway = $this->paymentFactory->getGateway($withdrawMethod->paymentGateway->code);
                    $paymentGateway->withdraw($details['netAmount'], $withdrawMethod->currency, $transaction->trx_id, $withdrawCredential);
                }
            }

            if ($transaction->processing_type === MethodType::MANUAL) {
                app(WithdrawHandler::class)->handleSubmitted($transaction);
            }

            DB::commit();
        } catch (NotifyErrorException $e) {
            DB::rollBack();
            Log::warning('Withdrawal blocked', [
                'error' => substr($e->getMessage(), 0, 100),
                'amount' => $amount ?? null,
                'user_id' => $wallet->user_id ?? null,
            ]);

            throw $e;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Withdrawal failed', [
                'error' => substr($e->getMessage(), 0, 100),
                'amount' => $amount ?? null,
                'user_id' => $wallet->user_id ?? null,
            ]);
            throw new NotifyErrorException(__('Withdrawal processing failed. Please try again.'));
        }
    }

    /**
     * @throws Throwable
     */
    public function paymentWithPaymentMethod($paymentMethodCode, $transaction)
    {
        $depositMethod = DepositMethod::getByCode($paymentMethodCode);
        $amount = $transaction->amount;

        if ($amount <= 0) {
            throw new Exception(__('Amount must be greater than zero.'));
        }

        $paymentGateway = $this->paymentFactory->getGateway($depositMethod->paymentGateway->code ?? $depositMethod->type);

        return $paymentGateway->deposit($transaction->payable_amount, $depositMethod->currency, $transaction->trx_id);
    }

    public function generateToken($trxId, $minutesValid = 30): string
    {
        $payload = [
            'trx_id' => $trxId,
            'exp' => Carbon::now()->addMinutes($minutesValid)->timestamp,
        ];

        $jsonPayload = json_encode($payload);
        $base64Payload = base64_encode($jsonPayload);
        $secretKey = config('app.key');
        $signature = hash_hmac('sha256', $base64Payload, $secretKey);

        return $base64Payload.'.'.$signature;
    }

    /**
     * @throws NotifyErrorException
     */
    public function verifyTokenAndGetData($token)
    {
        [$base64Payload, $signature] = explode('.', $token);

        $secretKey = config('app.key');
        $expectedSignature = hash_hmac('sha256', $base64Payload, $secretKey);

        if (! hash_equals($expectedSignature, $signature)) {
            throw new NotifyErrorException(__('Invalid or tampered token.'));
        }

        $payloadJson = base64_decode($base64Payload, true);
        $payload = json_decode($payloadJson, true);

        if (isset($payload['exp']) && $payload['exp'] < now()->timestamp) {
            throw new NotifyErrorException(__('Token has expired.'));
        }

        return $payload;
    }

    /**
     * Calculate transaction charges and amounts.
     */
    protected function calculateTransactionDetails($amount, $method)
    {
        $charge = $this->calculateCharge($amount, $method->charge, $method->charge_type);
        $conversionRate = $method->conversion_rate;

        $netAmount = $amount * $conversionRate;
        $payableCharge = $charge * $conversionRate;
        $payableAmount = $netAmount + $payableCharge;

        return compact('amount', 'charge', 'netAmount', 'payableCharge', 'payableAmount');
    }

    /**
     * Helper to calculate charge based on type.
     */
    protected function calculateCharge($amount, $charge, $chargeType)
    {
        return $chargeType === FixPctType::PERCENT ? $amount * $charge / 100 : $charge;
    }

    /**
     * Create transaction data object.
     */
    protected function createTransactionData($details, $method, $wallet, $trxType)
    {
        return new TransactionData(
            user_id: auth()->id(),
            trx_type: $trxType,
            amount: $details['amount'],
            amount_flow: $trxType === TrxType::DEPOSIT ? AmountFlow::PLUS : AmountFlow::MINUS,
            fee: $details['charge'],
            provider: $method->name,
            processing_type: $method->type,
            net_amount: $details['netAmount'],
            payable_amount: $details['payableAmount'],
            payable_currency: $method->currency,
            wallet_reference: $wallet->uuid,
            trx_data: $details['trxData'] ?? null,
            description: __(':type via :method', ['type' => $trxType->value, 'method' => $method->name]),
            status: TrxStatus::PENDING
        );
    }
}
