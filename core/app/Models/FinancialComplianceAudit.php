<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinancialComplianceAudit extends Model
{
    protected $fillable = [
        'admin_id',
        'user_id',
        'action',
        'before',
        'after',
        'reason',
        'ip_address',
        'user_agent'
    ];

    protected $casts = [
        'before' => 'array',
        'after' => 'array',
    ];

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
