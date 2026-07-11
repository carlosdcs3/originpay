<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerSubscriptionItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_subscription_id',
        'description',
        'quantity',
        'unit_amount',
        'total_amount',
        'metadata',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'metadata' => 'array',
    ];

    public function subscription()
    {
        return $this->belongsTo(CustomerSubscription::class, 'customer_subscription_id');
    }

    public function invoiceItems()
    {
        return $this->hasMany(SubscriptionInvoiceItem::class);
    }
}
