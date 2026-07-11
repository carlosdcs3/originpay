<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlatformIncident extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'severity',
        'status',
        'started_at',
        'resolved_at',
        'duration_ms',
        'root_cause',
        'resolution',
        'created_by',
        'resolved_by',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    const STATUS_OPEN = 'open';
    const STATUS_INVESTIGATING = 'investigating';
    const STATUS_MONITORING = 'monitoring';
    const STATUS_RESOLVED = 'resolved';

    const SEVERITY_MINOR = 'minor';
    const SEVERITY_HIGH = 'high';
    const SEVERITY_CRITICAL = 'critical';
}
