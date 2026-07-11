<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DisputeMessage extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'attachments' => 'array',
    ];

    public function dispute()
    {
        return $this->belongsTo(Dispute::class);
    }
}
