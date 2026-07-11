<?php
namespace App\Models\Connect;
use Illuminate\Database\Eloquent\Model;

class ConnectEventLog extends Model
{
    protected $table = 'connect_event_log';
    protected $guarded = ['id'];
    protected $casts = [
        'payload' => 'array',
        'occurred_at' => 'datetime',
    ];
}
