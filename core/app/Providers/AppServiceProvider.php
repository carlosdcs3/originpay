<?php

namespace App\Providers;

use App\Database\Schema\Grammars\NonTransactionalPostgresGrammar;
use App\Models\Charge;
use App\Models\CustomerSubscription;
use App\Models\Merchant;
use App\Models\User;
use App\Observers\ChargeSubscriptionInvoiceObserver;
use App\Observers\CustomerSubscriptionPaymentLinkObserver;
use App\Observers\MerchantObserver;
use App\Modules\Fees\Application\Actions\SimulatePlatformFeeAction;
use App\Modules\Fees\Domain\Contracts\PlatformFeeSimulator;
use App\Modules\Webhooks\Domain\Contracts\WebhookAdminAuditRecorder;
use App\Modules\Webhooks\Infrastructure\Persistence\EloquentWebhookAdminAuditRecorder;
use App\Observers\UserObserver;
use App\Payment\PaymentGatewayFactory;
use App\Services\AppConfigService;
use App\Services\CurrencyService;
use App\Services\IpInfoService;
use App\Services\PaymentService;
use App\Services\QRCodeService;
use App\Services\TransactionService;
use App\Services\WalletService;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register application services into the service container.
     */
    public function register(): void
    {
        $this->registerServices();
        $this->bindFacades();

        // Bind AppConfigService for application-wide configuration
        $this->app->singleton(AppConfigService::class, fn ($app) => new AppConfigService);
    }

    /**
     * Register singleton services for dependency injection.
     * - Use `singleton()` for shared instances across the application.
     * - Use `bind()` if a new instance is needed for each resolve.
     */
    protected function registerServices(): void
    {
        $this->app->singleton(\App\Services\CurrencyConversionService::class, function ($app) {
            return new \App\Services\CurrencyConversionService();
        });

        $this->app->bind(\App\Services\Payment\Contracts\BalanceProviderInterface::class, \App\Services\Payment\Providers\EfiBalanceProvider::class);
        $this->app->singleton(WalletService::class, fn ($app) => new WalletService);
        $this->app->singleton(TransactionService::class, fn ($app) => new TransactionService);
        $this->app->singleton(IpInfoService::class, fn ($app) => new IpInfoService);
        $this->app->singleton(QRCodeService::class, fn ($app) => new QRCodeService);

        // Bind PaymentService with dependency injection
        $this->app->singleton(PaymentService::class, fn ($app) => new PaymentService(
            $app->make(PaymentGatewayFactory::class),
            $app->make(\App\Services\Financial\WalletBalanceService::class)
        ));

        // Operational Metrics Service (Phase 5.3)
        $this->app->singleton(\App\Contracts\Metrics\OperationalMetricsServiceInterface::class, \App\Services\Metrics\NullMetricsDriver::class);

        // Sprint 4 & 5 - Mock Bindings for Payment Methods and Sessions
        $this->app->bind(\App\Contracts\PaymentMethod\PaymentMethodVaultInterface::class, \App\Vault\MockPaymentMethodVault::class);
        $this->app->bind(\App\Contracts\PaymentMethod\PaymentMethodRepositoryInterface::class, \App\Repositories\PaymentMethod\MockPaymentMethodRepository::class);
        $this->app->bind(\App\Contracts\Payments\SessionRepositoryInterface::class, \App\Repositories\Payments\MockSessionRepository::class);
        $this->app->bind(\App\Contracts\Payments\ChargeRepositoryInterface::class, \App\Repositories\Payments\EloquentChargeRepository::class);
        $this->app->bind(\App\Contracts\Auth\ApiCredentialRepositoryInterface::class, \App\Repositories\Auth\EloquentApiCredentialRepository::class);
        $this->app->bind(PlatformFeeSimulator::class, SimulatePlatformFeeAction::class);
        $this->app->bind(WebhookAdminAuditRecorder::class, EloquentWebhookAdminAuditRecorder::class);

        // Sprint 6 - Mock Bindings for Auth
        // Sprint 10 - Gateway Layer
        $this->app->singleton(\App\Services\Gateways\GatewayRegistry::class, function ($app) {
            $registry = new \App\Services\Gateways\GatewayRegistry();
            $registry->register('mock', new \App\Services\Gateways\Adapters\MockGatewayAdapter());
            $registry->register('efi', new \App\Services\Gateways\Adapters\EfiGatewayAdapter($this->app->make(\App\Services\Gateways\Adapters\Efi\EfiHttpClient::class)));
            $registry->register('sicoob', new \App\Services\Gateways\Adapters\SicoobGatewayAdapter());
        
            return $registry;
        });

        $this->app->singleton(\App\Services\Gateways\GatewayManager::class, function ($app) {
            return new \App\Services\Gateways\GatewayManager($app->make(\App\Services\Gateways\GatewayRegistry::class));
        });
    }

    /**
     * Bind services with aliases for Facade support.
     * This allows accessing services statically via Facades.
     */
    protected function bindFacades(): void
    {
        $this->app->singleton('currency.service', fn ($app) => $app->make(CurrencyService::class));
        $this->app->singleton('wallet.service', fn ($app) => $app->make(WalletService::class));
        $this->app->singleton('transaction.service', fn ($app) => $app->make(TransactionService::class));
        $this->app->singleton('payment.service', fn ($app) => $app->make(PaymentService::class));
        $this->app->singleton('ifinfo.service', fn ($app) => $app->make(IpInfoService::class));
    }

    /**
     * Bootstrap application services.
     * Loads configuration settings, sets up observers, and ensures security features.
     */
    public function boot(AppConfigService $appConfigService): void
    {
        Blade::component('icon', \App\View\Components\Icon::class);

        $this->ensureAppKey();
        $this->configurePostgresSchemaGrammar();

        try {
            $appConfigService->applyAppSettings();
            $appConfigService->applyMailSettings();
            $appConfigService->forceHttpsIfEnabled();
            $appConfigService->applySmsConfig();
            $appConfigService->applyGoogleReCaptchaConfig();
            $appConfigService->ensureStorageSymlink();
        } catch (\Exception $e) {
            // DB not ready yet, skip loading settings from DB
        }

        $this->configureObservers();
        
        Application::macro('getDefaultLocale', function () {
            try {
                return config('app.default_language', 'en');
            } catch (\Exception $e) {
                return 'en';
            }
        });

        // Configuração de Rate Limiters para a API e Gateway
        \Illuminate\Support\Facades\RateLimiter::for('api', function (\Illuminate\Http\Request $request) {
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(120)->by($request->user()?->id ?: $request->ip());
        });

        \Illuminate\Support\Facades\RateLimiter::for('payments', function (\Illuminate\Http\Request $request) {
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        \Illuminate\Support\Facades\RateLimiter::for('originpay_api', function (\Illuminate\Http\Request $request) {
            $merchantContext = $request->attributes->get('merchant_context');
            
            $id = $merchantContext ? $merchantContext->merchantId : $request->ip();
            $environment = $merchantContext ? $merchantContext->environment : 'sandbox';
            
            $limit = 100; // sandbox default
            if ($environment === 'production') {
                $limit = 500; // live
            }
            if ($environment === 'enterprise') {
                $limit = 2000;
            }

            return \Illuminate\Cache\RateLimiting\Limit::perMinute($limit)->by($id);
        });

    }

    protected function configurePostgresSchemaGrammar(): void
    {
        $disableTransactions = filter_var(env('DB_DISABLE_SCHEMA_TRANSACTIONS', false), FILTER_VALIDATE_BOOLEAN);

        if (config('database.default') !== 'pgsql' || ! $disableTransactions) {
            return;
        }

        try {
            $connection = DB::connection('pgsql');
            $grammar = $connection->withTablePrefix(new NonTransactionalPostgresGrammar($connection));

            $connection->setSchemaGrammar($grammar);
        } catch (\Throwable $e) {
            // The database may not be reachable while installing dependencies or clearing config.
        }
    }

    /**
     * Ensure the application key is set.
     * If no application key exists, generate a new one during deployment.
     */
    protected function ensureAppKey(): void
    {
        if (config('app.key') === '') {
            Artisan::call('key:generate', ['--force' => true]);
            Log::info('Application key generated successfully during deployment.');
        }
    }

    /**
     * Register model observers to handle model events.
     */
    protected function configureObservers(): void
    {
        User::observe(UserObserver::class);
        Merchant::observe(MerchantObserver::class);
        Charge::observe(ChargeSubscriptionInvoiceObserver::class);
        CustomerSubscription::observe(CustomerSubscriptionPaymentLinkObserver::class);
    }
}
