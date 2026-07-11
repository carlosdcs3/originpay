<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlanVersionFeature extends Model
{
    protected $guarded = ['id'];
    protected $casts = [
        'is_enabled' => 'boolean'
    ];

    public function planVersion()
    {
        return $this->belongsTo(PlanVersion::class);
    }

    public function feature()
    {
        return $this->belongsTo(CommercialFeature::class, 'commercial_feature_id');
    }
}
