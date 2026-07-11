<?php
namespace App\Models\Connect;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;

class Campaign extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'connect_campaigns';
    protected $guarded = ['id'];
    
    public const STATUS_DRAFT = 'draft';
    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_PREPARING = 'preparing';
    public const STATUS_RESOLVING = 'resolving_audience';
    public const STATUS_QUEUEING = 'queueing';
    public const STATUS_RUNNING = 'running';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_FAILED = 'failed';

    protected $casts = [
        'metadata' => 'array',
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'last_attempt_at' => 'datetime',
    ];

    public function scopeForMerchant($query, $merchantId)
    {
        return $query->where('merchant_id', $merchantId);
    }

    public function segment()
    {
        return $this->belongsTo(ConnectSegment::class, 'segment_id');
    }

    public function template()
    {
        return $this->belongsTo(Template::class, 'template_id');
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
