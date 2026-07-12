<?php

namespace App\Services;

use App\Enums\AmountFlow;
use App\Enums\ChargeStatus;
use App\Enums\MethodType;
use App\Enums\PaymentMethod;
use App\Enums\TrxStatus;
use App\Enums\TrxType;
use App\Events\ChargePaidEvent;
use App\Exceptions\GatewayBusinessException;
use App\Exceptions\GatewayCapabilityException;
use App\Gateway\GatewayManager;
use App\Gateway\GatewayResolver;
use App\Models\Charge;
use App\Models\GatewayLog;
use App\Models\Scopes\TenantScope;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletBalance;
use App\Services\Fees\PlatformFeeResolver;
use App\Services\Fraud\FraudEngineService;
use App\Services\PaymentLinks\PaymentLinkAnalyticsService;
use App\Services\Security\TenantBypass;
use App\Support\Observability\Metrics\LocalMetricsCollector;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Throwable;

class ChargeService
{
    protected PlatformFeeService $feeService;

    protected PlatformFeeResolver $platformFeeResolver;

    protected WalletService $walletService;

    public function __construct(
        PlatformFeeService $feeService,
        PlatformFeeResolver $platformFeeResolver,
        WalletService $walletService
    ) {
        $this->feeService = $feeService;
        $this->platformFeeResolver = $platformFeeResolver;
        $this->walletService = $walletService;
    }

    /**
     * Create a new charge.
     */
    public function create(User $user, float $amount, string $paymentMethod, array $customerData = []): Charge
    {
        try {
            $charge = DB::transaction(function () use ($user, $amount, $paymentMethod, $customerData) {
                // 0. Avaliação de Fraude (Fraud Engine)
                /** @var FraudEngineService $fraudEngine */
                $fraudEngine = app(FraudEngineService::class);
                $riskResult = $fraudEngine->evaluateRisk($customerData, request()->ip() ?? '127.0.0.1', $user->id);

                if ($riskResult['is_blocked']) {
                    $reasons = implode(' ', $riskResult['reasons']);
                    throw new Exception("Transação bloqueada por risco elevado. Motivo: {$reasons}");
                }

                if ($amount <= 0) {
                    throw new Exception('Amount must be greater than zero.');
                }

                // Idempotency Check
                if (! empty($customerData['idempotency_key'])) {
                    $existing = Charge::where('idempotency_key', $customerData['idempotency_key'])
                        ->where('user_id', $user->id)
                        ->first();

                    if ($existing) {
                        return $existing;
                    }
                }

                $legacyFees = $this->feeService->calculateFee($amount);
                $gatewayFee = (float) ($legacyFees['gateway_fee'] ?? 0);

                $feeResult = $this->platformFeeResolver->resolve($user, $paymentMethod, $amount, 'BRL');
                if ($feeResult->source === 'fallback') {
                    Log::warning('Using fallback platform fee rule for charge creation.', [
                        'user_id' => $user->id,
                        'payment_method' => $paymentMethod,
                        'amount' => $amount,
                    ]);
                }

                $platformFee = $feeResult->platformFeeAmount;
                $netAmount = round($amount - $platformFee - $gatewayFee, 2);

                if ($netAmount < 0) {
                    throw new Exception('Fee calculation resulted in negative net amount.');
                }

                $wallet = $this->walletService->getDefaultWalletByUserId($user->id);

                $charge = new Charge([
                    'uuid' => Str::uuid()->toString(),
                    'correlation_id' => Str::uuid()->toString(),
                    'idempotency_key' => $customerData['idempotency_key'] ?? Str::uuid()->toString(),
                    'user_id' => $user->id,
                    'wallet_id' => $wallet ? $wallet->id : null,
                    'currency_id' => $wallet ? $wallet->currency_id : null,
                    'payment_method' => $paymentMethod,
                    'amount' => $amount,
                    'platform_fee' => $platformFee,
                    'gateway_fee' => $gatewayFee,
                    'fee_rule_id' => $feeResult->ruleId,
                    'fee_snapshot' => $feeResult->snapshot + [
                        'gateway_fee' => $gatewayFee,
                        'charge_net_amount' => $netAmount,
                    ],
                    'net_amount' => $netAmount,
                    'description' => $customerData['description'] ?? null,
                    'customer_name' => $customerData['name'] ?? null,
                    'customer_email' => $customerData['email'] ?? null,
                    'customer_document' => $customerData['document'] ?? null,
                    'status' => ChargeStatus::PENDING,
                    'expires_at' => now()->addDays(3),
                ]);

                // Persiste a cobrança antes de chamar o PSP (para ter ID disponível nos logs)
                $charge->save();

                $pmEnum = match ($paymentMethod) {
                    'pix' => PaymentMethod::PIX,
                    'card' => PaymentMethod::CARD,
                    'boleto' => PaymentMethod::BOLETO,
                    'crypto' => PaymentMethod::CRYPTO,
                    default => throw new Exception("Método de pagamento inválido: {$paymentMethod}")
                };
                $gateways = GatewayResolver::resolveAllForCharge($user, $pmEnum);

                $lastException = null;
                $success = false;
                $attempt = 0;

                /** @var CircuitBreakerService $circuitBreaker */
                $circuitBreaker = app(CircuitBreakerService::class);

                foreach ($gateways as $gatewayModel) {
                    $attempt++;
                    $chainPosition = $attempt === 1 ? 'primary' : 'fallback_'.($attempt - 1);

                    // Verifica permissão do Circuit Breaker (Trata HALF_OPEN max 3 reqs)
                    if (! $circuitBreaker->attemptRequest($gatewayModel->code)) {
                        $lastException = new Exception('Circuit Breaker limitou a tentativa.');

                        continue;
                    }

                    $metricsService = app(GatewayMetricsService::class);
                    $concurrencyKey = 'gateway:concurrency:'.$gatewayModel->code;
                    $concurrencyLimit = 30; // Pode vir do $gatewayModel futuramente

                    try {
                        $executeGatewayCharge = function () use ($gatewayModel, $charge, $circuitBreaker, $metricsService, $chainPosition, $pmEnum) {
                            $metricsService->increment('gateway_concurrency_acquired');

                            $adapter = GatewayManager::adapter($gatewayModel);
                            $charge->gateway_id = $gatewayModel->id;

                            $startTime = microtime(true);
                            if ($pmEnum === PaymentMethod::BOLETO) {
                                if (! $adapter->supportsBoleto()) {
                                    throw new GatewayCapabilityException("Gateway {$gatewayModel->code} nao suporta boleto.");
                                }

                                $response = $adapter->createBoleto($charge);
                            } else {
                                $response = $adapter->createCharge($charge);
                            }
                            $execTime = (int) round((microtime(true) - $startTime) * 1000);

                            if (is_array($response)) {
                                $charge->gateway_charge_id = $response['gateway_charge_id'] ?? $charge->gateway_charge_id;
                                $charge->gateway_reference = $response['gateway_reference'] ?? $charge->gateway_reference;
                                $charge->payment_link = $response['payment_link'] ?? $charge->payment_link;
                                $charge->boleto_url = $response['boleto_url'] ?? $charge->boleto_url;
                                $charge->boleto_pdf_url = $response['boleto_pdf_url'] ?? $charge->boleto_pdf_url;
                                $charge->barcode = $response['barcode'] ?? $charge->barcode;
                                $charge->digitable_line = $response['digitable_line'] ?? $charge->digitable_line;
                                $charge->qr_code = $response['qr_code'] ?? $charge->qr_code;
                                $charge->pix_copy_paste = $response['pix_copy_paste'] ?? $charge->pix_copy_paste;
                            }

                            GatewayLog::logEvent(
                                $gatewayModel->code,
                                ['action' => 'createCharge', 'charge_uuid' => $charge->uuid, 'chain_position' => $chainPosition],
                                ['status' => 'success', 'gateway_charge_id' => $charge->gateway_charge_id],
                                200, $execTime, $charge->uuid
                            );

                            $circuitBreaker->recordSuccess($gatewayModel->code);

                            return true;
                        };

                        try {
                            $redisLimiter = Redis::funnel($concurrencyKey)->limit($concurrencyLimit);
                            $result = $redisLimiter->then($executeGatewayCharge, function () use ($metricsService) {
                                $metricsService->increment('gateway_concurrency_rejected');

                                return false; // Lock limit reached
                            });
                        } catch (Throwable $redisException) {
                            Log::warning('Redis concurrency lock unavailable; processing charge without funnel.', [
                                'gateway' => $gatewayModel->code,
                                'charge_uuid' => $charge->uuid,
                                'reason' => $redisException->getMessage(),
                            ]);

                            $result = $executeGatewayCharge();
                        }

                        if ($result === false) {
                            // Limit reached, try fallback
                            $lastException = new Exception('Rate limit / Concurrency lock reached.');
                            GatewayLog::logEvent(
                                $gatewayModel->code,
                                ['action' => 'createCharge', 'charge_uuid' => $charge->uuid, 'chain_position' => $chainPosition],
                                ['status' => 'error', 'message' => 'Concurrency limit reached.', 'fallback_triggered' => true],
                                429, null, $charge->uuid
                            );

                            continue;
                        }

                        $success = true;
                        break; // Funcinou, sai do loop de fallback

                    } catch (GatewayBusinessException $e) {
                        // Erro de negócio: payload inválido, documento recusado, KYC, etc. Não deve ter fallback.
                        GatewayLog::logEvent(
                            $gatewayModel->code,
                            ['action' => 'createCharge', 'charge_uuid' => $charge->uuid, 'chain_position' => $chainPosition],
                            ['status' => 'error', 'message' => $e->getMessage(), 'error_type' => 'business_error', 'fallback_triggered' => false],
                            400, null, $charge->uuid
                        );

                        $charge->delete();
                        throw $e;
                    } catch (Throwable $e) {
                        $lastException = $e;
                        $exceptionForCircuit = $e instanceof Exception
                            ? $e
                            : new Exception($e->getMessage(), 0, $e);

                        GatewayLog::logEvent(
                            $gatewayModel->code,
                            ['action' => 'createCharge', 'charge_uuid' => $charge->uuid, 'chain_position' => $chainPosition],
                            ['status' => 'error', 'message' => $e->getMessage(), 'error_type' => 'operational_error', 'fallback_triggered' => true],
                            500, null, $charge->uuid
                        );

                        $circuitBreaker->recordFailure($gatewayModel->code, $exceptionForCircuit);

                        // Tenta o próximo provider (Fallback)
                        continue;
                    }
                }

                if (! $success) {
                    // Reverte o registro criado no banco, pois nenhum PSP aceitou
                    $charge->delete();
                    throw new Exception('Falha ao criar cobrança. Todos os adquirentes falharam. Último erro: '.($lastException ? $lastException->getMessage() : 'Desconhecido'));
                }

                $charge->status = ChargeStatus::WAITING_PAYMENT;
                $charge->save();

                return $charge;
            });
            $this->recordChargeCreationMetric('success');

            return $charge;
        } catch (Throwable $exception) {
            $this->recordChargeCreationMetric('failure');

            throw $exception;
        }
    }

    private function recordChargeCreationMetric(string $result): void
    {
        try {
            app(LocalMetricsCollector::class)->recordFinancialEvent('charge_creation', 'configured', $result);
        } catch (Throwable) {
            // Metrics must never affect charge creation.
        }
    }

    /**
     * Mark charge as paid.
     */
    public function markAsPaid(Charge $charge, string $gatewayEventId): void
    {
        DB::transaction(function () use ($charge, $gatewayEventId) {
            // Lock the charge to prevent concurrent webhooks modifying it
            $lockedCharge = Charge::where('id', $charge->id)->lockForUpdate()->first();

            if ($lockedCharge->status === ChargeStatus::PAID) {
                return; // Already paid
            }

            // Insert or Ignore the event to handle duplicate webhook idempotency gracefully
            $inserted = DB::table('charge_events')->insertOrIgnore([
                'charge_id' => $lockedCharge->id,
                'gateway_event_id' => $gatewayEventId,
                'event' => 'payment.paid',
                'payload' => json_encode(['gateway_event_id' => $gatewayEventId]),
                'processed_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if (! $inserted) {
                // Event already exists (handled by unique constraint)
                return;
            }

            $lockedCharge->status = ChargeStatus::PAID;
            $lockedCharge->paid_at = $lockedCharge->paid_at ?: now();
            $lockedCharge->save();

            // Track conversion for Payment Links
            app(PaymentLinkAnalyticsService::class)->markConverted($lockedCharge);

            // Credit amount to user's wallet and debit fee explicitly
            $wallet = $lockedCharge->wallet_id
                ? Wallet::withoutGlobalScope(TenantScope::class)->find($lockedCharge->wallet_id)
                : Wallet::withoutGlobalScope(TenantScope::class)->where('user_id', $lockedCharge->user_id)->first();

            if (! $wallet) {
                throw new Exception("Wallet not found for user {$lockedCharge->user_id} and charge {$lockedCharge->uuid}");
            }
            if ($wallet) {
                if (in_array($lockedCharge->payment_method->value, ['pix', 'boleto'], true)) {
                    $this->walletService->creditPending($wallet, $lockedCharge->amount, "Cobranca {$lockedCharge->uuid}", $lockedCharge);
                    $this->walletService->settlePendingToAvailable($wallet, $lockedCharge->amount, "Liquidacao cobranca {$lockedCharge->uuid}", $lockedCharge);

                    if ($lockedCharge->platform_fee > 0) {
                        $this->walletService->debitAvailable($wallet, $lockedCharge->platform_fee, 'fee', "Taxa cobranca {$lockedCharge->uuid}", $lockedCharge, true);
                    }
                } else {
                    $this->walletService->creditPending($wallet, $lockedCharge->amount, "Cobranca cartao {$lockedCharge->uuid}", $lockedCharge);

                    if ($lockedCharge->platform_fee > 0) {
                        $this->walletService->debitAvailable($wallet, $lockedCharge->platform_fee, 'fee', "Taxa cobranca cartao {$lockedCharge->uuid}", $lockedCharge, true);
                    }
                }

                $wallet = TenantBypass::run(
                    fn () => Wallet::withoutGlobalScope(TenantScope::class)
                        ->where('id', $wallet->id)
                        ->lockForUpdate()
                        ->firstOrFail()
                );

                $wallet->balance = round(
                    (float) $wallet->available_balance
                    + (float) $wallet->pending_balance
                    + (float) $wallet->reserved_balance
                    + (float) $wallet->rolling_reserve_balance,
                    2
                );
                $wallet->save();

                if ($lockedCharge->gateway_id && $lockedCharge->net_amount > 0) {
                    $walletBalance = WalletBalance::firstOrCreate(
                        [
                            'wallet_id' => $wallet->id,
                            'gateway_id' => $lockedCharge->gateway_id,
                        ],
                        [
                            'available' => 0,
                            'pending' => 0,
                            'blocked' => 0,
                        ]
                    );

                    $walletBalance = WalletBalance::where('id', $walletBalance->id)
                        ->lockForUpdate()
                        ->firstOrFail();

                    $walletBalance->available += $lockedCharge->net_amount;
                    $walletBalance->save();
                }

                $existingReceivePayment = TenantBypass::run(
                    fn () => Transaction::withoutGlobalScope(TenantScope::class)
                        ->where('trx_type', TrxType::RECEIVE_PAYMENT)
                        ->where('trx_reference', (string) $lockedCharge->id)
                        ->first()
                );

                if (! $existingReceivePayment) {
                    Transaction::create([
                        'user_id' => $lockedCharge->user_id,
                        'trx_type' => TrxType::RECEIVE_PAYMENT,
                        'description' => "Recebimento de cobranca {$lockedCharge->uuid}",
                        'provider' => $lockedCharge->payment_method->value,
                        'processing_type' => MethodType::AUTOMATIC,
                        'amount' => $lockedCharge->amount,
                        'amount_flow' => AmountFlow::PLUS,
                        'fee' => (float) $lockedCharge->platform_fee + (float) $lockedCharge->gateway_fee,
                        'currency' => $wallet->currency?->code ?? 'BRL',
                        'net_amount' => $lockedCharge->net_amount,
                        'payable_amount' => $lockedCharge->amount,
                        'payable_currency' => $wallet->currency?->code ?? 'BRL',
                        'wallet_reference' => $wallet->uuid,
                        'trx_reference' => (string) $lockedCharge->id,
                        'trx_data' => [
                            'charge_id' => $lockedCharge->id,
                            'charge_uuid' => $lockedCharge->uuid,
                            'gateway_id' => $lockedCharge->gateway_id,
                            'gateway_charge_id' => $lockedCharge->gateway_charge_id,
                        ],
                        'status' => TrxStatus::COMPLETED,
                    ]);
                }
            }
        });

        // Dispara o evento assíncrono para processamento não-transacional (E-mails, Webhooks, etc)
        // Recarregamos a cobrança para garantir que está com o status atualizado
        $charge->refresh();
        if ($charge->status === ChargeStatus::PAID) {
            Event::dispatch(new ChargePaidEvent($charge, (float) $charge->amount));
        }
    }

    /**
     * Cancel a charge.
     */
    public function cancel(Charge $charge, GatewayAdapter $gatewayAdapter): void
    {
        if ($charge->status === ChargeStatus::PAID) {
            throw new Exception('Cannot cancel a paid charge.');
        }

        $gatewayAdapter->cancelCharge($charge);

        $charge->status = ChargeStatus::CANCELLED;
        $charge->save();
    }

    /**
     * Expire a charge.
     */
    public function expire(Charge $charge): void
    {
        if ($charge->status !== ChargeStatus::WAITING_PAYMENT) {
            return;
        }

        $charge->status = ChargeStatus::EXPIRED;
        $charge->save();
    }

    /**
     * Refund a charge.
     */
    public function refund(Charge $charge, \App\Gateway\GatewayAdapter $gatewayAdapter): void
    {
        DB::transaction(function () use ($charge, $gatewayAdapter) {
            $lockedCharge = Charge::where('id', $charge->id)->lockForUpdate()->first();

            if ($lockedCharge->status !== ChargeStatus::PAID) {
                throw new Exception('Only paid charges can be refunded.');
            }

            $gatewayAdapter->refund($lockedCharge);

            $lockedCharge->status = ChargeStatus::REFUNDED;
            $lockedCharge->save();

            // Debit user's wallet
            $wallet = $lockedCharge->wallet_id
                ? Wallet::find($lockedCharge->wallet_id)
                : $this->walletService->getDefaultWalletByUserId($lockedCharge->user_id);

            if ($wallet) {
                // Allows negative balance implicitly if handled by debitAvailable (but debitAvailable throws Exception if not enough)
                // Wait, debitAvailable throws exception if balance < amount. In gateways, refund can negative balance.
                // We should add a force parameter or handle negative balance explicitly.
                $this->walletService->debitAvailable($wallet, $lockedCharge->net_amount, 'refund', "Reembolso Cobrança {$lockedCharge->uuid}", $lockedCharge, true);
            }
        });
    }
}
