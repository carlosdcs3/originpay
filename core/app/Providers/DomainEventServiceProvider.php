<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

use App\Domain\Disputes\Events\DisputeCreated;
use App\Domain\Disputes\Events\MerchantReplied;
use App\Domain\Disputes\Events\DisputeClosed;

use App\Domain\Disputes\Listeners\UpdateHealthScoreListener;
use App\Domain\Ledger\Listeners\HoldFundsOnDisputeCreated;
use App\Domain\Risk\Listeners\EvaluateFraudScoreOnMerchantReply;

class DomainEventServiceProvider extends ServiceProvider
{
    /**
     * The domain event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        DisputeCreated::class => [
            HoldFundsOnDisputeCreated::class,
        ],
        
        MerchantReplied::class => [
            UpdateHealthScoreListener::class,
            EvaluateFraudScoreOnMerchantReply::class,
        ],
        
        DisputeClosed::class => [
            // Future listeners: DispatchEmailOnDisputeClosed, ReleaseFundsListener
        ]
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();
    }
}
