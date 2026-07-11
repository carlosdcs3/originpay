<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentLink extends Model
{
    use HasFactory;

    public const TYPE_CHARGE = 'charge';
    public const TYPE_SUBSCRIPTION = 'subscription';

    public const STATUS_PENDING = 'pending';
    public const STATUS_AWAITING_PAYMENT = 'awaiting_payment';
    public const STATUS_PAID = 'paid';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_CANCELED = 'canceled';
    public const STATUS_FAILED = 'failed';
    public const STATUS_ACTIVE = self::STATUS_PENDING;

    protected $fillable = [
        'uuid',
        'slug',
        'user_id',
        'type',
        'charge_id',
        'customer_subscription_id',
        'customer_id',
        'amount',
        'currency',
        'payment_method',
        'allowed_payment_methods',
        'title',
        'description',
        'status',
        'expires_at',
        'paid_at',
        'canceled_at',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'allowed_payment_methods' => 'array',
        'expires_at' => 'datetime',
        'paid_at' => 'datetime',
        'canceled_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function charge()
    {
        return $this->belongsTo(Charge::class);
    }

    public function subscription()
    {
        return $this->belongsTo(CustomerSubscription::class, 'customer_subscription_id');
    }

    public function visits()
    {
        return $this->hasMany(PaymentLinkVisit::class);
    }

    public function publicUrl(): string
    {
        return route('payment-links.public.show', $this->slug);
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isPubliclyPayable(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_AWAITING_PAYMENT], true) && ! $this->isExpired();
    }

    public function allowsPaymentMethod(string $method): bool
    {
        return in_array($method, $this->allowed_payment_methods ?: [$this->payment_method], true);
    }
}
