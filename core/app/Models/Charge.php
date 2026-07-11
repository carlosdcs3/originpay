<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Charge extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'api_charges';

    protected $fillable = [
        'uuid',
        'correlation_id',
        'idempotency_key',
        'charge_number',
        'charge_id',
        'merchant_id',
        'user_id',
        'wallet_id',
        'currency_id',
        'session_id',
        'payment_method_id',
        'gateway_id',
        'gateway_charge_id',
        'gateway_reference',
        'payment_method',
        'amount',
        'currency',
        'platform_fee',
        'gateway_fee',
        'fee_rule_id',
        'fee_snapshot',
        'net_amount',
        'description',
        'customer_name',
        'customer_email',
        'customer_document',
        'expires_at',
        'paid_at',
        'payment_link',
        'boleto_url',
        'boleto_pdf_url',
        'barcode',
        'digitable_line',
        'qr_code',
        'pix_copy_paste',
        'status',
        'failure_code',
        'failure_message',
        'merchant_metadata',
        'internal_metadata',
        'metadata',
        'environment'
    ];

    protected $casts = [
        'status' => \App\Enums\ChargeStatus::class,
        'payment_method' => \App\Enums\PaymentMethod::class,
        'merchant_metadata' => 'array',
        'internal_metadata' => 'array',
        'metadata' => 'array',
        'fee_snapshot' => 'array',
        'amount' => 'float',
        'platform_fee' => 'float',
        'gateway_fee' => 'float',
        'net_amount' => 'float',
        'expires_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    public function merchant()
    {
        return $this->belongsTo(Merchant::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function gateway()
    {
        return $this->belongsTo(PaymentGateway::class, 'gateway_id');
    }

    public function events()
    {
        return $this->hasMany(ChargeEvent::class);
    }
}
