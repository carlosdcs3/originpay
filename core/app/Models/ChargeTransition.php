<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChargeTransition extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'charge_id',
        'from_status',
        'to_status',
        'reason',
        'created_at'
    ];

    public function charge()
    {
        return $this->belongsTo(Charge::class, 'charge_id');
    }
}
