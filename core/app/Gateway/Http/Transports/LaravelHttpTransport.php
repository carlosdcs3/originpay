<?php

namespace App\Gateway\Http\Transports;

use Illuminate\Support\Facades\Http;
use App\Exceptions\Gateway\GatewayCommunicationException;
use App\Exceptions\Gateway\GatewayTimeoutException;
use Illuminate\Http\Client\ConnectionException;
use Exception;

class LaravelHttpTransport implements GatewayTransportInterface
{
    public function request(string $method, string $url, array $headers = [], array $body = [], int $timeout = 30, array $options = []): array
    {
        try {
            $request = Http::withHeaders($headers)
                ->timeout($timeout)
                ->withOptions($options);

            $response = match (strtolower($method)) {
                'get' => $request->get($url, $body),
                'post' => $request->post($url, $body),
                'put' => $request->put($url, $body),
                'delete' => $request->delete($url, $body),
                default => throw new Exception("HTTP Method not supported")
            };

            return [
                'status' => $response->status(),
                'headers' => $response->headers(),
                'body' => $response->body(),
                'successful' => $response->successful(),
                'serverError' => $response->serverError()
            ];

        } catch (ConnectionException $e) {
            throw new GatewayTimeoutException("Timeout or Connection Refused in LaravelTransport: " . $e->getMessage());
        } catch (Exception $e) {
            throw new GatewayCommunicationException($e->getMessage(), 0);
        }
    }
}
