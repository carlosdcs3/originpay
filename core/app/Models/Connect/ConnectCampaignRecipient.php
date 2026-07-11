<?php
namespace App\Models\Connect;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConnectCampaignRecipient extends Model
{
    use HasFactory;

    protected $table = 'connect_campaign_recipients';
    protected $guarded = ['id'];
    
    public const STATUS_PENDING = 'pending';
    public const STATUS_QUEUED = 'queued';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_PROCESSED = 'processed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_SKIPPED = 'skipped';

    protected $casts = [
        'last_attempt_at' => 'datetime',
        'scheduled_at' => 'datetime',
        'processed_at' => 'datetime',
    ];

    public function execution()
    {
        return $this->belongsTo(ConnectCampaignExecution::class, 'execution_id');
    }

    public function campaign()
    {
        return $this->belongsTo(Campaign::class, 'campaign_id');
    }
    
    public function contact()
    {
        return $this->belongsTo(ConnectContact::class, 'contact_id');
    }
}
