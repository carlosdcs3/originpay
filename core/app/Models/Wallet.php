<?php

namespace App\Models;

use App\Constants\CurrencyRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\HigherOrderCollectionProxy;

class Wallet extends Model
{
    use HasFactory, \App\Traits\HasTenant;

    /**
     * @var HigherOrderCollectionProxy|mixed
     */
    protected $table = 'wallets';

    protected $with = ['currency'];

    protected $appends = ['name', 'is_sender', 'is_receiver', 'is_payment', 'is_withdraw'];

    protected $fillable = [
        'currency_id',
        'user_id',
        'uuid',
        'balance',
        'available_balance',
        'pending_balance',
        'withdrawn_balance',
        'status',
    ];

    protected $casts = [
        'currency_id' => 'integer',
        'user_id'     => 'integer',
        'balance'     => 'float',
        'uuid'        => 'string',
        'status'      => 'boolean',
    ];

    public function fill(array $attributes)
    {
        if (! static::isUnguarded() && $this->exists) {
            unset(
                $attributes['balance'],
                $attributes['available_balance'],
                $attributes['pending_balance'],
                $attributes['reserved_balance'],
                $attributes['rolling_reserve_balance'],
                $attributes['withdrawn_balance']
            );
        }

        return parent::fill($attributes);
    }

    public function scopeActive($query, $role = null)
    {

        if ($role) {
            return $query->whereHas('currency', function ($query) use ($role) {
                $query->whereHas('roles', function ($query) use ($role) {
                    $query->where('role_name', $role)->where('is_active', true);
                });
            });
        }

        return $query->where('status', true);
    }

    public function scope($query)
    {
        return $query->where('status', false);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'wallet_reference', 'uuid');
    }

    public function getLatestTransactionAttribute()
    {
        return $this->transactions()->latest('created_at')->first();
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class)->withDefault();
    }

    public function getNameAttribute(): string
    {
        return "{$this->currency->code}";
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getCurrencyRoleInfo($role)
    {
        return $this->currency->getRoleInfo($role);
    }

    public function hasCurrencyRole(string $role): bool
    {
        return $this->currency->hasRole($role);
    }

    public function getIsSenderAttribute(): bool
    {
        return $this->hasCurrencyRole(CurrencyRole::SENDER);
    }

    public function getIsRequestMoneyAttribute(): bool
    {
        return $this->hasCurrencyRole(CurrencyRole::REQUEST_MONEY);
    }

    public function getIsPaymentAttribute(): bool
    {
        return $this->hasCurrencyRole(CurrencyRole::PAYMENT);
    }

    public function getIsWithdrawAttribute(): bool
    {
        return $this->hasCurrencyRole(CurrencyRole::WITHDRAW);
    }

    public function supportedPaymentMethods($currency)
    {
        return DepositMethod::active()->where('currency', $currency)->get();
    }

    public function virtualCardRequests()
    {
        return $this->hasMany(VirtualCardRequest::class);
    }

    public function virtualCards()
    {
        return $this->hasMany(VirtualCard::class);
    }

    public function balances()
    {
        return $this->hasMany(WalletBalance::class);
    }

    /**
     * Incrementa o saldo da carteira e aloca o saldo especificamente para o Gateway.
     */
    public function creditGateway(int $gatewayId, float $amount): void
    {
        if ($amount <= 0) return;

        \Illuminate\Support\Facades\DB::transaction(function () use ($gatewayId, $amount) {
            // Lock Wallet
            $wallet = static::where('id', $this->id)->lockForUpdate()->first();
            
            // Lock or create WalletBalance
            $walletBalance = WalletBalance::firstOrCreate(
                ['wallet_id' => $wallet->id, 'gateway_id' => $gatewayId],
                ['available' => 0, 'pending' => 0, 'blocked' => 0]
            );
            
            // Re-fetch with lock to ensure strict concurrency
            $walletBalance = WalletBalance::where('id', $walletBalance->id)->lockForUpdate()->first();
            
            // Update balances
            $walletBalance->available += $amount;
            $walletBalance->save();

            $wallet->balance += $amount;
            $wallet->save();
            
            // Update current instance state
            $this->balance = $wallet->balance;
        });
    }

    /**
     * Decrementa o saldo da carteira, validando se há saldo suficiente no Gateway específico.
     * Retorna true em sucesso, e lança exceção em falha de saldo.
     */
    public function debitGateway(int $gatewayId, float $amount): bool
    {
        if ($amount <= 0) return true;

        return \Illuminate\Support\Facades\DB::transaction(function () use ($gatewayId, $amount) {
            // Lock Wallet
            $wallet = static::where('id', $this->id)->lockForUpdate()->first();
            
            // Lock WalletBalance
            $walletBalance = WalletBalance::where('wallet_id', $wallet->id)
                                          ->where('gateway_id', $gatewayId)
                                          ->lockForUpdate()
                                          ->first();
                                          
            if (!$walletBalance || $walletBalance->available < $amount) {
                throw new \Exception(__('Saldo insuficiente no provedor selecionado para realizar esta operação.'));
            }
            
            // Update balances
            $walletBalance->available -= $amount;
            $walletBalance->save();

            $wallet->balance -= $amount;
            $wallet->save();
            
            // Update current instance state
            $this->balance = $wallet->balance;
            
            return true;
        });
    }
}
