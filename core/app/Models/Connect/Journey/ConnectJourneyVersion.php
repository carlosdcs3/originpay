<?php
namespace App\Models\Connect\Journey;
use Illuminate\Database\Eloquent\Model;

class ConnectJourneyVersion extends Model
{
    protected $table = 'connect_journey_versions';
    protected $guarded = ['id'];
    protected $casts = [
        'graph' => 'array',
        'published_at' => 'datetime',
    ];
}
