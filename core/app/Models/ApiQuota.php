<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApiQuota extends Model
{
    protected $fillable = [
        'user_id',
        'rate_limit_general',
        'rate_limit_financial',
        'quota_daily',
        'quota_monthly',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
