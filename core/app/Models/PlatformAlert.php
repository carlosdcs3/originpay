<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlatformAlert extends Model
{
    use HasFactory;

    protected $fillable = [
        'category',
        'severity',
        'source',
        'title',
        'description',
        'recommended_action',
        'related_link',
        'status',
        'resolved_at',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    const SEVERITY_INFO = 'Info';
    const SEVERITY_WARNING = 'Warning';
    const SEVERITY_CRITICAL = 'Critical';

    const CATEGORY_GATEWAY = 'Gateway';
    const CATEGORY_API = 'API';
    const CATEGORY_COMPLIANCE = 'Compliance';
    const CATEGORY_FINANCEIRO = 'Financeiro';
    const CATEGORY_SISTEMA = 'Sistema';
    const CATEGORY_SEGURANCA = 'Segurança';
    
    const STATUS_ACTIVE = 'active';
    const STATUS_RESOLVED = 'resolved';
    const STATUS_IGNORED = 'ignored';
}
