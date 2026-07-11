<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    use HasFactory;

    protected $guarded = ['id'];
    
    protected $casts = [
        'trial_ends_at' => 'datetime',
        'current_period_start' => 'datetime',
        'current_period_end' => 'datetime',
        'canceled_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function planVersion()
    {
        return $this->belongsTo(PlanVersion::class, 'plan_version_id');
    }

    public function price()
    {
        return $this->belongsTo(Price::class);
    }

    public function history()
    {
        return $this->hasMany(SubscriptionHistory::class);
    }

    public function usageMetrics()
    {
        return $this->hasMany(UsageMetric::class);
    }
}
