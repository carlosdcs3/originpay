<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MerchantGateway extends Model
{
    use HasFactory;

    protected $fillable = [
        'merchant_id',
        'gateway_name',
        'environment',
        'priority',
        'enabled',
        'configuration'
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'priority' => 'integer',
        'configuration' => 'array',
    ];

    public function merchant()
    {
        return $this->belongsTo(Merchant::class);
    }
}
