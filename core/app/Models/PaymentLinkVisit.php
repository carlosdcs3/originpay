<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentLinkVisit extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_link_id',
        'session_id',
        'visitor_hash',
        'ip_address',
        'user_agent',
        'referer',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_content',
        'utm_term',
        'device',
        'browser',
        'platform',
        'country',
        'state',
        'city',
        'is_bot',
        'converted_at',
    ];

    protected $casts = [
        'is_bot' => 'boolean',
        'converted_at' => 'datetime',
    ];

    /**
     * Get the payment link that owns the visit.
     */
    public function paymentLink(): BelongsTo
    {
        return $this->belongsTo(PaymentLink::class);
    }
}
