<?php

namespace App\Models;

use App\Enums\DisputeStatus;
use App\Enums\DisputeType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Dispute extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'type' => DisputeType::class,
        'status' => DisputeStatus::class,
        'due_at' => 'datetime',
        'resolved_at' => 'datetime',
        'assigned_at' => 'datetime',
        'health_score' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public function getFormattedAmountAttribute()
    {
        return number_format($this->amount_cents / 100, 2, ',', '.');
    }

    public function getFormattedRetainedAmountAttribute()
    {
        return number_format($this->retained_amount_cents / 100, 2, ',', '.');
    }

    public function merchant()
    {
        return $this->belongsTo(User::class, 'merchant_id');
    }

    public function owner()
    {
        return $this->belongsTo(Admin::class, 'owner_id');
    }

    public function assignedBy()
    {
        return $this->belongsTo(Admin::class, 'assigned_by');
    }

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class, 'transaction_id');
    }

    public function messages()
    {
        return $this->hasMany(DisputeMessage::class);
    }

    public function evidenceItems()
    {
        return $this->hasMany(DisputeEvidenceItem::class);
    }

    public function events()
    {
        return $this->hasMany(DisputeEvent::class)->orderBy('created_at', 'desc');
    }
}
