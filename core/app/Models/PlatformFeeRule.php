<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class PlatformFeeRule extends Model
{
    use HasFactory;

    public const SCOPE_GLOBAL = 'global';
    public const SCOPE_MERCHANT = 'merchant';

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';

    protected $fillable = [
        'scope',
        'user_id',
        'payment_method',
        'currency',
        'fixed_fee',
        'percentage_fee',
        'minimum_fee',
        'maximum_fee',
        'settlement_delay_days',
        'reserve_percentage',
        'status',
        'starts_at',
        'ends_at',
        'metadata',
        'created_by_admin_id',
        'updated_by_admin_id',
    ];

    protected $casts = [
        'fixed_fee' => 'decimal:8',
        'percentage_fee' => 'decimal:4',
        'minimum_fee' => 'decimal:8',
        'maximum_fee' => 'decimal:8',
        'settlement_delay_days' => 'integer',
        'reserve_percentage' => 'decimal:4',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeGlobal(Builder $query): Builder
    {
        return $query->where('scope', self::SCOPE_GLOBAL)->whereNull('user_id');
    }

    public function scopeMerchant(Builder $query, int $userId): Builder
    {
        return $query->where('scope', self::SCOPE_MERCHANT)->where('user_id', $userId);
    }

    public function scopeForMethod(Builder $query, string $paymentMethod, string $currency = 'BRL'): Builder
    {
        return $query
            ->where('payment_method', strtolower($paymentMethod))
            ->where('currency', strtoupper($currency));
    }

    public function scopeForMethodList(Builder $query, array $paymentMethods): Builder
    {
        return $query->whereIn('payment_method', array_map('strtolower', $paymentMethods));
    }

    public function scopeCurrentlyEffective(Builder $query, Carbon|string|null $at = null): Builder
    {
        $effectiveAt = $at ? Carbon::parse($at) : now();

        return $query
            ->where(function (Builder $query) use ($effectiveAt) {
                $query->whereNull('starts_at')->orWhere('starts_at', '<=', $effectiveAt);
            })
            ->where(function (Builder $query) use ($effectiveAt) {
                $query->whereNull('ends_at')->orWhere('ends_at', '>', $effectiveAt);
            });
    }

    public function scopeLatestEffective(Builder $query): Builder
    {
        return $query
            ->orderByRaw('starts_at IS NULL')
            ->orderByDesc('starts_at')
            ->orderByDesc('id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function creator()
    {
        return $this->belongsTo(Admin::class, 'created_by_admin_id');
    }

    public function updater()
    {
        return $this->belongsTo(Admin::class, 'updated_by_admin_id');
    }

    public function audits()
    {
        return $this->hasMany(PlatformFeeRuleAudit::class);
    }
}
