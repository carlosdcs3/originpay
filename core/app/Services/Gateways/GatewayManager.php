<?php

namespace App\Services\Gateways;

use App\Contracts\Gateways\GatewayInterface;
use App\Domain\Payments\GatewayAuthorizeRequest;
use App\Domain\Payments\GatewayResult;
use App\Models\MerchantGateway;
use App\Models\ApiGatewayLog;
use App\Domain\Payments\GatewayRuntimeConfig;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

class GatewayManager
{
    public function __construct(
        private readonly GatewayRegistry $registry
    ) {}

    public function authorize(GatewayAuthorizeRequest $request): GatewayResult
    {
        $gateways = MerchantGateway::where('merchant_id', $request->merchantId)
            ->where('environment', $request->environment)
            ->where('enabled', true)
            ->orderBy('priority', 'asc')
            ->get();

        if ($gateways->isEmpty()) {
            if ($request->environment === 'sandbox') {
                return $this->executeAdapter('mock', $request);
            }
            return new GatewayResult(
                success: false,
                status: 'failed',
                failureCode: 'gateway_not_configured',
                failureMessage: 'No active gateway configured for this environment.',
                isTechnicalFailure: true
            );
        }

        $lastResult = null;

        foreach ($gateways as $gatewayConfig) {
            $name = $gatewayConfig->gateway_name;
            
            // Check Circuit Breaker
            if (Cache::has("circuit_breaker_open_{$name}")) {
                // Gateway is OPEN (dead), skip it
                event('gateway.fallback', [$request->chargeId, $name]);
                continue; 
            }

            try {
                $adapter = $this->registry->resolve($name);
                
                // Health Cache
                $isHealthy = Cache::remember("gateway_health_{$name}", 30, function() use ($adapter) {
                    return $adapter->health();
                });

                if (!$isHealthy) {
                    $this->recordFailure($name);
                    $this->logInteraction($request, $name, 'authorize', 0, 'error', 'health_failed', 'Gateway offline');
                    event('gateway.fallback', [$request->chargeId, $name]);
                    continue; // technical fallback
                }

                // Construct RuntimeConfig safely
                $configData = $gatewayConfig->configuration ?? [];
                $runtimeConfig = new GatewayRuntimeConfig(
                    clientId: isset($configData['client_id']) ? Crypt::decryptString($configData['client_id']) : null,
                    clientSecret: isset($configData['client_secret']) ? Crypt::decryptString($configData['client_secret']) : null,
                    certificatePath: $configData['certificate_path'] ?? null,
                    certificatePassword: isset($configData['certificate_password']) ? Crypt::decryptString($configData['certificate_password']) : null,
                    pixKey: $configData['pix_key'] ?? null,
                    baseUrl: $configData['base_url'] ?? null,
                    environment: $gatewayConfig->environment
                );

                $retries = 0;
                $maxRetries = 2; // Total 3 attempts (1 + 2 retries)
                $successOrBusinessDecline = false;
                
                $requestWithConfig = $request->withConfig($runtimeConfig);

                while ($retries <= $maxRetries) {
                    $result = $this->executeAdapter($name, $requestWithConfig, $adapter);
                    $lastResult = $result;

                    if ($result->success || !$result->isTechnicalFailure) {
                        $successOrBusinessDecline = true;
                        break;
                    }

                    // It's a technical failure. Can we retry?
                    if (!$adapter->canRetryOn($result->failureCode ?? '')) {
                        break;
                    }

                    $retries++;
                    if ($retries <= $maxRetries) {
                        usleep(500000); // Wait 500ms before retry
                    }
                }

                if ($successOrBusinessDecline) {
                    $this->resetCircuitBreaker($name);
                    event($result->success ? 'gateway.success' : 'gateway.failed', [$request->chargeId, $name]);
                    return $result;
                }

                // If technical failure persists after retries
                $this->recordFailure($name);
                event('gateway.fallback', [$request->chargeId, $name]);
                
            } catch (\Exception $e) {
                $this->recordFailure($name);
                $this->logInteraction($request, $name, 'authorize', 0, 'error', 'exception', $e->getMessage());
                event('gateway.fallback', [$request->chargeId, $name]);
                // technical fallback, continue loop
            }
        }

        return $lastResult ?? new GatewayResult(
            success: false,
            status: 'failed',
            failureCode: 'all_gateways_failed',
            failureMessage: 'All configured gateways failed to process the request.',
            isTechnicalFailure: true
        );
    }

    public function getStatus(string $merchantId, string $environment, string $gatewayName, string $gatewayReference): GatewayResult
    {
        $gatewayConfig = MerchantGateway::where('merchant_id', $merchantId)
            ->where('environment', $environment)
            ->where('gateway_name', $gatewayName)
            ->first();

        if (!$gatewayConfig) {
            throw new \Exception("Gateway configuration not found for status check.");
        }

        $configData = $gatewayConfig->configuration ?? [];
        $runtimeConfig = new GatewayRuntimeConfig(
            clientId: isset($configData['client_id']) ? Crypt::decryptString($configData['client_id']) : null,
            clientSecret: isset($configData['client_secret']) ? Crypt::decryptString($configData['client_secret']) : null,
            certificatePath: $configData['certificate_path'] ?? null,
            certificatePassword: isset($configData['certificate_password']) ? Crypt::decryptString($configData['certificate_password']) : null,
            pixKey: $configData['pix_key'] ?? null,
            baseUrl: $configData['base_url'] ?? null,
            environment: $gatewayConfig->environment
        );

        $adapter = $this->registry->resolve($gatewayName);
        return $adapter->getStatus($gatewayReference, $runtimeConfig);
    }

    private function executeAdapter(string $name, GatewayAuthorizeRequest $request, ?GatewayInterface $adapter = null): GatewayResult
    {
        $adapter = $adapter ?? $this->registry->resolve($name);
        
        $startTime = microtime(true);
        $result = $adapter->authorize($request);
        $duration = (int) ((microtime(true) - $startTime) * 1000);

        $this->logInteraction(
            $request,
            $name,
            'authorize',
            $duration,
            $result->success ? 'success' : 'error',
            $result->failureCode,
            $result->failureMessage
        );

        event('gateway.selected', [$request->chargeId, $name]);

        return $result;
    }

    private function logInteraction(GatewayAuthorizeRequest $request, string $gateway, string $operation, int $durationMs, string $status, ?string $code, ?string $error): void
    {
        // Notice we do NOT log the raw payloads here to prevent sensitive data leaks.
        ApiGatewayLog::create([
            'request_id' => 'req_' . Str::random(16),
            'merchant_id' => $request->merchantId,
            'charge_id' => $request->chargeId,
            'gateway' => $gateway,
            'operation' => $operation,
            'duration_ms' => $durationMs,
            'status' => $status,
            'response_code' => $code,
            'error' => $error,
        ]);
    }

    private function recordFailure(string $name): void
    {
        $breakerKey = "circuit_breaker_failures_{$name}";
        $failures = Cache::get($breakerKey, 0) + 1;
        
        if ($failures >= 10) {
            // OPEN Circuit Breaker for 30 seconds
            Cache::put("circuit_breaker_open_{$name}", true, 30);
            Cache::forget($breakerKey);
        } else {
            Cache::put($breakerKey, $failures, 60); // Reset failures counter after 60s without errors
        }
    }

    private function resetCircuitBreaker(string $name): void
    {
        Cache::forget("circuit_breaker_failures_{$name}");
        Cache::forget("circuit_breaker_open_{$name}");
    }
}
