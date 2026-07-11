<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FinancialAnomaly extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'severity',
        'entity_type',
        'entity_id',
        'fingerprint',
        'description',
        'metadata',
        'suggested_actions',
        'detected_at',
        'resolved_at',
        'resolved_by',
        'resolution_notes',
    ];

    protected $casts = [
        'metadata' => 'array',
        'suggested_actions' => 'array',
        'detected_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function resolver()
    {
        return $this->belongsTo(Admin::class, 'resolved_by'); // Assuming Admin model handles backend resolution
    }

    public function isResolved(): bool
    {
        return !is_null($this->resolved_at);
    }
}
