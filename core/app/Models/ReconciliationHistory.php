<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReconciliationHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'gateway_code',
        'processed_count',
        'divergences_count',
        'duration_ms',
        'status',
        'error_message',
        'divergences_details',
    ];

    protected $casts = [
        'divergences_details' => 'array',
    ];
}
