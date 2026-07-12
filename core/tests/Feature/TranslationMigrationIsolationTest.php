<?php

use App\Providers\AppServiceProvider;
use JoeDixon\Translation\TranslationServiceProvider;

it('keeps the application language migration as the only canonical source', function () {
    config()->set('translation.driver', 'database');

    $provider = new AppServiceProvider(app());
    $provider->register();

    expect(config('translation.driver'))->toBe('file');

    (new TranslationServiceProvider(app()))->boot();

    expect(app('migrator')->paths())->not->toContain(
        base_path('vendor/joedixon/laravel-translation/database/migrations')
    );
});
