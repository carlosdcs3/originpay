<?php

namespace App\Models;

use App\Enums\PaymentOperation;
use App\Enums\RoutingStrategy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentMethodRoute extends Model
{
    use HasFactory;

    protected $table = 'payment_method_routes';

    protected $fillable = [
        // Legacy (still used by old GatewayResolver fallback path)
        'payment_method',
        // New primary key for routing
        'payment_operation',
        'primary_gateway_id',
        'fallback_gateway_ids',
        'routing_strategy',
        'gateway_weights',
        'enabled',
    ];

    protected $casts = [
        'fallback_gateway_ids' => 'array',
        'gateway_weights'      => 'array',
        'enabled'              => 'boolean',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    public function primaryGateway()
    {
        return $this->belongsTo(PaymentGateway::class, 'primary_gateway_id');
    }

    // ─── Accessors / Backward Compatibility ──────────────────────────────────

    /**
     * Resolves the routing strategy as an Enum safely.
     * Falls back to MANUAL if the stored value is not recognized (future-proof).
     */
    public function getStrategyEnum(): RoutingStrategy
    {
        try {
            return RoutingStrategy::from($this->routing_strategy ?? 'manual');
        } catch (\ValueError) {
            return RoutingStrategy::MANUAL;
        }
    }

    /**
     * Resolves the payment operation as an Enum safely.
     */
    public function getOperationEnum(): ?PaymentOperation
    {
        if (!$this->payment_operation) {
            return null;
        }
        try {
            return PaymentOperation::from($this->payment_operation);
        } catch (\ValueError) {
            return null;
        }
    }

    // ─── Query Helpers ────────────────────────────────────────────────────────

    /**
     * Find a route by operation (new, preferred).
     */
    public static function forOperation(PaymentOperation $operation): ?self
    {
        return self::where('payment_operation', $operation->value)->first();
    }

    /**
     * Find a route by legacy payment_method string (backward compat).
     */
    public static function forLegacyMethod(string $method): ?self
    {
        return self::where('payment_method', $method)->first();
    }
}
