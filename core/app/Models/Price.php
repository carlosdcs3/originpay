<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Price extends Model
{
    protected $guarded = ['id'];
    protected $casts = [
        'is_active' => 'boolean'
    ];

    public function planVersion()
    {
        return $this->belongsTo(PlanVersion::class);
    }
}
