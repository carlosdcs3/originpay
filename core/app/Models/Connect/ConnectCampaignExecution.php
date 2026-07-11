<?php
namespace App\Models\Connect;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConnectCampaignExecution extends Model
{
    use HasFactory;

    protected $table = 'connect_campaign_executions';
    protected $guarded = ['id'];
    
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    protected $casts = [
        'metadata' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function campaign()
    {
        return $this->belongsTo(Campaign::class, 'campaign_id');
    }

    public function recipients()
    {
        return $this->hasMany(ConnectCampaignRecipient::class, 'execution_id');
    }
}
