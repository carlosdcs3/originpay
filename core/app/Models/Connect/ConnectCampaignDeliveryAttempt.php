<?php
namespace App\Models\Connect;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConnectCampaignDeliveryAttempt extends Model
{
    use HasFactory;

    protected $table = 'connect_campaign_delivery_attempts';
    protected $guarded = ['id'];
    
    protected $casts = [
        'response_payload' => 'array',
    ];

    public function recipient()
    {
        return $this->belongsTo(ConnectCampaignRecipient::class, 'recipient_id');
    }

    public function execution()
    {
        return $this->belongsTo(ConnectCampaignExecution::class, 'execution_id');
    }
}
