<?php

namespace App\Services;

use App\Models\LedgerEntry;
use App\Models\Wallet;
use App\Models\Transaction;
use App\Exceptions\NotifyErrorException;
use App\Services\Security\TenantBypass;
use Illuminate\Support\Facades\DB;
use Exception;
use App\Services\Compliance\AccountRestrictionService;

class LedgerService
{
    /**
     * Lock multiple wallets in a deterministic order (by ID ascending) to prevent deadlocks.
     * Returns an associative array of locked wallets keyed by original ID.
     */
    protected function lockWalletsInOrder(array $wallets): array
    {
        // Deduplicate wallets
        $uniqueWallets = [];
        foreach ($wallets as $wallet) {
            $uniqueWallets[$wallet->id] = $wallet;
        }

        // Sort ascending by ID
        ksort($uniqueWallets);

        $lockedWallets = [];
        foreach ($uniqueWallets as $id => $wallet) {
            $lockedWallets[$id] = TenantBypass::run(
                fn () => Wallet::where('id', $id)->lockForUpdate()->firstOrFail()
            );
        }

        return $lockedWallets;
    }

    public function credit(Wallet $wallet, float $amount, ?Transaction $transaction = null, ?string $description = null, ?array $metadata = null): LedgerEntry
    {
        if ($amount <= 0) {
            throw new NotifyErrorException('Ledger amount must be greater than zero.');
        }

        if ($wallet->uuid === \App\Enums\SystemWalletUUID::SYSTEM_GENERAL->value) {
            if (!isset($metadata['legacy_call']) || $metadata['legacy_call'] !== true) {
                throw new NotifyErrorException('SYSTEM-GENERAL wallet cannot be used in new ledger flows.');
            }
        }

        if ($wallet->user_id) {
            $restrictionService = app(AccountRestrictionService::class);
            $restrictionService->checkRestrictionOrThrow($wallet->user, 'DEPOSIT_BLOCK');
        }

        if ($amount > 0 && !\App\Models\FeatureFlag::isActive('deposits_enabled') && (!isset($metadata['bypass_feature_flags']) || !$metadata['bypass_feature_flags'])) {
            // We can bypass for internal system credits if needed, but otherwise block.
            throw new NotifyErrorException("Deposits are currently disabled globally by the administrator.");
        }

        return DB::transaction(function () use ($wallet, $amount, $transaction, $description, $metadata) {
            $lockedWallet = TenantBypass::run(
                fn () => Wallet::where('id', $wallet->id)->lockForUpdate()->firstOrFail()
            );

            $rollingReservePercent = $metadata['rolling_reserve_percent'] ?? 0;
            $rollingReserveDays = $metadata['rolling_reserve_days'] ?? 7;
            
            $rollingReserveAmount = 0;
            if ($rollingReservePercent > 0 && $lockedWallet->user_id) {
                $rollingReserveAmount = $amount * ($rollingReservePercent / 100);
            }

            $lockedWallet->balance += $amount;
            $lockedWallet->available_balance += ($amount - $rollingReserveAmount);
            $lockedWallet->rolling_reserve_balance += $rollingReserveAmount;
            $lockedWallet->save();

            if ($rollingReserveAmount > 0) {
                \App\Models\RollingReserve::create([
                    'user_id' => $lockedWallet->user_id,
                    'wallet_id' => $lockedWallet->id,
                    'transaction_id' => $transaction?->id,
                    'amount' => $rollingReserveAmount,
                    'status' => 'HELD',
                    'release_at' => now()->addDays($rollingReserveDays)
                ]);

                LedgerEntry::create([
                    'transaction_id' => $transaction?->id,
                    'wallet_id' => $lockedWallet->id,
                    'direction' => 'debit',
                    'amount' => $rollingReserveAmount,
                    'currency' => $lockedWallet->currency->code ?? 'USD',
                    'balance_after' => $lockedWallet->balance,
                    'description' => 'Rolling Reserve Hold',
                    'metadata' => array_merge($metadata ?? [], ['type' => 'ROLLING_RESERVE_HOLD']),
                    'created_at' => now(),
                ]);
            }

            $entry = LedgerEntry::create([
                'transaction_id' => $transaction?->id,
                'wallet_id' => $lockedWallet->id,
                'direction' => 'credit',
                'amount' => $amount,
                'currency' => $lockedWallet->currency->code ?? 'USD',
                'balance_after' => $lockedWallet->balance,
                'description' => $description,
                'metadata' => $metadata,
                'created_at' => now(),
            ]);

            if ($lockedWallet->user_id) {
                app(\App\Services\Compliance\UserExposureService::class)->evaluateExposure($lockedWallet->user);
            }

            return $entry;
        });
    }

    public function debit(Wallet $wallet, float $amount, ?Transaction $transaction = null, ?string $description = null, ?array $metadata = null): LedgerEntry
    {
        if ($amount <= 0) {
            throw new NotifyErrorException('Ledger amount must be greater than zero.');
        }

        if ($wallet->uuid === \App\Enums\SystemWalletUUID::SYSTEM_GENERAL->value) {
            if (!isset($metadata['legacy_call']) || $metadata['legacy_call'] !== true) {
                throw new NotifyErrorException('SYSTEM-GENERAL wallet cannot be used in new ledger flows.');
            }
        }

        if ($wallet->user_id) {
            $restrictionService = app(AccountRestrictionService::class);
            $restrictionService->checkRestrictionOrThrow($wallet->user, 'WITHDRAW_BLOCK');
        }

        return DB::transaction(function () use ($wallet, $amount, $transaction, $description, $metadata) {
            $lockedWallet = TenantBypass::run(
                fn () => Wallet::where('id', $wallet->id)->lockForUpdate()->firstOrFail()
            );

            if ($lockedWallet->available_balance < $amount) {
                throw new NotifyErrorException('Insufficient available balance in wallet for ledger debit.');
            }

            $lockedWallet->balance -= $amount;
            $lockedWallet->available_balance -= $amount;
            $lockedWallet->save();

            return LedgerEntry::create([
                'transaction_id' => $transaction?->id,
                'wallet_id' => $lockedWallet->id,
                'direction' => 'debit',
                'amount' => $amount,
                'currency' => $lockedWallet->currency->code ?? 'USD',
                'balance_after' => $lockedWallet->balance,
                'description' => $description,
                'metadata' => $metadata,
                'created_at' => now(),
            ]);
        });
    }

    public function transfer(Wallet $fromWallet, Wallet $toWallet, float $amount, ?Transaction $transaction = null, ?string $description = null, ?array $metadata = null): array
    {
        return DB::transaction(function () use ($fromWallet, $toWallet, $amount, $transaction, $description, $metadata) {
            if ($fromWallet->id === $toWallet->id) {
                throw new NotifyErrorException('Cannot transfer to the same wallet.');
            }

            $locked = $this->lockWalletsInOrder([$fromWallet, $toWallet]);
            
            $debitEntry = $this->debit($locked[$fromWallet->id], $amount, $transaction, $description, $metadata);
            $creditEntry = $this->credit($locked[$toWallet->id], $amount, $transaction, $description, $metadata);

            return [$debitEntry, $creditEntry];
        });
    }

    /**
     * Splits an amount from one wallet into multiple destination wallets.
     * $destinations format: [['wallet' => Wallet, 'amount' => float], ...]
     */
    public function split(Wallet $fromWallet, float $totalDebitAmount, array $destinations, ?Transaction $transaction = null, ?string $description = null, ?array $metadata = null): array
    {
        if ($totalDebitAmount <= 0) {
            throw new NotifyErrorException('Total debit amount must be greater than zero.');
        }

        $walletsToLock = [$fromWallet];
        $totalCredits = 0.0;
        $seenWalletIds = [];

        foreach ($destinations as $dest) {
            $w = $dest['wallet'];
            $amt = (float) $dest['amount'];

            if ($amt <= 0) {
                throw new NotifyErrorException('Split destination amount must be greater than zero.');
            }

            if (in_array($w->id, $seenWalletIds)) {
                throw new NotifyErrorException('Duplicate destination wallet detected in split. Aggregate amounts first.');
            }
            $seenWalletIds[] = $w->id;

            $totalCredits += $amt;
            $walletsToLock[] = $w;
        }

        // Float comparison with small epsilon
        if (abs($totalDebitAmount - $totalCredits) > 0.001) {
            throw new NotifyErrorException('Sum of split amounts does not equal the total debit amount.');
        }

        return DB::transaction(function () use ($fromWallet, $totalDebitAmount, $destinations, $transaction, $description, $metadata, $walletsToLock) {
            $locked = $this->lockWalletsInOrder($walletsToLock);

            $entries = [];
            
            // Single debit for the payer
            $entries[] = $this->debit($locked[$fromWallet->id], $totalDebitAmount, $transaction, $description, $metadata);

            // Multiple credits
            foreach ($destinations as $dest) {
                $lockedWallet = $locked[$dest['wallet']->id];
                $entries[] = $this->credit($lockedWallet, $dest['amount'], $transaction, $description, $metadata);
            }

            return $entries;
        });
    }

    /**
     * FX Exchange. Debits source, credits target, credits spread to revenue.
     */
    public function exchange(Wallet $fromWallet, Wallet $toWallet, Wallet $spreadWallet, float $sourceAmount, float $targetAmount, float $spreadAmount, array $metadata, ?Transaction $transaction = null, ?string $description = null): array
    {
        $requiredKeys = ['conversion_id', 'exchange_rate_applied', 'base_currency', 'target_currency', 'source_amount', 'target_amount', 'spread_amount'];
        foreach ($requiredKeys as $key) {
            if (!array_key_exists($key, $metadata)) {
                throw new NotifyErrorException("Missing required FX metadata: {$key}");
            }
        }

        if ($sourceAmount <= 0 || $targetAmount <= 0) {
            throw new NotifyErrorException('Exchange amounts must be greater than zero.');
        }

        return DB::transaction(function () use ($fromWallet, $toWallet, $spreadWallet, $sourceAmount, $targetAmount, $spreadAmount, $metadata, $transaction, $description) {
            $locked = $this->lockWalletsInOrder([$fromWallet, $toWallet, $spreadWallet]);

            $entries = [];
            
            $entries[] = $this->debit($locked[$fromWallet->id], $sourceAmount, $transaction, $description, $metadata);
            $entries[] = $this->credit($locked[$toWallet->id], $targetAmount, $transaction, $description, $metadata);
            
            if ($spreadAmount > 0) {
                $entries[] = $this->credit($locked[$spreadWallet->id], $spreadAmount, $transaction, $description, $metadata);
            }

            return $entries;
        });
    }

    /**
     * Records an internal non-balance-affecting or specialized ledger entry (e.g. Reservation)
     */
    public function recordInternal(Wallet $fromWallet, Wallet $toWallet, float $amount, string $currency, string $type, string $description = null, array $metadata = []): LedgerEntry
    {
        $direction = str_contains(strtoupper($type), 'RELEASE') ? 'credit' : 'debit';

        return LedgerEntry::create([
            'transaction_id' => null,
            'wallet_id' => $fromWallet->id,
            'direction' => $direction,
            'amount' => $amount,
            'currency' => $currency,
            'balance_after' => $fromWallet->balance, // Total balance remains the same
            'description' => $description,
            'metadata' => array_merge($metadata, ['type' => $type]),
            'created_at' => now(),
        ]);
    }
}
