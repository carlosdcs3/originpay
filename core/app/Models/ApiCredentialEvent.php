<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiCredentialEvent extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'api_credential_id',
        'action',
        'performed_by',
        'ip_address',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function credential()
    {
        return $this->belongsTo(ApiCredential::class, 'api_credential_id');
    }
}
