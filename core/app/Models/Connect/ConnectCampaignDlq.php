<?php
namespace App\Models\Connect;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConnectCampaignDlq extends Model
{
    use HasFactory;

    protected $table = 'connect_campaign_dlq';
    protected $guarded = ['id'];

    public function recipient()
    {
        return $this->belongsTo(ConnectCampaignRecipient::class, 'recipient_id');
    }

    public function merchant()
    {
        return $this->belongsTo(\App\Models\User::class, 'merchant_id');
    }
}
