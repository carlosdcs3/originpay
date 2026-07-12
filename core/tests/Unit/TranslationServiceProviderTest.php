<?php

namespace Tests\Unit;

use App\Providers\TranslationServiceProvider as ApplicationTranslationServiceProvider;
use Illuminate\Database\Migrations\Migrator;
use JoeDixon\Translation\Drivers\Translation;
use JoeDixon\Translation\Scanner;
use JoeDixon\Translation\TranslationServiceProvider as VendorTranslationServiceProvider;
use Tests\TestCase;

class TranslationServiceProviderTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        $databasePath = dirname(__DIR__, 2).DIRECTORY_SEPARATOR.'database'.DIRECTORY_SEPARATOR.'database.sqlite';

        if (! file_exists($databasePath)) {
            touch($databasePath);
        }

        $database = new \PDO('sqlite:'.$databasePath);
        $database->exec('create table if not exists languages (id integer primary key autoincrement, flag varchar, name varchar, code varchar, is_default integer default 0, is_rtl integer default 0, status integer default 1, created_at datetime, updated_at datetime)');
        $database->exec("insert into languages (name, code, is_default, is_rtl, status) select 'English', 'en', 1, 0, 1 where not exists(select 1 from languages)");
        $database->exec('create table if not exists currencies (id integer primary key autoincrement, name varchar, code varchar, symbol varchar, "default" integer default 0, status integer default 1, created_at datetime, updated_at datetime)');
        $database->exec("insert into currencies (name, code, symbol, \"default\", status) select 'US Dollar', 'USD', '$', 1, 1 where not exists(select 1 from currencies)");
        $database->exec('create table if not exists plugins (id integer primary key autoincrement, name varchar, code varchar, credentials text, status integer default 0, created_at datetime, updated_at datetime)');

        foreach (['twilio', 'google-recaptcha', 'pusher', 'ipinfo'] as $code) {
            $statement = $database->prepare("insert into plugins (name, code, credentials, status) select ?, ?, '{}', 0 where not exists(select 1 from plugins where code = ?)");
            $statement->execute([$code, $code, $code]);
        }
    }

    public function test_it_does_not_load_vendor_translation_package_migrations_into_the_migrator(): void
    {
        /** @var Migrator $migrator */
        $migrator = $this->app->make('migrator');

        $loadedMigrationFiles = $migrator->getMigrationFiles([
            database_path('migrations'),
            ...$migrator->paths(),
        ]);

        $this->assertArrayNotHasKey('2018_08_29_200844_create_languages_table', $loadedMigrationFiles);
        $this->assertArrayHasKey('2024_07_11_042348_create_languages_table', $loadedMigrationFiles);
        $this->assertStringNotContainsString('vendor/joedixon/laravel-translation', implode("\n", $loadedMigrationFiles));
    }

    public function test_it_keeps_the_translation_package_services_used_by_the_application_available(): void
    {
        $loadedProviders = $this->app->getLoadedProviders();

        $this->assertArrayHasKey(ApplicationTranslationServiceProvider::class, $loadedProviders);
        $this->assertArrayNotHasKey(VendorTranslationServiceProvider::class, $loadedProviders);
        $this->assertInstanceOf(Scanner::class, $this->app->make(Scanner::class));
        $this->assertInstanceOf(Translation::class, $this->app->make(Translation::class));
        $this->assertTrue($this->app->make('view')->exists('translation::languages.index'));
    }
}
