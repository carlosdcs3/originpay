<?php

namespace App\Services\Financial;

use App\Models\Wallet;
use App\Models\WalletBalance;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WalletBalanceService
{
    /**
     * Adiciona fundos reais (Cash-In) provenientes de um Gateway (via Charge Paga).
     */
    public function creditGateway(int $walletId, int $gatewayId, float $amount, array $params): WalletTransaction
    {
        $this->assertPositiveAmount($amount);

        return DB::transaction(function () use ($walletId, $gatewayId, $amount, $params) {
            if ($existing = $this->findExistingByIdempotencyKey($params['idempotency_key'] ?? null)) {
                return $existing;
            }
            $wallet = Wallet::where('id', $walletId)->lockForUpdate()->firstOrFail();

            $walletBalance = WalletBalance::firstOrCreate(
                ['wallet_id' => $walletId, 'gateway_id' => $gatewayId],
                ['available' => 0, 'pending' => 0, 'blocked' => 0]
            );

            $walletBalance = WalletBalance::where('id', $walletBalance->id)->lockForUpdate()->firstOrFail();
            $oldBalance = $wallet->balance;

            $walletBalance->available += $amount;
            $walletBalance->save();

            $wallet->balance += $amount;
            $wallet->available_balance += $amount;
            $wallet->save();

            $transaction = WalletTransaction::create([
                'user_id' => $wallet->user_id,
                'wallet_id' => $wallet->id,
                'type' => $params['transaction_type'] ?? 'credit',
                'amount' => $amount,
                'correlation_id' => $params['correlation_id'] ?? Str::uuid()->toString(),
                'idempotency_key' => $params['idempotency_key'] ?? Str::uuid()->toString(),
                'balance_before' => $oldBalance,
                'balance_after' => $wallet->balance,
                'description' => $params['description'] ?? 'Credito via Gateway',
                'reference_type' => $params['reference_type'] ?? null,
                'reference_id' => $params['reference_id'] ?? null,
            ]);

            Log::channel('audit')->info('Wallet Credited', ['wallet' => $walletId, 'amount' => $amount, 'tx' => $transaction->id]);

            return $transaction;
        });
    }

    /**
     * Debita fundos (Cash-Out, Settlement, Chargeback Loss).
     */
    public function debitGateway(int $walletId, int $gatewayId, float $amount, array $params): WalletTransaction
    {
        $this->assertPositiveAmount($amount);

        return DB::transaction(function () use ($walletId, $gatewayId, $amount, $params) {
            if ($existing = $this->findExistingByIdempotencyKey($params['idempotency_key'] ?? null)) {
                return $existing;
            }
            $wallet = Wallet::where('id', $walletId)->lockForUpdate()->firstOrFail();
            $walletBalance = WalletBalance::where('wallet_id', $walletId)
                ->where('gateway_id', $gatewayId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($walletBalance->available < $amount) {
                throw new \Exception("Saldo insuficiente no gateway_id {$gatewayId}.");
            }

            if ($wallet->available_balance < $amount || $wallet->balance < $amount) {
                throw new \Exception('Insufficient available balance in wallet for gateway debit.');
            }

            $oldBalance = $wallet->balance;

            $walletBalance->available -= $amount;
            $walletBalance->save();

            $wallet->balance -= $amount;
            $wallet->available_balance -= $amount;
            $wallet->save();

            $transaction = WalletTransaction::create([
                'user_id' => $wallet->user_id,
                'wallet_id' => $wallet->id,
                'type' => $params['transaction_type'] ?? 'debit',
                'amount' => -$amount,
                'correlation_id' => $params['correlation_id'] ?? Str::uuid()->toString(),
                'idempotency_key' => $params['idempotency_key'] ?? Str::uuid()->toString(),
                'balance_before' => $oldBalance,
                'balance_after' => $wallet->balance,
                'description' => $params['description'] ?? 'Debito via Gateway',
                'reference_type' => $params['reference_type'] ?? null,
                'reference_id' => $params['reference_id'] ?? null,
            ]);

            Log::channel('audit')->info('Wallet Debited', ['wallet' => $walletId, 'amount' => $amount, 'tx' => $transaction->id]);

            return $transaction;
        });
    }

    /**
     * Bloqueia fundos (Ex: Chargeback Disputa Abertura, Retencao).
     */
    public function blockFunds(int $walletId, int $gatewayId, float $amount, array $params): WalletTransaction
    {
        $this->assertPositiveAmount($amount);

        return DB::transaction(function () use ($walletId, $gatewayId, $amount, $params) {
            if ($existing = $this->findExistingByIdempotencyKey($params['idempotency_key'] ?? null)) {
                return $existing;
            }
            $wallet = Wallet::where('id', $walletId)->lockForUpdate()->firstOrFail();
            $walletBalance = WalletBalance::where('wallet_id', $walletId)
                ->where('gateway_id', $gatewayId)
                ->lockForUpdate()
                ->firstOrFail();

            $oldBalance = $wallet->balance;

            if ($walletBalance->available < $amount) {
                throw new \Exception("Saldo insuficiente no gateway_id {$gatewayId} para bloqueio.");
            }

            if ($wallet->available_balance < $amount) {
                throw new \Exception('Insufficient available balance in wallet for funds block.');
            }

            $walletBalance->available -= $amount;
            $walletBalance->blocked += $amount;
            $walletBalance->save();

            $wallet->balance -= $amount;
            $wallet->available_balance -= $amount;
            $wallet->save();

            $transaction = WalletTransaction::create([
                'user_id' => $wallet->user_id,
                'wallet_id' => $wallet->id,
                'type' => $params['transaction_type'] ?? 'block',
                'amount' => -$amount,
                'correlation_id' => $params['correlation_id'] ?? Str::uuid()->toString(),
                'idempotency_key' => $params['idempotency_key'] ?? Str::uuid()->toString(),
                'balance_before' => $oldBalance,
                'balance_after' => $wallet->balance,
                'description' => $params['description'] ?? 'Bloqueio de Saldo',
                'reference_type' => $params['reference_type'] ?? null,
                'reference_id' => $params['reference_id'] ?? null,
            ]);

            return $transaction;
        });
    }

    /**
     * Libera fundos (Ex: Chargeback Ganho, Liberacao de Retencao).
     */
    public function releaseFunds(int $walletId, int $gatewayId, float $amount, array $params): WalletTransaction
    {
        $this->assertPositiveAmount($amount);

        return DB::transaction(function () use ($walletId, $gatewayId, $amount, $params) {
            if ($existing = $this->findExistingByIdempotencyKey($params['idempotency_key'] ?? null)) {
                return $existing;
            }
            $wallet = Wallet::where('id', $walletId)->lockForUpdate()->firstOrFail();
            $walletBalance = WalletBalance::where('wallet_id', $walletId)
                ->where('gateway_id', $gatewayId)
                ->lockForUpdate()
                ->firstOrFail();

            $oldBalance = $wallet->balance;

            if ($walletBalance->blocked < $amount) {
                throw new \Exception("Saldo bloqueado insuficiente no gateway_id {$gatewayId} para liberacao.");
            }

            $walletBalance->blocked -= $amount;
            $walletBalance->available += $amount;
            $walletBalance->save();

            $wallet->balance += $amount;
            $wallet->available_balance += $amount;
            $wallet->save();

            $transaction = WalletTransaction::create([
                'user_id' => $wallet->user_id,
                'wallet_id' => $wallet->id,
                'type' => $params['transaction_type'] ?? 'release',
                'amount' => $amount,
                'correlation_id' => $params['correlation_id'] ?? Str::uuid()->toString(),
                'idempotency_key' => $params['idempotency_key'] ?? Str::uuid()->toString(),
                'balance_before' => $oldBalance,
                'balance_after' => $wallet->balance,
                'description' => $params['description'] ?? 'Liberacao de Saldo Bloqueado',
                'reference_type' => $params['reference_type'] ?? null,
                'reference_id' => $params['reference_id'] ?? null,
            ]);

            return $transaction;
        });
    }

    /**
     * Reserva fundos de saque legado sem reduzir o saldo total da carteira.
     * Efeito esperado:
     * - wallet.available_balance -= amount
     * - wallet.reserved_balance += amount
     * - wallet.balance permanece igual
     * - wallet_balances.available -= amount
     * - wallet_balances.blocked += amount
     */
    public function reserveWithdrawalFunds(int $walletId, int $gatewayId, float $amount, array $params = []): WalletTransaction
    {
        $this->assertPositiveAmount($amount);

        return DB::transaction(function () use ($walletId, $gatewayId, $amount, $params) {
            if ($existing = $this->findExistingByIdempotencyKey($params['idempotency_key'] ?? null)) {
                return $existing;
            }

            [$wallet, $walletBalance] = $this->lockWalletState($walletId, $gatewayId);

            if ($wallet->available_balance < $amount) {
                throw new \Exception('Insufficient available balance in wallet for withdrawal reservation.');
            }

            if ($walletBalance->available < $amount) {
                throw new \Exception("Saldo insuficiente no gateway_id {$gatewayId} para reservar saque.");
            }

            $oldBalance = (float) $wallet->balance;
            $before = $this->snapshotState($wallet, $walletBalance);

            $walletBalance->available -= $amount;
            $walletBalance->blocked += $amount;
            $walletBalance->save();

            $wallet->available_balance -= $amount;
            $wallet->reserved_balance = (float) ($wallet->reserved_balance ?? 0) + $amount;
            $wallet->save();

            return $this->createWalletTransaction(
                wallet: $wallet,
                amount: 0,
                balanceBefore: $oldBalance,
                balanceAfter: (float) $wallet->balance,
                params: $params,
                defaultType: 'withdrawal_reserve',
                defaultDescription: 'Legacy withdrawal reservation',
                metadata: [
                    'movement_amount' => $amount,
                    'before' => $before,
                    'after' => $this->snapshotState($wallet, $walletBalance),
                ]
            );
        });
    }

    /**
     * Libera uma reserva de saque legado sem alterar o saldo total da carteira.
     * Efeito esperado:
     * - wallet.available_balance += amount
     * - wallet.reserved_balance -= amount
     * - wallet.balance permanece igual
     * - wallet_balances.blocked -= amount
     * - wallet_balances.available += amount
     */
    public function releaseWithdrawalFunds(int $walletId, int $gatewayId, float $amount, array $params = []): WalletTransaction
    {
        $this->assertPositiveAmount($amount);

        return DB::transaction(function () use ($walletId, $gatewayId, $amount, $params) {
            if ($existing = $this->findExistingByIdempotencyKey($params['idempotency_key'] ?? null)) {
                return $existing;
            }

            [$wallet, $walletBalance] = $this->lockWalletState($walletId, $gatewayId);

            if ($walletBalance->blocked < $amount) {
                throw new \Exception("Saldo bloqueado insuficiente no gateway_id {$gatewayId} para liberar saque.");
            }

            if ((float) ($wallet->reserved_balance ?? 0) < $amount) {
                throw new \Exception("Saldo reservado insuficiente na carteira {$walletId} para liberar saque.");
            }

            $oldBalance = (float) $wallet->balance;
            $before = $this->snapshotState($wallet, $walletBalance);

            $walletBalance->blocked -= $amount;
            $walletBalance->available += $amount;
            $walletBalance->save();

            $wallet->reserved_balance -= $amount;
            $wallet->available_balance += $amount;
            $wallet->save();

            return $this->createWalletTransaction(
                wallet: $wallet,
                amount: 0,
                balanceBefore: $oldBalance,
                balanceAfter: (float) $wallet->balance,
                params: $params,
                defaultType: 'withdrawal_release',
                defaultDescription: 'Legacy withdrawal reservation release',
                metadata: [
                    'movement_amount' => $amount,
                    'before' => $before,
                    'after' => $this->snapshotState($wallet, $walletBalance),
                ]
            );
        });
    }

    /**
     * Liquida uma reserva de saque legado.
     * Efeito esperado:
     * - wallet.balance -= amount
     * - wallet.reserved_balance -= amount
     * - wallet.available_balance permanece igual
     * - wallet.withdrawn_balance += amount
     * - wallet_balances.blocked -= amount
     */
    public function settleWithdrawalFunds(int $walletId, int $gatewayId, float $amount, array $params = []): WalletTransaction
    {
        $this->assertPositiveAmount($amount);

        return DB::transaction(function () use ($walletId, $gatewayId, $amount, $params) {
            if ($existing = $this->findExistingByIdempotencyKey($params['idempotency_key'] ?? null)) {
                return $existing;
            }

            [$wallet, $walletBalance] = $this->lockWalletState($walletId, $gatewayId);

            if ($walletBalance->blocked < $amount) {
                throw new \Exception("Saldo bloqueado insuficiente no gateway_id {$gatewayId} para liquidar saque.");
            }

            if ((float) ($wallet->reserved_balance ?? 0) < $amount) {
                throw new \Exception("Saldo reservado insuficiente na carteira {$walletId} para liquidar saque.");
            }

            $oldBalance = (float) $wallet->balance;
            $before = $this->snapshotState($wallet, $walletBalance);

            $walletBalance->blocked -= $amount;
            $walletBalance->save();

            $wallet->reserved_balance -= $amount;
            $wallet->withdrawn_balance = (float) ($wallet->withdrawn_balance ?? 0) + $amount;
            $wallet->balance -= $amount;
            $wallet->save();

            return $this->createWalletTransaction(
                wallet: $wallet,
                amount: -$amount,
                balanceBefore: $oldBalance,
                balanceAfter: (float) $wallet->balance,
                params: $params,
                defaultType: 'withdrawal_settlement',
                defaultDescription: 'Legacy withdrawal settlement',
                metadata: [
                    'movement_amount' => $amount,
                    'before' => $before,
                    'after' => $this->snapshotState($wallet, $walletBalance),
                ]
            );
        });
    }

    public function rebuildBalance(int $walletId): void
    {
        DB::transaction(function () use ($walletId) {
            $wallet = Wallet::where('id', $walletId)->lockForUpdate()->first();
            if (!$wallet) {
                throw new \Exception("Carteira {$walletId} nao encontrada para reconstrucao.");
            }

            $transactions = WalletTransaction::where('wallet_id', $walletId)->orderBy('id', 'asc')->get();
            $expectedBalance = 0.0;
            foreach ($transactions as $tx) {
                $expectedBalance += (float) $tx->amount;
            }

            if ((float) $wallet->balance !== $expectedBalance) {
                Log::channel('audit')->warning('Wallet Rebuild...', ['old' => $wallet->balance, 'new' => $expectedBalance]);
            }

            $wallet->balance = $expectedBalance;
            $wallet->save();
        });
    }

    protected function findExistingByIdempotencyKey(?string $idempotencyKey): ?WalletTransaction
    {
        if (! $idempotencyKey) {
            return null;
        }

        return WalletTransaction::where('idempotency_key', $idempotencyKey)->first();
    }

    protected function assertPositiveAmount(float $amount): void
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Financial amount must be greater than zero.');
        }
    }

    protected function lockWalletState(int $walletId, int $gatewayId): array
    {
        $wallet = Wallet::where('id', $walletId)->lockForUpdate()->firstOrFail();
        $walletBalance = WalletBalance::where('wallet_id', $walletId)
            ->where('gateway_id', $gatewayId)
            ->lockForUpdate()
            ->firstOrFail();

        return [$wallet, $walletBalance];
    }

    protected function snapshotState(Wallet $wallet, WalletBalance $walletBalance): array
    {
        return [
            'wallet' => [
                'balance' => (float) $wallet->balance,
                'available_balance' => (float) $wallet->available_balance,
                'reserved_balance' => (float) ($wallet->reserved_balance ?? 0),
                'withdrawn_balance' => (float) ($wallet->withdrawn_balance ?? 0),
            ],
            'gateway' => [
                'available' => (float) $walletBalance->available,
                'pending' => (float) $walletBalance->pending,
                'blocked' => (float) $walletBalance->blocked,
            ],
        ];
    }

    protected function createWalletTransaction(
        Wallet $wallet,
        float $amount,
        float $balanceBefore,
        float $balanceAfter,
        array $params,
        string $defaultType,
        string $defaultDescription,
        array $metadata = []
    ): WalletTransaction {
        return WalletTransaction::create([
            'user_id' => $wallet->user_id,
            'wallet_id' => $wallet->id,
            'type' => $params['transaction_type'] ?? $defaultType,
            'amount' => $amount,
            'correlation_id' => $params['correlation_id'] ?? Str::uuid()->toString(),
            'idempotency_key' => $params['idempotency_key'] ?? Str::uuid()->toString(),
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'description' => $params['description'] ?? $defaultDescription,
            'reference_type' => $params['reference_type'] ?? null,
            'reference_id' => $params['reference_id'] ?? null,
            'metadata' => array_merge($params['metadata'] ?? [], $metadata),
        ]);
    }
}
