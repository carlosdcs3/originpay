<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountRestriction extends Model
{
    protected $fillable = [
        'user_id',
        'restriction_type',
        'reason',
        'admin_id',
        'expires_at'
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
