<?php

namespace App\Models;

use App\Enums\CustomerSubscriptionStatus;
use App\Enums\SubscriptionInterval;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerSubscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'user_id',
        'customer_name',
        'customer_email',
        'customer_document',
        'status',
        'amount',
        'currency',
        'payment_method',
        'interval',
        'interval_count',
        'description',
        'start_at',
        'current_period_start',
        'current_period_end',
        'next_billing_at',
        'canceled_at',
        'cancel_at_period_end',
        'metadata',
        'last_error',
        'idempotency_key',
    ];

    protected $casts = [
        'status' => CustomerSubscriptionStatus::class,
        'interval' => SubscriptionInterval::class,
        'amount' => 'decimal:2',
        'interval_count' => 'integer',
        'start_at' => 'datetime',
        'current_period_start' => 'datetime',
        'current_period_end' => 'datetime',
        'next_billing_at' => 'datetime',
        'canceled_at' => 'datetime',
        'cancel_at_period_end' => 'boolean',
        'metadata' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(CustomerSubscriptionItem::class);
    }

    public function invoices()
    {
        return $this->hasMany(SubscriptionInvoice::class);
    }

    public function latestInvoice()
    {
        return $this->hasOne(SubscriptionInvoice::class)->latestOfMany();
    }
}
