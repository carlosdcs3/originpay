<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WalletTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'wallet_id',
        'type', // charge, fee, withdrawal, adjustment, refund
        'amount',
        'correlation_id',
        'idempotency_key',
        'balance_before',
        'balance_after',
        'description',
        'reference_type',
        'reference_id',
        'previous_integrity_hash',
        'integrity_hash',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:8',
        'balance_before' => 'decimal:8',
        'balance_after' => 'decimal:8',
        'metadata' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        // 1. Proibir UPDATE
        static::updating(function ($transaction) {
            throw new \Exception("Ledger Violation: Wallet transactions are append-only. Updates are strictly forbidden.");
        });

        // 2. Proibir DELETE
        static::deleting(function ($transaction) {
            throw new \Exception("Ledger Violation: Wallet transactions are append-only. Deletions are strictly forbidden.");
        });

        // 3. Assinatura de Integridade (HMAC) no momento da criação
        static::creating(function ($transaction) {
            // Pega a última transação desta carteira para encadear o hash
            $lastTx = self::where('wallet_id', $transaction->wallet_id)->latest('id')->first();
            
            $transaction->previous_integrity_hash = $lastTx ? $lastTx->integrity_hash : null;
            
            // O ID ainda não existe, então geramos o hash com o que temos (UUID/Idempotency) 
            // e os valores cruciais financeiros.
            $payload = implode('|', [
                $transaction->wallet_id,
                $transaction->amount,
                $transaction->type,
                $transaction->balance_before,
                $transaction->balance_after,
                $transaction->correlation_id,
                $transaction->idempotency_key,
                $transaction->previous_integrity_hash
            ]);

            $secret = config('app.key');
            $transaction->integrity_hash = hash_hmac('sha256', $payload, $secret);
        });
    }


    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }

    public function reference()
    {
        return $this->morphTo();
    }
}
