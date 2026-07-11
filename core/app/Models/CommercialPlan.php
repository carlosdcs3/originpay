<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommercialPlan extends Model
{
    protected $table = 'commercial_plans';
    protected $guarded = ['id'];
    protected $casts = [
        'is_active' => 'boolean'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function versions()
    {
        return $this->hasMany(PlanVersion::class, 'plan_id');
    }

    public function currentVersion()
    {
        return $this->hasOne(PlanVersion::class, 'plan_id')->where('is_active', true)->orderBy('version_number', 'desc');
    }
}
