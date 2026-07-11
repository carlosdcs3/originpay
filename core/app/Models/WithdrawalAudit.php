<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WithdrawalAudit extends Model
{
    protected $fillable = [
        'withdrawal_id',
        'user_id',
        'admin_id',
        'action',
        'reason',
        'ip_address',
        'metadata'
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function withdrawal()
    {
        return $this->belongsTo(WithdrawalRequest::class, 'withdrawal_id');
    }
}
