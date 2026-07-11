<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DisputeEvent extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'metadata' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            if (empty($model->reference)) {
                // Get the max ID and increment
                $lastId = self::max('id') ?? 0;
                $model->reference = 'EVT-' . str_pad($lastId + 1, 6, '0', STR_PAD_LEFT);
            }
        });

        static::updating(function ($model) {
            throw new \Exception("DisputeEvents are append-only and cannot be updated.");
        });

        static::deleting(function ($model) {
            throw new \Exception("DisputeEvents are append-only and cannot be deleted.");
        });
    }

    public function dispute()
    {
        return $this->belongsTo(Dispute::class);
    }
}
