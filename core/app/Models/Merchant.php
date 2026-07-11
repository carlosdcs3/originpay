<?php

namespace App\Models;

use App\Enums\EnvironmentMode;
use App\Enums\MerchantStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\Request;

class Merchant extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'business_name',
        'site_url',
        'currency_id',
        'business_logo',
        'business_description',
        'business_email',
        'fee',
        'status',
        'current_mode',
        'sandbox_enabled',
        'webhook_url',
    ];

    protected $hidden = [
        'merchant_key',
        'api_key',
        'api_secret',
        'test_api_key',
        'test_api_secret',
        'test_merchant_key',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'fee'              => 'double',
        'status'           => MerchantStatus::class,
        'sandbox_enabled'  => 'boolean',
        'current_mode'     => EnvironmentMode::class,
    ];

    public function scopeActive()
    {
        return $this->where('status', MerchantStatus::APPROVED);
    }

    /**
     * Check if merchant is in sandbox/test mode
     */
    public function isSandboxMode(): bool
    {
        return $this->current_mode === EnvironmentMode::SANDBOX;
    }

    /**
     * Check if merchant is in production mode
     */
    public function isProductionMode(): bool
    {
        return $this->current_mode === EnvironmentMode::PRODUCTION;
    }

    /**
     * Get current API credentials based on active mode
     */
    public function getCurrentApiKey(): ?string
    {
        return __('Create or rotate a hash-based API key in the Developer Portal.');
    }

    /**
     * Get current API secret based on active mode
     */
    public function getCurrentApiSecret(): ?string
    {
        return __('API secrets are shown only once when generated.');
    }

    /**
     * Get current merchant key based on active mode
     */
    public function getCurrentMerchantKey(): ?string
    {
        return $this->getRawOriginal('merchant_key');
    }

    /**
     * Switch to sandbox mode
     */
    public function switchToSandbox(): bool
    {
        if (!$this->sandbox_enabled) {
            return false;
        }
        
        $this->current_mode = EnvironmentMode::SANDBOX;
        return $this->save();
    }

    /**
     * Switch to production mode
     */
    public function switchToProduction(): bool
    {
        $this->current_mode = EnvironmentMode::PRODUCTION;
        return $this->save();
    }

    /**
     * Check if test credentials are set
     */
    public function hasTestCredentials(): bool
    {
        return !empty($this->test_api_key) && 
               !empty($this->test_api_secret) && 
               !empty($this->test_merchant_key);
    }

    /**
     * Generate test credentials if not exists
     */
    public function generateTestCredentials(): void
    {
        // Legacy plaintext test credentials are intentionally no longer generated.
        // Use App\Services\Auth\ApiKeyManagementService to issue hash-based
        // sandbox credentials and show the secret only once.
    }

    // App\Models\Merchant.php

    public function scopeFilter($query, Request $request)
    {
        $query->when($request->filled('type'), function ($q) use ($request) {
            $q->where('status', $request->type);
        });

        $query->when($request->filled('status') && $request->status !== 'all', function ($q) use ($request) {
            $q->where('status', $request->status);
        });

        $query->when($request->filled('daterange'), function ($q) use ($request) {
            $dateRange = explode(',', $request->daterange);

            if (count($dateRange) === 2) {
                [$startDate, $endDate] = $dateRange;

                $q->whereBetween('created_at', [
                    \Carbon\Carbon::parse(trim($startDate))->startOfDay(),
                    \Carbon\Carbon::parse(trim($endDate))->endOfDay(),
                ]);
            }
        });

        $query->when($request->filled('search'), function ($q) use ($request) {
            $search = $request->search;
            $q->where(function ($q) use ($search) {
                $q->where('business_name', 'like', "%{$search}%")
                    ->orWhere('merchant_key', 'like', "%{$search}%")
                    ->orWhere('business_email', 'like', "%{$search}%")
                    ->orWhere('site_url', 'like', "%{$search}%");
            });
        });

        return $query;
    }

    public function getBusinessLogoAttribute(?string $value): string
    {
        return $value ?? '/general/static/default/shop.png';
    }

    /**
     * Get the API Key only when the merchant is APPROVED.
     */
    public function getApiKeyAttribute(?string $value): string
    {
        return __('Use the Developer Portal to create a hash-based API credential. Legacy API keys are hidden.');
    }

    /**
     * Get the API Secret only when the merchant is APPROVED.
     */
    public function getApiSecretAttribute(?string $value): string
    {
        return __('API secrets are shown only once when generated and are never stored in plain text.');
    }

    /**
     * Get the user that owns the merchant.
     *
     * @return BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the currency associated with the merchant.
     *
     * @return BelongsTo
     */
    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }
}
