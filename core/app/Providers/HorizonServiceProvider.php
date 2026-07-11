<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Laravel\Horizon\Horizon;
use Laravel\Horizon\HorizonApplicationServiceProvider;
use App\Models\User;

class HorizonServiceProvider extends HorizonApplicationServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        parent::boot();

        // Configure Horizon alerts (Long wait times, failures)
        Horizon::routeSmsNotificationsTo('15556667777');
        Horizon::routeMailNotificationsTo('admin@example.com');
        Horizon::routeSlackNotificationsTo('slack-webhook-url', '#horizon');
        
        // Define wait time threshold
        Horizon::night();
    }

    /**
     * Register the Horizon gate.
     *
     * This gate determines who can access Horizon in non-local environments.
     */
    protected function gate(): void
    {
        Gate::define('viewHorizon', function (?User $user) {
            // Adjust to use actual admin logic of the system. 
            // In many Laravel apps, it's $user->is_admin or checking a role via spatie/laravel-permission.
            // Returning true temporarily if user has admin privileges.
            return $user && ($user->is_admin || $user->hasRole('Super Admin') || $user->hasRole('Admin'));
        });
    }
}
