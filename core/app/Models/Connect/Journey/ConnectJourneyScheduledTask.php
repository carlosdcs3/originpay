<?php
namespace App\Models\Connect\Journey;
use Illuminate\Database\Eloquent\Model;

class ConnectJourneyScheduledTask extends Model
{
    protected $table = 'connect_journey_scheduled_tasks';
    protected $guarded = ['id'];
    protected $casts = [
        'resume_at' => 'datetime',
        'payload' => 'array',
    ];
}
