<?php

namespace App\Services;

use App\Constants\Status;
use App\Models\Language;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

class AppConfigService
{
    /**
     * Apply application-wide settings dynamically.
     */
    public function applyAppSettings(): void
    {
        try {
            $defaultLanguage     = Language::default();
            $defaultLanguageCode = $defaultLanguage->code ?? 'en';
            $timezone = setting('site_timezone', 'UTC');
            if (! is_string($timezone) || trim($timezone) === '') {
                $timezone = 'UTC';
            }

            Config::set([
                'app.timezone'                          => $timezone,
                'app.env'                               => setting('site_environment', 'local'),
                'app.debug'                             => setting('development_mode', true),
                'app.locale'                            => $defaultLanguageCode,
                'app.default_language'                  => $defaultLanguageCode,
                'app.default_currency'                  => siteCurrency(),
                'app.default_currency_symbol'           => siteCurrency('symbol'),
                'security.duplicate_submission_timeout' => setting('submission_lock_duration', 5),
            ]);
        } catch (\Exception $e) {
            // DB probably not migrated yet
        }
    }

    /**
     * Dynamically apply SMTP email settings.
     */
    public function applyMailSettings(): void
    {
        $defaultMailer = env('MAIL_MAILER', 'smtp');
        
        Config::set('mail', [
            'default' => $defaultMailer,
            'from'    => [
                'name'    => setting('site_title', env('MAIL_FROM_NAME', 'Wallet System')),
                'address' => setting('email_from_address', env('MAIL_FROM_ADDRESS', 'noreply@example.com')),
            ],
            'mailers' => [
                'smtp' => [
                    'transport'  => 'smtp',
                    'host'       => setting('mail_host', env('MAIL_HOST', 'smtp.example.com')),
                    'port'       => setting('mail_port', env('MAIL_PORT', 587)),
                    'username'   => setting('mail_username', env('MAIL_USERNAME', 'user@example.com')),
                    'password'   => setting('mail_password', env('MAIL_PASSWORD', 'password')),
                    'encryption' => setting('mail_secure', env('MAIL_ENCRYPTION', 'tls')),
                ],
                'log' => [
                    'transport' => 'log',
                    'channel' => env('MAIL_LOG_CHANNEL'),
                ],
                'array' => [
                    'transport' => 'array',
                ],
            ],
        ]);
    }

    public function applySmsConfig(): void
    {
        $twilioConfig = pluginCredentials('twilio');

        if (! isset($twilioConfig['status']) || $twilioConfig['status'] !== Status::TRUE) {
            return;
        }

        Config::set('twilio-notification-channel', [
            'account_sid' => $twilioConfig['account_sid'],
            'auth_token'  => $twilioConfig['auth_token'],
            'from'        => $twilioConfig['from'],
        ]);
    }

    public function applyGoogleReCaptchaConfig(): void
    {
        $googleReCaptchaCredentials = pluginCredentials('google-recaptcha');

        if (! isset($googleReCaptchaCredentials['status']) || $googleReCaptchaCredentials['status'] !== Status::TRUE) {
            return;
        }

        config()->set([
            'services.recaptcha.key'    => $googleReCaptchaCredentials['recaptcha_key'],
            'services.recaptcha.secret' => $googleReCaptchaCredentials['recaptcha_secret'],
            'services.recaptcha.status' => $googleReCaptchaCredentials['status'],
        ]);
    }

    /**
     * Force HTTPS if enabled.
     */
    public function forceHttpsIfEnabled(): void
    {
        if (config('app.env') !== 'local' && setting('force_https', false)) {
            URL::forceScheme('https');
        }
    }

    /**
     * Ensures the public/storage symlink exists for file uploads.
     * Attempts creation only if missing, logs outcome, never interrupts app.
     */
    public function ensureStorageSymlink(): void
    {
        $link = public_path('storage');

        if (! is_link($link) && ! file_exists($link)) {
            try {
                \Artisan::call('storage:link');
                if (is_link($link) || file_exists($link)) {
                    Log::info('Storage symlink created successfully.');
                } else {
                    Log::warning('Tried to create storage symlink, but it does not exist. Check server permissions.');
                }
            } catch (\Throwable $e) {
                Log::error('Storage symlink creation failed: '.$e->getMessage());
            }
        }
    }
}
