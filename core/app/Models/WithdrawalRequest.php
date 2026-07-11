<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WithdrawalRequest extends Model
{
    use \App\Traits\HasTenant;

    protected $fillable = [
        'user_id',
        'wallet_id',
        'transaction_id',
        'provider',
        'pix_key_snapshot',
        'pix_key_type',
        'pix_owner_name',
        'pix_owner_document',
        'amount',
        'fee_amount',
        'net_amount',
        'status',
        'requested_at',
        'approved_at',
        'rejected_at',
        'processed_at',
        'processed_by',
        'metadata'
    ];

    protected $casts = [
        'amount' => 'decimal:8',
        'fee_amount' => 'decimal:8',
        'net_amount' => 'decimal:8',
        'requested_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'processed_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }

    public function audits()
    {
        return $this->hasMany(WithdrawalAudit::class, 'withdrawal_id');
    }

    public function approvals()
    {
        return $this->hasMany(WithdrawalApproval::class, 'withdrawal_request_id');
    }
}
