<?php

use App\Http\Middleware\CorrelationIdMiddleware;
use App\Logging\StructuredLogProcessor;
use App\Support\Observability\LogRedactor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

function r62TempLogPath(string $name): string
{
    return storage_path('framework/testing/r62-'.$name.'-'.Str::uuid().'.log');
}

function r62UseTempChannel(string $channel, string $path): void
{
    config()->set("logging.channels.$channel.path", $path);
    Log::forgetChannel($channel);
}

function r62ReadLog(string $path): string
{
    $candidate = $path;

    if (! file_exists($candidate)) {
        $dated = preg_replace('/\\.log$/', '-'.now()->format('Y-m-d').'.log', $path);
        $candidate = is_string($dated) ? $dated : $path;
    }

    clearstatcache(true, $candidate);

    return file_exists($candidate) ? (string) file_get_contents($candidate) : '';
}

it('generates a correlation id when the request does not provide one', function () {
    $response = $this->getJson('/api/health/live');

    $response->assertHeader('X-Correlation-ID');

    expect(Str::isUuid($response->headers->get('X-Correlation-ID')))->toBeTrue();
});

it('preserves a valid uuid correlation id provided by the client', function () {
    $correlationId = '22222222-2222-4222-8222-222222222222';

    $response = $this->withHeader('X-Correlation-ID', $correlationId)
        ->getJson('/api/health/live');

    $response->assertHeader('X-Correlation-ID', $correlationId);
});

it('returns different generated correlation ids for different requests', function () {
    $first = $this->getJson('/api/health/live')->headers->get('X-Correlation-ID');
    $second = $this->getJson('/api/health/live')->headers->get('X-Correlation-ID');

    expect($first)->not->toBe($second);
});

it('keeps the same correlation id available during the same request lifecycle', function () {
    $request = Request::create('/r62-test/correlation-context', 'GET');
    $seen = [];

    app(CorrelationIdMiddleware::class)->handle($request, function () use (&$seen) {
        $seen[] = Context::get('correlation_id');
        $seen[] = Context::get('correlation_id');

        return response()->noContent();
    });

    expect($seen[0])->toBe($seen[1])
        ->and(Str::isUuid($seen[0]))->toBeTrue();
});

it('adds correlation id to log context', function () {
    $request = Request::create('/r62-test/log-context', 'GET', server: ['HTTP_X_CORRELATION_ID' => '22222222-2222-4222-8222-222222222222']);
    $seen = null;

    app(CorrelationIdMiddleware::class)->handle($request, function () use (&$seen) {
        $seen = Context::get('correlation_id');

        return response()->noContent();
    });

    expect($seen)->toBe('22222222-2222-4222-8222-222222222222');
});

it('redacts sensitive operational log fields centrally', function () {
    $redacted = app(LogRedactor::class)->redact([
        'Authorization' => 'Bearer secret-token',
        'authorization' => 'Basic another-secret',
        'client_secret' => 'client-secret-value',
        'api_key' => 'api-key-value',
        'headers' => [
            'Cookie' => 'laravel_session=secret-cookie',
            'X-Safe' => 'visible',
        ],
    ]);

    expect($redacted['Authorization'])->toBe('[REDACTED]')
        ->and($redacted['authorization'])->toBe('[REDACTED]')
        ->and($redacted['client_secret'])->toBe('[REDACTED]')
        ->and($redacted['api_key'])->toBe('[REDACTED]')
        ->and($redacted['headers']['Cookie'])->toBe('[REDACTED]')
        ->and($redacted['headers']['X-Safe'])->toBe('visible');
});

it('redacts bearer tokens embedded in log strings', function () {
    $redacted = app(LogRedactor::class)->redact('Authorization: Bearer secret-token');

    expect($redacted)->not->toContain('secret-token')
        ->and($redacted)->toContain('[REDACTED]');
});

it('connects structured processor to configured operational channels without duplicates', function () {
    foreach (['payments', 'webhooks', 'gateway', 'security', 'audit', 'performance', 'single', 'daily'] as $channel) {
        $config = config("logging.channels.$channel");

        expect($config)->toBeArray()
            ->and($config['driver'] ?? null)->toBeIn(['single', 'daily'])
            ->and($config['tap'] ?? [])->toContain(StructuredLogProcessor::class)
            ->and(array_count_values($config['tap'] ?? [])[StructuredLogProcessor::class] ?? 0)->toBe(1);

        r62UseTempChannel($channel, r62TempLogPath($channel));
        expect(fn () => Log::channel($channel)->debug('r62 config load'))->not->toThrow(Throwable::class);
    }
});

it('runs the structured processor on real logs for every operational channel', function (string $channel) {
    $path = r62TempLogPath($channel);
    r62UseTempChannel($channel, $path);

    Context::add('correlation_id', '11111111-1111-4111-8111-111111111111');
    Context::add('request_method', 'POST');
    Context::add('request_path', '/api/r62/logging');
    Context::add('ip', '203.0.113.10');

    Log::channel($channel)->info('r62 processor integration', [
        'Authorization' => 'Bearer r62-authorization-secret',
        'token_line' => 'Authorization: Bearer r62-bearer-token',
        'client_secret' => 'r62-client-secret',
        'x-api-key' => 'r62-api-key',
        'headers' => [
            'Cookie' => 'laravel_session=r62-cookie-secret',
            'Set-Cookie' => 'remember_web=r62-remember-secret',
            'nested' => [
                'refresh_token' => 'r62-refresh-secret',
                'visible_value' => 'ordinary-value',
            ],
        ],
        'payment_key_type' => 'pix',
        'idempotency_key_hash' => 'hash-must-remain',
        'public_key_id' => 'public-id-must-remain',
    ]);

    $log = r62ReadLog($path);

    expect($log)->toContain('11111111-1111-4111-8111-111111111111')
        ->and($log)->toContain('POST')
        ->and($log)->toContain('/api/r62/logging')
        ->and($log)->toContain('203.0.113.10')
        ->and($log)->toContain('[REDACTED]')
        ->and($log)->not->toContain('r62-authorization-secret')
        ->and($log)->not->toContain('r62-bearer-token')
        ->and($log)->not->toContain('r62-client-secret')
        ->and($log)->not->toContain('r62-api-key')
        ->and($log)->not->toContain('r62-cookie-secret')
        ->and($log)->not->toContain('r62-refresh-secret')
        ->and($log)->toContain('ordinary-value')
        ->and($log)->toContain('hash-must-remain')
        ->and(substr_count($log, 'correlation_id'))->toBe(1);

    @unlink($path);
})->with(['payments', 'webhooks', 'gateway', 'security', 'audit', 'performance', 'single', 'daily']);

it('applies the redaction policy case-insensitively without redacting innocent key identifiers', function () {
    $redacted = app(LogRedactor::class)->redact([
        'AUTHORIZATION' => 'Bearer secret',
        'access_token' => 'access-secret',
        'refresh_token' => 'refresh-secret',
        'api-key' => 'api-secret',
        'x-api-key' => 'x-api-secret',
        'client-secret' => 'client-secret',
        'passwd' => 'password-secret',
        'senha' => 'senha-secret',
        'set-cookie' => 'cookie-secret',
        'private_key' => 'private-secret',
        'certificate' => 'certificate-secret',
        'pix_key' => 'pix-secret',
        'payment_key_type' => 'pix',
        'idempotency_key_hash' => 'safe-hash',
        'public_key_id' => 'safe-public-id',
    ]);

    expect($redacted['AUTHORIZATION'])->toBe('[REDACTED]')
        ->and($redacted['access_token'])->toBe('[REDACTED]')
        ->and($redacted['refresh_token'])->toBe('[REDACTED]')
        ->and($redacted['api-key'])->toBe('[REDACTED]')
        ->and($redacted['x-api-key'])->toBe('[REDACTED]')
        ->and($redacted['client-secret'])->toBe('[REDACTED]')
        ->and($redacted['passwd'])->toBe('[REDACTED]')
        ->and($redacted['senha'])->toBe('[REDACTED]')
        ->and($redacted['set-cookie'])->toBe('[REDACTED]')
        ->and($redacted['private_key'])->toBe('[REDACTED]')
        ->and($redacted['certificate'])->toBe('[REDACTED]')
        ->and($redacted['pix_key'])->toBe('[REDACTED]')
        ->and($redacted['payment_key_type'])->toBe('pix')
        ->and($redacted['idempotency_key_hash'])->toBe('safe-hash')
        ->and($redacted['public_key_id'])->toBe('safe-public-id');
});

it('preserves valid UUID correlation ids and replaces unsafe client supplied values', function (?string $incoming) {
    $server = [];

    if ($incoming !== null) {
        $server['HTTP_X_CORRELATION_ID'] = $incoming;
    }

    $request = Request::create('/r62-test/safe-correlation', 'GET', server: $server);
    $response = app(CorrelationIdMiddleware::class)->handle($request, fn () => response()->noContent());
    $outgoing = $response->headers->get('X-Correlation-ID');

    expect($outgoing)->not->toContain("\r")
        ->and($outgoing)->not->toContain("\n")
        ->and(Str::isUuid($outgoing))->toBeTrue();

    if ($incoming === '22222222-2222-4222-8222-222222222222') {
        expect($outgoing)->toBe($incoming);
    } else {
        expect($outgoing)->not->toBe($incoming);
    }
})->with([
    '22222222-2222-4222-8222-222222222222',
    "bad\r\ninjected: header",
    str_repeat('a', 256),
    'merchant-supplied-correlation-id',
    null,
]);

it('reports exceptions with correlation id, redaction, and no debug payload to the client', function () {
    config()->set('app.debug', false);
    config()->set('logging.default', 'single');
    $path = r62TempLogPath('exception');
    r62UseTempChannel('single', $path);

    $correlationId = '33333333-3333-4333-8333-333333333333';
    $request = Request::create('/r62-test/exception-reporting', 'GET', server: [
        'HTTP_X_CORRELATION_ID' => $correlationId,
    ]);

    $response = app(CorrelationIdMiddleware::class)->handle($request, function () {
        report(new RuntimeException('R62 reported exception'));

        Log::channel('single')->error('R62 exception context', [
            'client_secret' => 'r62-exception-client-secret',
            'Authorization' => 'Bearer r62-exception-bearer',
        ]);

        return response()->json(['ok' => false], 500);
    });

    $log = r62ReadLog($path);

    expect($response->getStatusCode())->toBe(500)
        ->and($response->headers->get('X-Correlation-ID'))->toBe($correlationId)
        ->and((string) $response->getContent())->toContain('"ok":false')
        ->and($response->getContent())->not->toContain('RuntimeException')
        ->and($response->getContent())->not->toContain('stack')
        ->and($log)->toContain($correlationId)
        ->and($log)->not->toContain('r62-exception-client-secret')
        ->and($log)->not->toContain('r62-exception-bearer');

    @unlink($path);
});
