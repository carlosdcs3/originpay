<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Gateway\Security\GatewayAuthenticationRegistry;
use App\Gateway\Security\Drivers\EfiOAuthDriver;

class GatewayIntegrationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(GatewayAuthenticationRegistry::class, function ($app) {
            $registry = new GatewayAuthenticationRegistry();
            $registry->register('efi', new EfiOAuthDriver());
            return $registry;
        });
    }

    public function boot(): void
    {
        //
    }
}
