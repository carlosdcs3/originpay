<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UsageMetric extends Model
{
    use HasFactory;

    protected $guarded = ['id'];
    
    protected $casts = [
        'cycle_start' => 'datetime',
        'cycle_end' => 'datetime',
    ];

    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
