<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeatureFlag extends Model
{
    protected $guarded = ['id'];
    
    protected $casts = [
        'is_active' => 'boolean'
    ];

    public static function isActive(string $key): bool
    {
        $flag = self::where('key', $key)->first();
        return $flag ? $flag->is_active : false;
    }
}
