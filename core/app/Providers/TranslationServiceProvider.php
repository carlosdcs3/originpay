<?php

namespace App\Providers;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;
use Illuminate\Translation\Translator;
use JoeDixon\Translation\Console\Commands\AddLanguageCommand;
use JoeDixon\Translation\Console\Commands\AddTranslationKeyCommand;
use JoeDixon\Translation\Console\Commands\ListLanguagesCommand;
use JoeDixon\Translation\Console\Commands\ListMissingTranslationKeys;
use JoeDixon\Translation\Console\Commands\SynchroniseMissingTranslationKeys;
use JoeDixon\Translation\Console\Commands\SynchroniseTranslationsCommand;
use JoeDixon\Translation\ContractDatabaseLoader;
use JoeDixon\Translation\Drivers\Translation;
use JoeDixon\Translation\InterfaceDatabaseLoader;
use JoeDixon\Translation\Scanner;
use JoeDixon\Translation\TranslationManager;

class TranslationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(base_path('vendor/joedixon/laravel-translation/config/translation.php'), 'translation');

        $this->registerCommands();
        $this->registerContainerBindings();
        $this->registerDatabaseTranslatorWhenRequired();
    }

    public function boot(): void
    {
        $this->loadViewsFrom(base_path('vendor/joedixon/laravel-translation/resources/views'), 'translation');
        $this->loadRoutesFrom(base_path('vendor/joedixon/laravel-translation/routes/web.php'));
        $this->loadTranslationsFrom(base_path('vendor/joedixon/laravel-translation/resources/lang'), 'translation');

        $this->publishes([
            base_path('vendor/joedixon/laravel-translation/config/translation.php') => config_path('translation.php'),
        ], 'config');

        $this->publishes([
            base_path('vendor/joedixon/laravel-translation/public/assets') => public_path('vendor/translation'),
        ], 'assets');

        $this->publishes([
            base_path('vendor/joedixon/laravel-translation/resources/views') => resource_path('views/vendor/translation'),
        ]);

        $this->publishes([
            base_path('vendor/joedixon/laravel-translation/resources/lang') => resource_path('lang/vendor/translation'),
        ]);

        $this->registerHelpers();
    }

    private function registerCommands(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->commands([
            AddLanguageCommand::class,
            AddTranslationKeyCommand::class,
            ListLanguagesCommand::class,
            ListMissingTranslationKeys::class,
            SynchroniseMissingTranslationKeys::class,
            SynchroniseTranslationsCommand::class,
        ]);
    }

    private function registerContainerBindings(): void
    {
        $this->app->singleton(Scanner::class, function () {
            $config = $this->app['config']['translation'];

            return new Scanner(new Filesystem, $config['scan_paths'], $config['translation_methods']);
        });

        $this->app->singleton(Translation::class, function ($app) {
            return (new TranslationManager($app, $app['config']['translation'], $app->make(Scanner::class)))->resolve();
        });
    }

    private function registerDatabaseTranslatorWhenRequired(): void
    {
        if ($this->app['config']['translation.driver'] !== 'database') {
            return;
        }

        $this->app->singleton('translation.loader', function ($app) {
            if (interface_exists('Illuminate\\Contracts\\Translation\\Loader')) {
                return new ContractDatabaseLoader($app->make(Translation::class));
            }

            return new InterfaceDatabaseLoader($app->make(Translation::class));
        });

        $this->app->singleton('translator', function ($app) {
            $translator = new Translator($app['translation.loader'], $app['config']['app.locale']);
            $translator->setFallback($app['config']['app.fallback_locale']);

            return $translator;
        });
    }

    private function registerHelpers(): void
    {
        $helpersPath = base_path('vendor/joedixon/laravel-translation/resources/helpers.php');

        if (file_exists($helpersPath)) {
            require_once $helpersPath;
        }
    }
}
