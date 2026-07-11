<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubscriptionInvoiceItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'subscription_invoice_id',
        'customer_subscription_item_id',
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

    public function invoice()
    {
        return $this->belongsTo(SubscriptionInvoice::class, 'subscription_invoice_id');
    }

    public function subscriptionItem()
    {
        return $this->belongsTo(CustomerSubscriptionItem::class, 'customer_subscription_item_id');
    }
}
