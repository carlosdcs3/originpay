<?php

use App\Jobs\ProcessGatewayWebhookJob;
use App\Support\Observability\CarriesOperationalContext;
use App\Support\Observability\QueueOperationalContext;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

function r63PayloadFor(object $job): string
{
    $connection = app('queue')->connection('sync');
    $createPayload = function (object $job): string {
        return $this->createPayload($job, 'critical');
    };

    return $createPayload->call($connection, $job);
}

function r63TempLogPath(string $name): string
{
    return storage_path('framework/testing/r63-'.$name.'-'.Str::uuid().'.log');
}

function r63UseTempSingleLog(string $path): void
{
    config()->set('logging.channels.single.path', $path);
    Log::forgetChannel('single');
}

it('serializes current request correlation id and safe domain context into a critical webhook job payload', function () {
    Context::add('correlation_id', '33333333-3333-4333-8333-333333333333');
    Context::add('merchant_id', 123);
    Context::add('payment_id', 456);
    Context::add('gateway', 'efi');
    Context::add('webhook_event_id', 789);

    $payload = r63PayloadFor(new ProcessGatewayWebhookJob('efi', ['id' => 'evt_1'], [
        'Authorization' => ['Bearer very-secret-token'],
        'X-Api-Key' => ['raw-api-key'],
        'client_secret' => ['client-secret'],
        'X-Safe' => ['ok'],
    ], 789));

    expect($payload)->toContain('33333333-3333-4333-8333-333333333333')
        ->and($payload)->toContain('merchant_id')
        ->and($payload)->toContain('payment_id')
        ->and($payload)->toContain('webhook_event_id')
        ->and($payload)->toContain('efi')
        ->and($payload)->not->toContain('Authorization')
        ->and($payload)->not->toContain('Bearer very-secret-token')
        ->and($payload)->not->toContain('raw-api-key')
        ->and($payload)->not->toContain('client-secret');
});

it('generates a safe correlation id and distinct job ids when no request context exists', function () {
    Context::flush();

    $first = new ProcessGatewayWebhookJob('efi', ['id' => 'evt_1'], [], 10);
    $second = new ProcessGatewayWebhookJob('efi', ['id' => 'evt_2'], [], 11);

    expect(Str::isUuid($first->operationalContext()['correlation_id']))->toBeTrue()
        ->and(Str::isUuid($first->operationalContext()['job_id']))->toBeTrue()
        ->and($first->operationalContext()['job_id'])->not->toBe($second->operationalContext()['job_id']);
});

it('restores context for worker logs and clears it after processing', function () {
    $path = r63TempLogPath('worker');
    r63UseTempSingleLog($path);

    $job = new ProcessGatewayWebhookJob('efi', ['id' => 'evt_1'], [], 77);
    $context = $job->operationalContext();
    Context::flush();

    QueueOperationalContext::restore($job, 'webhooks_ingestion', 2);
    Log::channel('single')->info('job log line', ['result' => 'processing']);

    QueueOperationalContext::clear();
    Log::channel('single')->info('after job log line');

    $contents = (string) file_get_contents($path);

    expect($contents)->toContain($context['correlation_id'])
        ->and($contents)->toContain($context['job_id'])
        ->and($contents)->toContain('webhooks_ingestion')
        ->and($contents)->toContain('attempt')
        ->and(substr_count($contents, $context['correlation_id']))->toBe(1);
});

it('preserves correlation id and documented job id across retry attempts', function () {
    $job = new ProcessGatewayWebhookJob('efi', ['id' => 'evt_1'], [], 77);
    $first = $job->operationalContext();

    QueueOperationalContext::restore($job, 'webhooks_ingestion', 1);
    QueueOperationalContext::clear();
    QueueOperationalContext::restore($job, 'webhooks_ingestion', 2);

    expect(Context::get('correlation_id'))->toBe($first['correlation_id'])
        ->and(Context::get('job_id'))->toBe($first['job_id']);
});

it('failure logs include restored correlation id and job id', function () {
    $path = r63TempLogPath('failure');
    r63UseTempSingleLog($path);

    $job = new ProcessGatewayWebhookJob('efi', ['id' => 'evt_1'], [], 77);
    QueueOperationalContext::restore($job, 'webhooks_ingestion', 1);
    Log::channel('single')->error('job failed', ['error_class' => RuntimeException::class]);
    QueueOperationalContext::clear();

    $contents = (string) file_get_contents($path);

    expect($contents)->toContain($job->operationalContext()['correlation_id'])
        ->and($contents)->toContain($job->operationalContext()['job_id']);
});

it('events and listeners can inherit the active job correlation context', function () {
    $job = new ProcessGatewayWebhookJob('efi', ['id' => 'evt_1'], [], 77);
    QueueOperationalContext::restore($job, 'webhooks_ingestion', 1);

    $event = new class
    {
        use CarriesOperationalContext;

        public function __construct()
        {
            $this->captureOperationalContext();
        }
    };

    Context::flush();
    QueueOperationalContext::restore($event, 'listeners', 1);

    expect(Context::get('correlation_id'))->toBe($job->operationalContext()['correlation_id']);

    QueueOperationalContext::clear();
});

it('does not let the next job inherit previous job context', function () {
    $first = new ProcessGatewayWebhookJob('efi', ['id' => 'evt_1'], [], 1);
    $second = new ProcessGatewayWebhookJob('mock', ['id' => 'evt_2'], [], 2);

    QueueOperationalContext::restore($first, 'webhooks_ingestion', 1);
    QueueOperationalContext::clear();
    QueueOperationalContext::restore($second, 'webhooks_ingestion', 1);

    expect(Context::get('correlation_id'))->toBe($second->operationalContext()['correlation_id'])
        ->and(Context::get('correlation_id'))->not->toBe($first->operationalContext()['correlation_id']);

    QueueOperationalContext::clear();
});
