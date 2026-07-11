<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KycProfile extends Model
{
    use HasFactory, \App\Traits\HasTenant;

    protected $fillable = ['user_id', 'level', 'status', 'approved_by', 'approved_at', 'rejection_reason'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
