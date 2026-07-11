<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommercialFeature extends Model
{
    protected $guarded = ['id'];
    protected $casts = [
        'is_active' => 'boolean',
        'is_visible' => 'boolean'
    ];
}
