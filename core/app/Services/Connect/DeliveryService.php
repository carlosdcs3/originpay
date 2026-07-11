<?php
namespace App\Services\Connect;

use App\Models\Connect\ConnectCampaignRecipient;
use App\Models\Connect\ConnectCampaignDeliveryAttempt;
use App\Services\Connect\Delivery\ProviderRegistry;
use App\Services\Connect\Delivery\DeliveryResult;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Exception;

class DeliveryService
{
    protected ProviderRegistry $registry;

    public function __construct(ProviderRegistry $registry)
    {
        $this->registry = $registry;
    }

    public function dispatch(ConnectCampaignRecipient $recipient, string $compiledMessage, array $metadata = []): DeliveryResult
    {
        $credentials = $this->registry->getActiveCredentials($recipient->merchant_id, $recipient->channel);

        if (empty($credentials)) {
            throw new Exception("Nenhuma credencial ativa encontrada para o canal {$recipient->channel}.");
        }

        $lastResult = null;

        // Failover Loop via Priority
        foreach ($credentials as $cred) {
            // Circuit Breaker Check
            $circuitKey = "connect_circuit_breaker_cred_{$cred->id}";
            if (Cache::has($circuitKey)) {
                continue; // OPEN Circuit, skip to next failover priority
            }

            $driver = $this->registry->resolveDriver($cred->provider);
            $adapter = $this->registry->resolveAdapter($recipient->channel, $driver, $cred);

            // Audit Record
            $attempt = new ConnectCampaignDeliveryAttempt([
                'uuid' => Str::uuid()->toString(),
                'recipient_id' => $recipient->id,
                'execution_id' => $recipient->execution_id,
                'provider' => $cred->provider,
                'priority' => $cred->priority,
                'attempt_number' => $recipient->attempts + 1,
                'driver_class' => get_class($driver),
                'adapter_class' => get_class($adapter),
            ]);

            try {
                $lastResult = $adapter->send($recipient, $compiledMessage, $metadata);
                
                $attempt->status = $lastResult->success ? 'success' : 'failed';
                $attempt->latency_ms = $lastResult->latencyMs;
                $attempt->response_payload = (array) $lastResult;
                $attempt->error_message = $lastResult->errorMessage;
                $attempt->save();

                if ($lastResult->success) {
                    $cred->recordSuccess();
                    // Reset circuit breaker errors on success
                    Cache::forget("connect_circuit_errors_cred_{$cred->id}");
                    return $lastResult;
                } else {
                    $cred->recordFailure($lastResult->errorMessage);
                    $this->evaluateCircuitBreaker($cred);
                    
                    // If transient, don't failover, throw it back up to Job for Laravel retry
                    if ($lastResult->isTransient) {
                        return $lastResult; 
                    }
                    // If NOT transient (e.g. 400 Bad Request, blocked), let's skip failover too, because it's a contact error, not provider error.
                    // Wait, if it's 500 from provider, it's transient. 
                    // Failover is only for 5xx/Timeouts that we want to bypass instantly.
                    // Let's assume for this Epic that if isTransient is false, it's a contact error, so we return immediately.
                    // If we wanted failover on 500, the driver would mark it as transient, but maybe another flag `isProviderError` would be better.
                    // For simplicity, we will failover on specific driver exceptions or transient.
                }

            } catch (Exception $e) {
                // Hard exception (Timeout, network down)
                $attempt->status = 'failed';
                $attempt->error_message = $e->getMessage();
                $attempt->save();
                
                $cred->recordFailure($e->getMessage());
                $this->evaluateCircuitBreaker($cred);
                
                // Continue loop for failover
            }
        }

        // If we exhausted all priorities and didn't return a success
        return $lastResult ?? new DeliveryResult(false, 'unknown', null, 'failed', 0, 0, 'FAILOVER_EXHAUSTED', 'All providers failed or circuit open', false);
    }

    protected function evaluateCircuitBreaker($credential)
    {
        $errorKey = "connect_circuit_errors_cred_{$credential->id}";
        $errors = Cache::increment($errorKey);
        
        // If 5 consecutive errors, open circuit for 5 minutes
        if ($errors >= 5) {
            $circuitKey = "connect_circuit_breaker_cred_{$credential->id}";
            Cache::put($circuitKey, true, now()->addMinutes(5));
            Cache::forget($errorKey); // Reset for next window
        }
    }
}
