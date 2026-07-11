<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ConnectTemplate extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'merchant_id',
        'uuid',
        'channel',
        'name',
        'subject',
        'content',
        'content_format',
        'metadata',
        'dimensions',
        'variables',
        'version',
        'is_current',
        'parent_template_id',
        'published_at',
        'created_by',
        'updated_by',
        'status',
    ];

    protected $casts = [
        'metadata' => 'array',
        'dimensions' => 'array',
        'variables' => 'array',
        'is_current' => 'boolean',
        'published_at' => 'datetime',
    ];

    /**
     * Get the merchant (user) that owns the template.
     */
    public function merchant()
    {
        return $this->belongsTo(User::class, 'merchant_id');
    }
}
