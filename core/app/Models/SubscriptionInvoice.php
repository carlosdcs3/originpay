<?php

namespace App\Models;

use App\Enums\SubscriptionInvoiceStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubscriptionInvoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'customer_subscription_id',
        'user_id',
        'charge_id',
        'status',
        'period_start',
        'period_end',
        'amount_due',
        'amount_paid',
        'currency',
        'due_at',
        'paid_at',
        'failed_at',
        'metadata',
        'last_error',
        'idempotency_key',
    ];

    protected $casts = [
        'status' => SubscriptionInvoiceStatus::class,
        'period_start' => 'datetime',
        'period_end' => 'datetime',
        'amount_due' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'due_at' => 'datetime',
        'paid_at' => 'datetime',
        'failed_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function subscription()
    {
        return $this->belongsTo(CustomerSubscription::class, 'customer_subscription_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function charge()
    {
        return $this->belongsTo(Charge::class);
    }

    public function items()
    {
        return $this->hasMany(SubscriptionInvoiceItem::class);
    }
}
