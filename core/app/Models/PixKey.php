<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PixKey extends Model
{
    use \App\Traits\HasTenant;

    protected $fillable = ['user_id', 'key_type', 'pix_key', 'verified', 'verified_at', 'last_used_at', 'is_primary'];
    protected $casts = [
        'verified' => 'boolean',
        'is_primary' => 'boolean',
        'verified_at' => 'datetime',
        'last_used_at' => 'datetime'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
