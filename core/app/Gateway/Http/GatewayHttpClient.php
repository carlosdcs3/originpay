<?php

namespace App\Gateway\Http;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Client\ConnectionException;
use App\Gateway\Exceptions\GatewayTimeoutException;
use App\Gateway\Exceptions\GatewayCommunicationException;
use App\Gateway\Exceptions\GatewayAuthenticationException;
use App\Gateway\Exceptions\GatewayValidationException;
use App\Gateway\Exceptions\GatewayRateLimitException;
use App\Gateway\Contracts\Data\GatewayResponse;
use Illuminate\Support\Str;

class GatewayHttpClient
{
    protected string $gatewayName;
    protected array $defaultHeaders = [];
    protected array $options = [];
    protected string $baseUrl = '';
    protected int $connectTimeout = 10;
    protected int $requestTimeout = 30;
    protected int $retryCount = 2;
    protected int $retryDelay = 1000;
    protected string $correlationId;

    protected ?string $lastRequestId = null;
    protected ?int $lastStatusCode = null;
    protected ?float $lastLatency = null;

    public function __construct(string $gatewayName)
    {
        $this->gatewayName = $gatewayName;
        $this->correlationId = Str::uuid()->toString();
    }

    public function withHeaders(array $headers): self
    {
        $this->defaultHeaders = array_merge($this->defaultHeaders, $headers);
        return $this;
    }

    public function withOptions(array $options): self
    {
        $this->options = array_merge($this->options, $options);
        return $this;
    }

    public function setBaseUrl(string $url): self
    {
        $this->baseUrl = $url;
        return $this;
    }

    public function withTimeouts(int $connect, int $request): self
    {
        $this->connectTimeout = $connect;
        $this->requestTimeout = $request;
        return $this;
    }

    public function withRetries(int $count, int $delayMs): self
    {
        $this->retryCount = $count;
        $this->retryDelay = $delayMs;
        return $this;
    }

    public function post(string $endpoint, array $payload = []): Response
    {
        $url = $this->baseUrl . $endpoint;
        return $this->send('post', $url, $payload);
    }

    public function get(string $endpoint, array $query = []): Response
    {
        $url = $this->baseUrl . $endpoint;
        return $this->send('get', $url, $query);
    }
    
    public function put(string $endpoint, array $payload = []): Response
    {
        $url = $this->baseUrl . $endpoint;
        return $this->send('put', $url, $payload);
    }

    protected function send(string $method, string $url, array $data = []): Response
    {
        $startTime = microtime(true);
        $this->lastRequestId = Str::uuid()->toString();

        try {
            $client = Http::withHeaders(array_merge($this->defaultHeaders, [
                'X-Correlation-ID' => $this->correlationId,
                'X-Request-ID' => $this->lastRequestId,
            ]))
            ->timeout($this->requestTimeout)
            ->connectTimeout($this->connectTimeout)
            ->retry($this->retryCount, $this->retryDelay);

            if (!empty($this->options)) {
                $client->withOptions($this->options);
            }

            $response = $client->{$method}($url, $data);
            
            $this->lastStatusCode = $response->status();
            $this->lastLatency = round((microtime(true) - $startTime) * 1000, 2);

            $this->logRequest($method, $url, $response);
            $this->handleResponseErrors($response);

            return $response;

        } catch (ConnectionException $e) {
            $this->lastLatency = round((microtime(true) - $startTime) * 1000, 2);
            $this->logError($method, $url, $e);
            throw new GatewayTimeoutException("Timeout error with {$this->gatewayName}: " . $e->getMessage());
        } catch (\Exception $e) {
            $this->lastLatency = round((microtime(true) - $startTime) * 1000, 2);
            $this->logError($method, $url, $e);
            if ($e instanceof GatewayAuthenticationException || $e instanceof GatewayValidationException || $e instanceof GatewayRateLimitException) {
                throw $e;
            }
            throw new GatewayCommunicationException("Communication error with {$this->gatewayName}: " . $e->getMessage());
        }
    }

    protected function handleResponseErrors(Response $response): void
    {
        $status = $response->status();

        if ($status === 429) {
            throw new GatewayRateLimitException("Rate limit exceeded for {$this->gatewayName}");
        }

        if ($status === 401 || $status === 403) {
            $data = $response->json() ?? [];
            $errorMsg = $data['error_description'] ?? $data['mensagem'] ?? 'Access denied';
            throw new GatewayAuthenticationException("Authentication failed for {$this->gatewayName}: {$errorMsg}");
        }
        
        if ($status === 422 || $status === 400) {
            $data = $response->json() ?? [];
            $errorMsg = $data['mensagem'] ?? $data['error_description'] ?? 'Validation Error';
            throw new GatewayValidationException("Validation failed for {$this->gatewayName}: {$errorMsg}");
        }
    }

    protected function logRequest(string $method, string $url, Response $response): void
    {
        Log::channel('gateway')->info("Gateway Request: {$this->gatewayName}", [
            'gateway' => $this->gatewayName,
            'operation' => strtoupper($method),
            'url' => $url,
            'correlation_id' => $this->correlationId,
            'request_id' => $this->lastRequestId,
            'latency_ms' => $this->lastLatency,
            'status_code' => $response->status(),
            'success' => $response->successful(),
            'response' => $this->sanitizeLogData($response->json() ?? [])
        ]);
    }

    protected function logError(string $method, string $url, \Exception $e): void
    {
        Log::channel('gateway')->error("Gateway Request Error: {$this->gatewayName}", [
            'gateway' => $this->gatewayName,
            'operation' => strtoupper($method),
            'url' => $url,
            'correlation_id' => $this->correlationId,
            'request_id' => $this->lastRequestId,
            'latency_ms' => $this->lastLatency,
            'error' => $e->getMessage()
        ]);
    }

    protected function sanitizeLogData(array $data): array
    {
        $sensitiveKeys = ['client_secret', 'access_token', 'authorization', 'cert', 'chave', 'password', 'client_id'];
        array_walk_recursive($data, function(&$value, $key) use ($sensitiveKeys) {
            if (in_array(strtolower((string) $key), $sensitiveKeys, true)) {
                $value = '********';
            }
        });
        return $data;
    }

    public function enrich(GatewayResponse $response): GatewayResponse
    {
        $response->correlationId = $this->correlationId;
        $response->requestId = $this->lastRequestId;
        $response->statusCode = $this->lastStatusCode;
        $response->latency = $this->lastLatency;
        $response->retryCount = $this->retryCount; 
        return $response;
    }
}
