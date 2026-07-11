<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlatformFeeRuleAudit extends Model
{
    use HasFactory;

    protected $fillable = [
        'platform_fee_rule_id',
        'admin_id',
        'action',
        'old_values',
        'new_values',
        'reason',
        'ip_address',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    public function rule()
    {
        return $this->belongsTo(PlatformFeeRule::class, 'platform_fee_rule_id');
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }
}
