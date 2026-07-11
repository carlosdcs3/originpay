<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ConnectSegment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'merchant_id',
        'uuid',
        'name',
        'description',
        'rules',
        'is_dynamic',
        'total_contacts',
    ];

    protected $casts = [
        'rules' => 'array',
        'is_dynamic' => 'boolean',
        'total_contacts' => 'integer',
    ];

    /**
     * Get the merchant (user) that owns the segment.
     */
    public function merchant()
    {
        return $this->belongsTo(User::class, 'merchant_id');
    }

    /**
     * Helper to get the number of rules.
     */
    public function getRulesCountAttribute()
    {
        if (empty($this->rules) || !is_array($this->rules) || !isset($this->rules['rules'])) {
            return 0;
        }
        return count($this->rules['rules']);
    }
}
