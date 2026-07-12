<?php

namespace App\Providers;

use App\Contracts\Auth\ApiCredentialRepositoryInterface;
use App\Contracts\Metrics\OperationalMetricsServiceInterface;
use App\Contracts\PaymentMethod\PaymentMethodRepositoryInterface;
use App\Contracts\PaymentMethod\PaymentMethodVaultInterface;
use App\Contracts\Payments\ChargeRepositoryInterface;
use App\Contracts\Payments\SessionRepositoryInterface;
use App\Database\Schema\Grammars\NonTransactionalPostgresGrammar;
use App\Models\Charge;
use App\Models\CustomerSubscription;
use App\Models\Merchant;
use App\Models\User;
use App\Modules\Fees\Application\Actions\SimulatePlatformFeeAction;
use App\Modules\Fees\Domain\Contracts\PlatformFeeSimulator;
use App\Modules\Webhooks\Domain\Contracts\WebhookAdminAuditRecorder;
use App\Modules\Webhooks\Infrastructure\Persistence\EloquentWebhookAdminAuditRecorder;
use App\Observers\ChargeSubscriptionInvoiceObserver;
use App\Observers\CustomerSubscriptionPaymentLinkObserver;
use App\Observers\MerchantObserver;
use App\Observers\UserObserver;
use App\Payment\PaymentGatewayFactory;
use App\Repositories\Auth\EloquentApiCredentialRepository;
use App\Repositories\PaymentMethod\MockPaymentMethodRepository;
use App\Repositories\Payments\EloquentChargeRepository;
use App\Repositories\Payments\MockSessionRepository;
use App\Services\AppConfigService;
use App\Services\CurrencyConversionService;
use App\Services\CurrencyService;
use App\Services\Financial\WalletBalanceService;
use App\Services\Gateways\Adapters\Efi\EfiHttpClient;
use App\Services\Gateways\Adapters\EfiGatewayAdapter;
use App\Services\Gateways\Adapters\MockGatewayAdapter;
use App\Services\Gateways\Adapters\SicoobGatewayAdapter;
use App\Services\Gateways\GatewayManager;
use App\Services\Gateways\GatewayRegistry;
use App\Services\IpInfoService;
use App\Services\Metrics\NullMetricsDriver;
use App\Services\Payment\Contracts\BalanceProviderInterface;
use App\Services\Payment\Providers\EfiBalanceProvider;
use App\Services\PaymentService;
use App\Services\QRCodeService;
use App\Services\TransactionService;
use App\Services\WalletService;
use App\Support\Observability\Metrics\InMemoryMetricsStore;
use App\Support\Observability\Metrics\LocalMetricsCollector;
use App\Support\Observability\Metrics\MetricsStore;
use App\Support\Observability\Metrics\NoOpMetricsStore;
use App\Support\Observability\Metrics\RedisMetricsStore;
use App\Support\Observability\QueueOperationalContext;
use App\Vault\MockPaymentMethodVault;
use App\View\Components\Icon;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register application services into the service container.
     */
    public function register(): void
    {
        $this->app['config']->set('translation.driver', 'file');

        $this->app->singleton(MetricsStore::class, function () {
            try {
                $config = config('observability.metrics_baseline', []);
                $backend = is_array($config) ? ($config['backend'] ?? 'redis') : 'invalid';

                if ($backend === 'memory') {
                    return new InMemoryMetricsStore;
                }

                if ($backend !== 'redis' || ! class_exists(\Redis::class)) {
                    return new NoOpMetricsStore;
                }

                return new RedisMetricsStore(
                    Redis::connection($config['redis_connection'] ?? null),
                    max(1, (int) ($config['ttl_seconds'] ?? 7776000)),
                    (string) ($config['redis_namespace'] ?? 'originpay:metrics'),
                );
            } catch (\Throwable) {
                return new NoOpMetricsStore;
            }
        });
        $this->app->singleton(LocalMetricsCollector::class, function ($app) {
            try {
                $config = config('observability.metrics_baseline', []);

                return new LocalMetricsCollector(
                    $app->make(MetricsStore::class),
                    is_array($config) ? $config : [],
                );
            } catch (\Throwable) {
                return new LocalMetricsCollector(new NoOpMetricsStore);
            }
        });
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
        $this->app->singleton(CurrencyConversionService::class, function ($app) {
            return new CurrencyConversionService;
        });

        $this->app->bind(BalanceProviderInterface::class, EfiBalanceProvider::class);
        $this->app->singleton(WalletService::class, fn ($app) => new WalletService);
        $this->app->singleton(TransactionService::class, fn ($app) => new TransactionService);
        $this->app->singleton(IpInfoService::class, fn ($app) => new IpInfoService);
        $this->app->singleton(QRCodeService::class, fn ($app) => new QRCodeService);

        // Bind PaymentService with dependency injection
        $this->app->singleton(PaymentService::class, fn ($app) => new PaymentService(
            $app->make(PaymentGatewayFactory::class),
            $app->make(WalletBalanceService::class)
        ));

        // Operational Metrics Service (Phase 5.3)
        $this->app->singleton(OperationalMetricsServiceInterface::class, NullMetricsDriver::class);

        // Sprint 4 & 5 - Mock Bindings for Payment Methods and Sessions
        $this->app->bind(PaymentMethodVaultInterface::class, MockPaymentMethodVault::class);
        $this->app->bind(PaymentMethodRepositoryInterface::class, MockPaymentMethodRepository::class);
        $this->app->bind(SessionRepositoryInterface::class, MockSessionRepository::class);
        $this->app->bind(ChargeRepositoryInterface::class, EloquentChargeRepository::class);
        $this->app->bind(ApiCredentialRepositoryInterface::class, EloquentApiCredentialRepository::class);
        $this->app->bind(PlatformFeeSimulator::class, SimulatePlatformFeeAction::class);
        $this->app->bind(WebhookAdminAuditRecorder::class, EloquentWebhookAdminAuditRecorder::class);

        // Sprint 6 - Mock Bindings for Auth
        // Sprint 10 - Gateway Layer
        $this->app->singleton(GatewayRegistry::class, function ($app) {
            $registry = new GatewayRegistry;
            $registry->register('mock', new MockGatewayAdapter);
            $registry->register('efi', new EfiGatewayAdapter($this->app->make(EfiHttpClient::class)));
            $registry->register('sicoob', new SicoobGatewayAdapter);

            return $registry;
        });

        $this->app->singleton(GatewayManager::class, function ($app) {
            return new GatewayManager($app->make(GatewayRegistry::class));
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
        Blade::component('icon', Icon::class);

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
        $this->configureQueueOperationalContext();

        Application::macro('getDefaultLocale', function () {
            try {
                return config('app.default_language', 'en');
            } catch (\Exception $e) {
                return 'en';
            }
        });

        // Configuração de Rate Limiters para a API e Gateway
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(120)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('payments', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('originpay_api', function (Request $request) {
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

            return Limit::perMinute($limit)->by($id);
        });

    }

    protected function configureQueueOperationalContext(): void
    {
        Queue::before(function (JobProcessing $event): void {
            $payload = $event->job->payload();
            $command = isset($payload['data']['command']) && is_string($payload['data']['command'])
                ? @unserialize($payload['data']['command'])
                : null;

            if (is_object($command)) {
                QueueOperationalContext::restore($command, $event->job->getQueue(), $event->job->attempts());
            }
        });

        Queue::after(function (JobProcessed $event): void {
            QueueOperationalContext::clear();
        });

        Queue::exceptionOccurred(function (JobExceptionOccurred $event): void {
            Log::error('Queued job failed', [
                'result' => 'failed',
                'error_class' => get_class($event->exception),
            ]);

            QueueOperationalContext::clear();
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
