<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Exception;

class LedgerEntry extends Model
{
    use HasFactory;

    protected $table = 'ledger_entries';
    
    public $timestamps = false; // We only use created_at, handled below

    protected $fillable = [
        'transaction_id',
        'wallet_id',
        'direction',
        'amount',
        'currency',
        'balance_after',
        'description',
        'metadata',
        'created_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'metadata' => 'json',
        'created_at' => 'datetime',
    ];

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }

    protected static function booted()
    {
        // Enforce immutability
        static::updating(function ($model) {
            throw new Exception("LedgerEntry is immutable and cannot be updated.");
        });

        static::deleting(function ($model) {
            throw new Exception("LedgerEntry is immutable and cannot be deleted.");
        });
    }
}
