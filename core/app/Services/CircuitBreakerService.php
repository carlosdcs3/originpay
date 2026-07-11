<?php

namespace App\Services;

use App\Models\PlatformAlert;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Throwable;

class CircuitBreakerService
{
    public const STATE_CLOSED = 'CLOSED';
    public const STATE_OPEN = 'OPEN';
    public const STATE_HALF_OPEN = 'HALF_OPEN';

    public const WINDOW_SECONDS = 60;
    public const MIN_REQUESTS = 20;
    public const ERROR_THRESHOLD_PERCENT = 50;
    public const COOLDOWN_MINUTES = 15;
    public const MAX_HALF_OPEN_TESTS = 3;

    protected PlatformAlertService $alertService;
    protected GatewayMetricsService $metricsService;
    protected GatewayHealthScoreService $healthScoreService;

    public function __construct(
        PlatformAlertService $alertService,
        GatewayMetricsService $metricsService,
        GatewayHealthScoreService $healthScoreService
    ) {
        $this->alertService = $alertService;
        $this->metricsService = $metricsService;
        $this->healthScoreService = $healthScoreService;
    }

    protected function getWindowKey(string $gatewayCode): string
    {
        return "gateway:circuit:{$gatewayCode}:window";
    }

    protected function getOpenKey(string $gatewayCode): string
    {
        return "gateway:circuit:{$gatewayCode}:open";
    }

    protected function getWasOpenKey(string $gatewayCode): string
    {
        return "gateway:circuit:{$gatewayCode}:was_open";
    }

    protected function getHalfOpenTestsKey(string $gatewayCode): string
    {
        return "gateway:circuit:{$gatewayCode}:half_open_tests";
    }

    public function getState(string $gatewayCode): string
    {
        $redis = $this->redisConnection();
        if (! $redis) {
            return self::STATE_CLOSED;
        }

        if ($redis->exists($this->getOpenKey($gatewayCode))) {
            return self::STATE_OPEN;
        }

        if ($redis->exists($this->getHalfOpenTestsKey($gatewayCode))) {
            return self::STATE_HALF_OPEN;
        }

        if ($redis->exists($this->getWasOpenKey($gatewayCode))) {
            return self::STATE_HALF_OPEN;
        }

        return self::STATE_CLOSED;
    }

    public function attemptRequest(string $gatewayCode): bool
    {
        $state = $this->getState($gatewayCode);

        if ($state === self::STATE_OPEN) {
            return false;
        }

        if ($state === self::STATE_HALF_OPEN) {
            $redis = $this->redisConnection();
            if (! $redis) {
                return true;
            }
            $tests = $redis->incr($this->getHalfOpenTestsKey($gatewayCode));
            $redis->expire($this->getHalfOpenTestsKey($gatewayCode), 120);

            return $tests <= self::MAX_HALF_OPEN_TESTS;
        }

        return true;
    }

    public function recordFailure(string $gatewayCode, \Exception $exception): void
    {
        if ($this->isClientError($exception)) {
            return;
        }

        $this->recordEvent($gatewayCode, 'failure', $exception);
    }

    public function recordSuccess(string $gatewayCode): void
    {
        $this->recordEvent($gatewayCode, 'success');
    }

    protected function recordEvent(string $gatewayCode, string $result, ?\Exception $exception = null): void
    {
        $stateBeforeEvent = $this->getState($gatewayCode);
        $redis = $this->redisConnection();
        if (! $redis) {
            return;
        }
        $key = $this->getWindowKey($gatewayCode);
        $nowMs = (int) round(microtime(true) * 1000);

        $redis->zadd($key, $nowMs, "{$result}:{$nowMs}:" . Str::uuid()->toString());
        $redis->zremrangebyscore($key, '-inf', $nowMs - (self::WINDOW_SECONDS * 1000));

        $total = $redis->zcount($key, '-inf', '+inf');
        $failures = $this->countFailuresInWindow($gatewayCode);

        $this->metricsService->increment("circuit_{$result}_total");
        $this->metricsService->increment('circuit_window_size', 0);

        if ($result === 'success') {
            $this->healthScoreService->recordSuccess($gatewayCode);
        } elseif ($exception && str_contains(strtolower(get_class($exception)), 'timeout')) {
            $this->healthScoreService->recordTimeout($gatewayCode);
        } else {
            $this->healthScoreService->recordFailure($gatewayCode);
        }

        if ($stateBeforeEvent === self::STATE_HALF_OPEN) {
            if ($result === 'success') {
                $this->closeCircuit($gatewayCode);
                return;
            }

            $this->openCircuit(
                $gatewayCode,
                $exception ? $exception->getMessage() : 'Falha durante teste em HALF_OPEN'
            );
            return;
        }

        if ($stateBeforeEvent === self::STATE_CLOSED && $total >= self::MIN_REQUESTS) {
            $errorRate = ($failures / $total) * 100;

            if ($errorRate >= self::ERROR_THRESHOLD_PERCENT) {
                $this->openCircuit(
                    $gatewayCode,
                    $exception ? $exception->getMessage() : 'Alta taxa de erros'
                );
            }
        }
    }

    protected function countFailuresInWindow(string $gatewayCode): int
    {
        $redis = $this->redisConnection();
        if (! $redis) {
            return 0;
        }

        $members = $redis->zrange($this->getWindowKey($gatewayCode), 0, -1);
        $failures = 0;

        foreach ($members as $member) {
            if (strpos($member, 'failure:') === 0) {
                $failures++;
            }
        }

        return $failures;
    }

    protected function openCircuit(string $gatewayCode, string $lastError): void
    {
        $redis = $this->redisConnection();
        if (! $redis) {
            return;
        }

        $redis->setex($this->getOpenKey($gatewayCode), self::COOLDOWN_MINUTES * 60, '1');
        $redis->setex($this->getWasOpenKey($gatewayCode), self::COOLDOWN_MINUTES * 120, '1');
        $redis->del($this->getHalfOpenTestsKey($gatewayCode));

        $this->metricsService->increment('circuit_open_total');
        $this->healthScoreService->recordCircuitOpen($gatewayCode);

        $this->alertService->createAlert([
            'category' => 'gateway_health',
            'severity' => PlatformAlert::SEVERITY_CRITICAL,
            'source' => "circuit_breaker_{$gatewayCode}",
            'title' => "Circuit Breaker OPEN: {$gatewayCode}",
            'description' => "O gateway {$gatewayCode} atingiu a taxa limite de erros e foi isolado. Ultimo erro: {$lastError}",
            'action_recommended' => 'Verificar status do provedor e aguardar cooldown de ' . self::COOLDOWN_MINUTES . ' minutos.',
        ]);
    }

    protected function closeCircuit(string $gatewayCode): void
    {
        $redis = $this->redisConnection();
        if (! $redis) {
            return;
        }

        $redis->del($this->getOpenKey($gatewayCode));
        $redis->del($this->getWasOpenKey($gatewayCode));
        $redis->del($this->getHalfOpenTestsKey($gatewayCode));
        $redis->del($this->getWindowKey($gatewayCode));

        $this->metricsService->increment('circuit_closed_total');

        $this->alertService->createAlert([
            'category' => 'gateway_health',
            'severity' => PlatformAlert::SEVERITY_INFO,
            'source' => "circuit_breaker_{$gatewayCode}",
            'title' => "Circuit Breaker CLOSED: {$gatewayCode}",
            'description' => "O gateway {$gatewayCode} passou no teste e voltou a operar normalmente.",
            'action_recommended' => 'Nenhuma acao necessaria.',
        ]);
    }

    protected function isClientError(\Exception $exception): bool
    {
        $code = $exception->getCode();

        return $code >= 400 && $code < 500;
    }

    public function getAllStates(array $gatewayCodes): array
    {
        $states = [];

        foreach ($gatewayCodes as $code) {
            $redis = $this->redisConnection();
            if (! $redis) {
                $states[$code] = [
                    'state' => self::STATE_CLOSED,
                    'window_size' => 0,
                    'failures' => 0,
                    'error_rate' => 0,
                    'cooldown_minutes' => self::COOLDOWN_MINUTES,
                ];
                continue;
            }

            $total = $redis->zcount($this->getWindowKey($code), '-inf', '+inf');
            $failures = $this->countFailuresInWindow($code);

            $states[$code] = [
                'state' => $this->getState($code),
                'window_size' => $total,
                'failures' => $failures,
                'error_rate' => $total > 0 ? round(($failures / $total) * 100, 2) : 0,
                'cooldown_minutes' => self::COOLDOWN_MINUTES,
            ];
        }

        return $states;
    }

    private function redisConnection()
    {
        try {
            return Redis::connection();
        } catch (Throwable $exception) {
            Log::warning('Circuit breaker Redis unavailable; falling back to permissive mode.', [
                'reason' => $exception->getMessage(),
            ]);

            return null;
        }
    }
}
