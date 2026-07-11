<?php
namespace App\Models\Connect\Journey;
use Illuminate\Database\Eloquent\Model;

class ConnectJourneyInstance extends Model
{
    protected $table = 'connect_journey_instances';
    protected $guarded = ['id'];
    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'metadata' => 'array',
    ];
    
    public function version() {
        return $this->belongsTo(ConnectJourneyVersion::class, 'version_id');
    }
    
    public function contact() {
        return $this->belongsTo(\App\Models\Connect\Contact::class, 'contact_id');
    }
}
