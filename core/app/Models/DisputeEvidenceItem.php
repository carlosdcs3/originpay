<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DisputeEvidenceItem extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'required' => 'boolean',
        'reviewed_at' => 'datetime',
    ];

    public function dispute()
    {
        return $this->belongsTo(Dispute::class);
    }
}
