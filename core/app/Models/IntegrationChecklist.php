<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IntegrationChecklist extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'has_api_key',
        'has_webhook',
        'has_test_charge',
        'has_simulated_payment',
        'has_received_webhook',
        'is_production_active',
    ];

    protected $casts = [
        'has_api_key' => 'boolean',
        'has_webhook' => 'boolean',
        'has_test_charge' => 'boolean',
        'has_simulated_payment' => 'boolean',
        'has_received_webhook' => 'boolean',
        'is_production_active' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
