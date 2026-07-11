<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $guarded = ['id'];

    public function versions()
    {
        return $this->hasMany(ProductVersion::class);
    }

    public function currentVersion()
    {
        return $this->hasOne(ProductVersion::class)->where('is_active', true)->orderBy('version_number', 'desc');
    }
}
