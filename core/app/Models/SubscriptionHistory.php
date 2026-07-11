<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubscriptionHistory extends Model
{
    use HasFactory;

    protected $table = 'subscription_history';
    protected $guarded = ['id'];

    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function oldPlanVersion()
    {
        return $this->belongsTo(PlanVersion::class, 'old_plan_version_id');
    }

    public function newPlanVersion()
    {
        return $this->belongsTo(PlanVersion::class, 'new_plan_version_id');
    }
}
