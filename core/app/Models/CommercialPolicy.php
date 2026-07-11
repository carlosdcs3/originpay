<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommercialPolicy extends Model
{
    use HasFactory;

    protected $fillable = [
        'admin_id',
        'payload',
        'changes',
        'reason',
        'ip_address',
        'user_agent',
        'applied_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'changes' => 'array',
        'applied_at' => 'datetime',
    ];

    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }

    /**
     * Scope a query to only include active policies (applied_at <= now).
     * Order by applied_at descending so the first one is the currently active one.
     */
    public function scopeActive($query)
    {
        return $query->where('applied_at', '<=', now())->orderBy('applied_at', 'desc')->orderBy('id', 'desc');
    }
}
