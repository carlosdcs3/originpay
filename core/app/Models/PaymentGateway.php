<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class PaymentGateway extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider',
        'adapter',
        'logo',
        'name',
        'code',
        'currencies',
        'credentials',
        'is_withdraw',
        'status',
        'is_maintenance',
        'priority',
        'is_sandbox',
        'supports_pix',
        'supports_card',
        'supports_boleto',
        'supports_crypto',
        'supports_refund',
        'supports_withdrawal',
    ];

    protected $casts = [
        'credentials'         => 'array',
        'accepted_currencies' => 'array',
        'webhook_headers'     => 'array',
        'status'              => 'integer',
        'is_maintenance'      => 'boolean',
        'priority'            => 'integer',
        'is_sandbox'          => 'boolean',
        'supports_pix'        => 'boolean',
        'supports_card'       => 'boolean',
        'supports_boleto'     => 'boolean',
        'supports_crypto'     => 'boolean',
        'supports_refund'     => 'boolean',
        'supports_withdrawal' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', true);
    }

    /**
     * Scope to only include gateways that support withdrawal.
     */
    public function scopeWithdrawAvailable(Builder $query): Builder
    {
        return $query->whereNotNull('is_withdraw')->where('is_withdraw', '!=', '');
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    /**
     * Get and set the currencies attribute safely, handling array, string, JSON, and null.
     */
    protected function currencies(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                if (empty($value)) {
                    return [];
                }
                
                if (is_array($value)) {
                    return $value;
                }
                
                // If it is valid JSON
                $decoded = json_decode($value, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    return is_array($decoded) ? $decoded : [$decoded];
                }
                
                // If it is a comma separated string
                if (is_string($value)) {
                    $arr = array_map('trim', explode(',', $value));
                    return array_values(array_filter($arr));
                }
                
                return [];
            },
            set: function ($value) {
                if (is_array($value)) {
                    return json_encode(array_values(array_filter($value)));
                }
                
                if (is_string($value)) {
                    // Try to see if it is already json
                    $decoded = json_decode($value, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        return $value; 
                    }
                    // Otherwise split by comma and encode
                    $arr = array_map('trim', explode(',', $value));
                    return json_encode(array_values(array_filter($arr)));
                }
                
                return json_encode([]);
            }
        );
    }

    /**
     * Check if withdraw field is available.
     */
    public function getWithdrawAvailableAttribute(): bool
    {
        return !empty($this->is_withdraw);
    }

    /*
    |--------------------------------------------------------------------------
    | Static Fetch Methods (with optimized Caching)
    |--------------------------------------------------------------------------
    */

    /**
     * Get all payment gateways (optionally paginated).
     */
    public static function allCached()
    {
        return Cache::rememberForever('payment_gateways_all', function () {
            return self::active()->orderBy('id')->get();
        });
    }

    /**
     * Get a payment gateway by its ID.
     */
    public static function getById(int $id): ?self
    {
        return Cache::rememberForever("payment_gateway_id_{$id}", function () use ($id) {
            return self::find($id);
        });
    }

    /**
     * Get a payment gateway by its code.
     */
    public static function getByCode(string $code): ?self
    {
        return Cache::rememberForever("payment_gateway_code_{$code}", function () use ($code) {
            return self::where('code', $code)->first();
        });
    }

    /**
     * Get credentials for a specific gateway code.
     */
    public static function getCredentials(string $code): array
    {
        return self::getByCode($code)?->credentials ?? [];
    }

    /**
     * Get currencies supported by a specific gateway code.
     */
    public static function getCurrencies(string $code): array
    {
        return self::getByCode($code)?->currencies ?? [];
    }

    /*
   |--------------------------------------------------------------------------
   | Relationships
   |--------------------------------------------------------------------------
   */

    public function depositMethods(): \Illuminate\Database\Eloquent\Relations\HasMany|PaymentGateway
    {
        return $this->hasMany(DepositMethod::class, 'payment_gateway_id', 'id');
    }

    public function withdrawMethods(): \Illuminate\Database\Eloquent\Relations\HasMany|PaymentGateway
    {
        return $this->hasMany(WithdrawMethod::class, 'payment_gateway_id', 'id');
    }

    /*
    |--------------------------------------------------------------------------
    | Cache Management
    |--------------------------------------------------------------------------
    */

    /**
     * Flush related cache keys.
     */
    public static function flushCache(self $gateway): void
    {
        Cache::forget('payment_gateways_all');
        Cache::forget("payment_gateway_id_{$gateway->id}");
        Cache::forget("payment_gateway_code_{$gateway->code}");
    }

    /**
     * Auto-clear cache on update or delete.
     */
    protected static function booted()
    {
        static::saved(fn (self $gateway) => self::flushCache($gateway));
        static::deleted(fn (self $gateway) => self::flushCache($gateway));
    }
}
