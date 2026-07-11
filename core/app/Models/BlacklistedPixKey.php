<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BlacklistedPixKey extends Model
{
    protected $fillable = [
        'pix_key',
        'reason',
        'risk_level',
        'admin_id'
    ];

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
