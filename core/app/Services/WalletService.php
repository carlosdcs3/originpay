<?php

namespace App\Services;

use App\Constants\FixPctType;
use App\Exceptions\NotifyErrorException;
use App\Models\Currency;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Wallet as WalletModel;
use App\Services\Security\TenantBypass;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class WalletService
{
    /**
     * @throws NotifyErrorException
     */
    public function getDefaultWallet(User $user)
    {
        $defaultCurrency = Currency::getDefault();

        if (! $defaultCurrency) {
            throw new NotifyErrorException('Default currency not found.');
        }

        return $user->wallets()->where('currency_id', $defaultCurrency->id)->first();
    }

    /**
     * Create a default wallet for a user if they don't have one with the default currency.
     */
    public function createDefaultWalletForUser(User $user): ?Wallet
    {
        // Fetch all auto wallet currencies
        $currencies = Currency::autoWallets();

        foreach ($currencies as $currency) {
            // Check if the user already has a wallet with this currency
            if (! $this->userHasWalletWithCurrency($user, $currency->id)) {
                $this->createWallet($user, $currency);
            }
        }

        return null; // Return null if no new wallet was created
    }

    /**
     * Create a wallet for a specified currency if the user doesn't already have one.
     */
    public function createWalletForCurrency(User $user, int $currencyId): ?Wallet
    {
        return ! $this->userHasWalletWithCurrency($user, $currencyId) ? $this->createWallet($user, Currency::findOrFail($currencyId)) : null;
    }

    /**
     * @throws Exception
     */
    public function subtractMoneyByWalletUuid($walletUuid, $amount): WalletModel
    {
        try {
            $wallet = $this->getWalletByUniqueId($walletUuid);
        } catch (ModelNotFoundException $e) {
            throw new NotifyErrorException("Wallet with UUID {$walletUuid} not found.");
        }

        return $this->subtractMoney($wallet, $amount);
    }

    /**
     * Retrieve a wallet by its UniqueWalletId.
     *
     * @throws Exception
     */
    public function getWalletByUniqueId(string $uuid): Wallet
    {
        $wallet = TenantBypass::run(fn () => Wallet::where('uuid', $uuid)->first());

        if (! $wallet) {
            throw new NotifyErrorException(__("Wallet with ID $uuid not found."));
        }

        return $wallet;
    }

    /**
     * Subtract money from a wallet.
     *
     * @throws Exception
     */
    public function subtractMoney(Wallet $wallet, float $amount): Wallet
    {
        if ($amount <= 0) {
            throw new NotifyErrorException('Amount must be greater than zero.');
        }

        return \Illuminate\Support\Facades\DB::transaction(function () use ($wallet, $amount) {
            // Find system general wallet for the counterparty
            $systemWallet = TenantBypass::run(
                fn () => Wallet::where('uuid', 'SYSTEM-GENERAL')->firstOrFail()
            );
            
            // Transfer from user wallet to system wallet
            app(\App\Services\LedgerService::class)->transfer(
                $wallet, 
                $systemWallet, 
                $amount, 
                null, 
                'Legacy subtractMoney call', 
                ['source' => 'WalletService::subtractMoney', 'legacy_call' => true]
            );

            return $wallet->refresh();
        });
    }

    /**
     * @throws Exception
     */
    public function addMoneyByWalletUuid($walletUuid, $amount): WalletModel
    {
        try {
            $wallet = $this->getWalletByUniqueId($walletUuid);
        } catch (ModelNotFoundException $e) {
            throw new NotifyErrorException("Wallet with UUID {$walletUuid} not found.");
        }

        return $this->addMoney($wallet, $amount);
    }

    /**
     * Add money to a wallet.
     *
     * @throws Exception
     */
    public function addMoney(Wallet $wallet, float $amount): Wallet
    {
        if ($amount <= 0) {
            throw new NotifyErrorException('Amount must be greater than zero.');
        }

        return \Illuminate\Support\Facades\DB::transaction(function () use ($wallet, $amount) {
            // Find system general wallet for the counterparty
            $systemWallet = TenantBypass::run(
                fn () => Wallet::where('uuid', 'SYSTEM-GENERAL')->firstOrFail()
            );
            
            // Transfer from system wallet to user wallet
            app(\App\Services\LedgerService::class)->transfer(
                $systemWallet, 
                $wallet, 
                $amount, 
                null, 
                'Legacy addMoney call', 
                ['source' => 'WalletService::addMoney', 'legacy_call' => true]
            );

            return $wallet->refresh();
        });
    }

    public function getWalletByUserId(int $userId, string $currencyCode): ?Wallet
    {
        return Wallet::where('user_id', $userId)
            ->whereHas('currency', function ($query) use ($currencyCode) {
                $query->where('code', $currencyCode);
            })
            ->first();
    }

    public function getDefaultWalletByUserId(int $userId): ?Wallet
    {
        $currency = Currency::getDefault();

        return self::getWalletByUserId($userId, $currency->code);
    }

    public function isWalletBalanceSufficient($walletUuid, $amount): bool
    {
        $myWallet = $this->getWalletByUniqueId($walletUuid);

        $walletBalance = $this->getWalletBalance($myWallet);

        return $walletBalance >= $amount;
    }

    /**
     * Get a wallet's balance.
     */
    public function getWalletBalance(Wallet $wallet): float
    {
        return $wallet->balance;
    }

    /**
     * Retrieves a payer's wallet, given their identifier and currency ID.
     *
     * @throws Exception
     */
    public function getWalletByUserEmailOrWalletUid($emailOrWalletUid, $currencyId): ?WalletModel
    {
        // First check by username
        $recipientUser = User::where('username', $emailOrWalletUid)->first();

        // If not found by username, try email
        if (!$recipientUser && filter_var($emailOrWalletUid, FILTER_VALIDATE_EMAIL)) {
            $recipientUser = User::where('email', $emailOrWalletUid)->first();
        }

        if ($recipientUser) {
            return WalletModel::where('user_id', $recipientUser->id)->where('currency_id', $currencyId)->first();
        }

        if (ctype_digit($emailOrWalletUid)) {
            return self::getWalletByUniqueId((int) $emailOrWalletUid);
        }

        return null;
    }

    /**
     * Determines if the payer and requester wallets are the same.
     */
    public function isSelfTransaction($formWallet, $toWallet): bool
    {
        return $formWallet->user_id === auth()->id() || $formWallet->id === $toWallet->id;
    }

    /**
     * Calculates the fee for requesting money, based on the requester's wallet and amount.
     */
    public function calculateFeeByRole($wallet, $amount, $role)
    {

        $currencyRole = $wallet->currency->roles()->where('role_name', $role)->first();

        return $currencyRole->fee_type === FixPctType::FIXED ? $currencyRole->fee : ($amount * $currencyRole->fee / 100);
    }

    public function conversionAmount($wallet, $amount)
    {
        $rate = $wallet->currency->exchange_rate;

        return $amount * $rate;
    }

    /**
     * @throws Exception
     */
    public function validateAmountLimitByRole($requesterWallet, $payableAmount, $role): void
    {
        $currencyRole = $requesterWallet->currency->roles()->where('role_name', $role)->first();

        if ($payableAmount < $currencyRole->min_limit || $payableAmount > $currencyRole->max_limit) {
            $message = __('Amount must be between :min and :max', ['min' => $currencyRole->min_limit, 'max' => $currencyRole->max_limit]);
            throw new NotifyErrorException($message);
        }

    }

    /**
     * Check if a user already has a wallet in a specific currency.
     */
    protected function userHasWalletWithCurrency(User $user, int $currencyId): bool
    {
        return $user->wallets()->where('currency_id', $currencyId)->exists();
    }

    /**
     * Create a wallet for a user with a given currency.
     */
    protected function createWallet(User $user, Currency $currency): Wallet
    {
        return Wallet::create([
            'currency_id' => $currency->id,
            'user_id'     => $user->id,
            'uuid'        => $this->generateUniqueWalletId(),
            'balance'     => 0.0,
            'status'      => true,
        ]);
    }

    /**
     * Generate a unique wallet ID.
     */
    protected function generateUniqueWalletId(): string
    {
        do {
            $walletUuid = mt_rand(100000000, 999999999);
        } while (Wallet::where('uuid', $walletUuid)->exists());

        return (string) $walletUuid;
    }

    /**
     * Gateway Operations: Credit to pending balance
     */
    public function creditPending(Wallet $wallet, float $amount, string $description, $reference = null, ?string $correlationId = null, ?string $idempotencyKey = null): \App\Models\WalletTransaction
    {
        if ($amount <= 0) {
            throw new Exception("O valor de crédito deve ser maior que zero.");
        }

        return \Illuminate\Support\Facades\DB::transaction(function () use ($wallet, $amount, $description, $reference, $correlationId, $idempotencyKey) {
            $wallet = TenantBypass::run(
                fn () => Wallet::where('id', $wallet->id)->lockForUpdate()->firstOrFail()
            );

            $balanceBefore = $wallet->available_balance;
            $wallet->pending_balance += $amount;
            $wallet->save();

            return \App\Models\WalletTransaction::create([
                'user_id' => $wallet->user_id,
                'wallet_id' => $wallet->id,
                'type' => 'charge',
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $wallet->available_balance, // Main balance doesn't change yet
                'description' => $description,
                'reference_type' => $reference ? get_class($reference) : null,
                'reference_id' => $reference ? $reference->id : null,
                'correlation_id' => $correlationId,
                'idempotency_key' => $idempotencyKey,
            ]);
        });
    }

    /**
     * Gateway Operations: Settle pending balance to available balance
     */
    public function settlePendingToAvailable(Wallet $wallet, float $amount, string $description, $reference = null, ?string $correlationId = null, ?string $idempotencyKey = null): \App\Models\WalletTransaction
    {
        if ($amount <= 0) {
            throw new Exception("O valor de liquidação deve ser maior que zero.");
        }

        return \Illuminate\Support\Facades\DB::transaction(function () use ($wallet, $amount, $description, $reference, $correlationId, $idempotencyKey) {
            $wallet = TenantBypass::run(
                fn () => Wallet::where('id', $wallet->id)->lockForUpdate()->firstOrFail()
            );

            if ($wallet->pending_balance < $amount) {
                throw new Exception("Saldo pendente insuficiente.");
            }

            $balanceBefore = $wallet->available_balance;
            $wallet->pending_balance -= $amount;
            $wallet->available_balance += $amount;
            $wallet->save();

            return \App\Models\WalletTransaction::create([
                'user_id' => $wallet->user_id,
                'wallet_id' => $wallet->id,
                'type' => 'adjustment',
                'amount' => $amount, // Available balance increased
                'balance_before' => $balanceBefore,
                'balance_after' => $wallet->available_balance,
                'description' => $description,
                'reference_type' => $reference ? get_class($reference) : null,
                'reference_id' => $reference ? $reference->id : null,
                'correlation_id' => $correlationId,
                'idempotency_key' => $idempotencyKey,
            ]);
        });
    }

    /**
     * Gateway Operations: Debit available balance (for withdrawals, refunds)
     */
    public function debitAvailable(Wallet $wallet, float $amount, string $type, string $description, $reference = null, bool $forceNegative = false, ?string $correlationId = null, ?string $idempotencyKey = null): \App\Models\WalletTransaction
    {
        if ($amount <= 0) {
            throw new Exception("O valor de débito deve ser maior que zero.");
        }

        return \Illuminate\Support\Facades\DB::transaction(function () use ($wallet, $amount, $type, $description, $reference, $forceNegative, $correlationId, $idempotencyKey) {
            $wallet = TenantBypass::run(
                fn () => Wallet::where('id', $wallet->id)->lockForUpdate()->firstOrFail()
            );

            if (!$forceNegative && $wallet->available_balance < $amount) {
                throw new Exception("Saldo disponível insuficiente.");
            }

            $balanceBefore = $wallet->available_balance;
            $wallet->available_balance -= $amount;
            
            if ($type === 'withdrawal') {
                $wallet->withdrawn_balance += $amount;
            }

            $wallet->save();

            return \App\Models\WalletTransaction::create([
                'user_id' => $wallet->user_id,
                'wallet_id' => $wallet->id,
                'type' => $type,
                'amount' => -$amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $wallet->available_balance,
                'description' => $description,
                'reference_type' => $reference ? get_class($reference) : null,
                'reference_id' => $reference ? $reference->id : null,
                'correlation_id' => $correlationId,
                'idempotency_key' => $idempotencyKey,
            ]);
        });
    }
}
