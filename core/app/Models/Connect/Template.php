<?php
namespace App\Models\Connect;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;

class Template extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'connect_templates';
    protected $guarded = ['id'];
    
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_ARCHIVED = 'archived';

    protected $casts = [
        'content' => 'array',
        'metadata' => 'array',
        'dimensions' => 'array',
        'variables' => 'array',
        'is_current' => 'boolean',
        'published_at' => 'datetime',
    ];

    public function scopeForMerchant($query, $merchantId)
    {
        return $query->where('merchant_id', $merchantId);
    }

    public function parentTemplate()
    {
        return $this->belongsTo(self::class, 'parent_template_id');
    }

    public function versions()
    {
        return $this->hasMany(self::class, 'parent_template_id');
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
