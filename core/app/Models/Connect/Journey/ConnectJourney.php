<?php
namespace App\Models\Connect\Journey;
use Illuminate\Database\Eloquent\Model;

class ConnectJourney extends Model
{
    protected $table = 'connect_journeys';
    protected $guarded = ['id'];
    protected $casts = [
        'published_at' => 'datetime',
        'metadata' => 'array',
    ];
}
