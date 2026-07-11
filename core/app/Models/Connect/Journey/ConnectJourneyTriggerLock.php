<?php
namespace App\Models\Connect\Journey;
use Illuminate\Database\Eloquent\Model;

class ConnectJourneyTriggerLock extends Model
{
    protected $table = 'connect_journey_trigger_locks';
    protected $guarded = ['id'];
}
