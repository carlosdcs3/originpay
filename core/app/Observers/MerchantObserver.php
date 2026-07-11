<?php

namespace App\Observers;

use App\Enums\EnvironmentMode;
use App\Enums\MerchantStatus;
use App\Models\Merchant;
use Illuminate\Support\Str;

class MerchantObserver
{
    public function creating(Merchant $merchant): void
    {
        // Keep only the non-secret legacy merchant identifier required by the
        // historical schema. API secrets must be issued by the hash-based
        // ApiCredential/ApiKey flows and shown only once at creation time.
        $merchant->merchant_key ??= 'mrc_' . Str::lower(Str::random(24));
        $merchant->status       = MerchantStatus::PENDING;

        // Set default sandbox settings using enum
        $merchant->current_mode     = EnvironmentMode::SANDBOX; // Start in sandbox mode for safety
        $merchant->sandbox_enabled  = true;                     // Enable sandbox by default
    }

    public function created(Merchant $merchant): void
    {
        // Log merchant creation with environment details. Never log API keys or secrets.
        \Illuminate\Support\Facades\Log::info('Merchant created', [
            'merchant_id' => $merchant->id,
            'business_name' => $merchant->business_name,
            'sandbox_enabled' => $merchant->sandbox_enabled,
            'current_mode' => $merchant->current_mode->value
        ]);
    }
}
