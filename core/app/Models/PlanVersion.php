<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlanVersion extends Model
{
    protected $guarded = ['id'];
    protected $casts = [
        'is_active' => 'boolean',
        'deprecated_at' => 'datetime'
    ];

    public function plan()
    {
        return $this->belongsTo(CommercialPlan::class, 'plan_id');
    }

    public function productVersion()
    {
        return $this->belongsTo(ProductVersion::class);
    }

    public function prices()
    {
        return $this->hasMany(Price::class);
    }

    public function features()
    {
        return $this->hasMany(PlanVersionFeature::class);
    }

}
