<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeviceFingerprint extends Model
{
    protected $guarded = ['id'];
    
    protected $casts = [
        'reduced_metadata' => 'array',
        'trusted' => 'boolean',
        'first_seen_at' => 'datetime',
        'last_seen_at' => 'datetime'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
