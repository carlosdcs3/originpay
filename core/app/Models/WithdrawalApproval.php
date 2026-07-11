<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WithdrawalApproval extends Model
{
    protected $fillable = [
        'withdrawal_request_id',
        'admin_id',
        'approval_level',
        'role_at_approval'
    ];

    public function withdrawalRequest()
    {
        return $this->belongsTo(WithdrawalRequest::class);
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
