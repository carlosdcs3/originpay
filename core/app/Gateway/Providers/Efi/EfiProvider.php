<?php

namespace App\Gateway\Providers\Efi;

use App\Gateway\Contracts\AbstractGatewayProvider;
use App\Gateway\Contracts\Enums\GatewayOperation;
use App\Gateway\Contracts\Data\GatewayResponse;
use App\Gateway\Contracts\Data\GatewayHealthData;
use App\Gateway\Exceptions\GatewayConfigurationException;
use App\Gateway\Exceptions\GatewayAuthenticationException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Exception;

class EfiProvider extends AbstractGatewayProvider
{
    public function getIdentifier(): string
    {
        return 'efi';
    }

    public function sendRequest(GatewayOperation $operation, array $payload): GatewayResponse
    {
        return match ($operation) {
            GatewayOperation::CHARGE_PIX => $this->createPixCharge($payload),
            GatewayOperation::CHECK_STATUS => $this->getPixCharge($payload['txid'] ?? ''),
            GatewayOperation::REFUND => $this->refundPixCharge($payload),
            default => GatewayResponse::error('OPERATION_NOT_SUPPORTED', "Operation {$operation->value} not supported.", null)
        };
    }

    public function checkHealth(): GatewayHealthData
    {
        $startTime = microtime(true);
        $cacheKeyBase = "efi:" . ($this->credentials->sandbox ? 'sandbox' : 'production') . ":" . md5($this->credentials->clientId) . ":oauth";
        
        try {
            $this->getAccessToken();
            $latencyMs = (int) round((microtime(true) - $startTime) * 1000);
            return GatewayHealthData::up($latencyMs, [
                'authenticated' => true,
                'certificate_valid' => true,
                'environment' => $this->credentials->sandbox ? 'sandbox' : 'production',
                'token_expires_at' => Cache::get("{$cacheKeyBase}:token_expires_at"),
                'last_success' => Cache::get("{$cacheKeyBase}:last_success"),
            ]);
        } catch (Exception $e) {
            $latencyMs = (int) round((microtime(true) - $startTime) * 1000);
            return GatewayHealthData::down($latencyMs, [
                'authenticated' => false,
                'certificate_valid' => false,
                'environment' => $this->credentials->sandbox ? 'sandbox' : 'production',
                'last_failure' => now()->toIso8601String(),
            ]);
        }
    }

    protected function createPixCharge(array $payload): GatewayResponse
    {
        $token = $this->getAccessToken();
        $pixKey = $this->credentials->pixKey;
        $providedTxid = $payload['txid'] ?? null;

        if (empty($pixKey)) {
            throw new GatewayConfigurationException("Efi PIX key not configured.");
        }

        $body = [
            'calendario' => [
                'expiracao' => (int) ($payload['expiration'] ?? 86400)
            ],
            'valor' => [
                'original' => number_format($payload['amount'], 2, '.', '')
            ],
            'chave' => $pixKey, 
            'solicitacaoPagador' => mb_substr($payload['description'] ?? 'Charge', 0, 140)
        ];

        $client = $this->createEfiClient($token);
        
        if ($providedTxid) {
            $response = $client->put("/v2/cob/{$providedTxid}", $body);
        } else {
            $response = $client->post('/v2/cob', $body);
        }
        
        $data = $response->json();
        
        if (!$response->successful()) {
            $errResponse = GatewayResponse::error(
                $data['nome'] ?? 'EFI_ERROR',
                $data['mensagem'] ?? 'Unknown error creating PIX.',
                $data
            );
            return $client->enrich($errResponse);
        }

        $txid = $data['txid'] ?? null;
        $locId = $data['loc']['id'] ?? null;
        
        $qrCodeData = null;
        if ($locId) {
            $qrResponse = $client->get("/v2/loc/{$locId}/qrcode");
            if ($qrResponse->successful()) {
                $qrCodeData = $qrResponse->json();
            }
        }

        $successResponse = GatewayResponse::success(
            gatewayReference: $txid,
            status: 'PENDING',
            amount: $payload['amount'],
            rawResponse: $data,
            providerMetadata: [],
            txid: $txid,
            locationId: $locId,
            pixCopyPaste: $qrCodeData['qrcode'] ?? null,
            qrCodeImage: $qrCodeData['imagemQrcode'] ?? null
        );

        return $client->enrich($successResponse);
    }

    protected function getPixCharge(string $txid): GatewayResponse
    {
        if (empty($txid)) {
            throw new GatewayConfigurationException("TXID not provided for query.");
        }

        $token = $this->getAccessToken();
        $client = $this->createEfiClient($token);
        
        $response = $client->get("/v2/cob/{$txid}");
        $data = $response->json();
        
        if (!$response->successful()) {
            $errResponse = GatewayResponse::error(
                $data['nome'] ?? 'EFI_ERROR',
                $data['mensagem'] ?? 'Unknown error querying PIX.',
                $data
            );
            return $client->enrich($errResponse);
        }

        $successResponse = GatewayResponse::success(
            gatewayReference: $txid,
            status: $data['status'] === 'CONCLUIDA' ? 'PAID' : 'PENDING',
            amount: isset($data['valor']['original']) ? (float) $data['valor']['original'] : null,
            rawResponse: $data,
            providerMetadata: [],
            txid: $txid
        );
        return $client->enrich($successResponse);
    }

    protected function refundPixCharge(array $payload): GatewayResponse
    {
        $txid = $payload['txid'] ?? '';
        $amount = $payload['amount'] ?? 0;

        if (empty($txid)) {
            throw new GatewayConfigurationException("TXID not provided for refund.");
        }

        $token = $this->getAccessToken();
        $client = $this->createEfiClient($token);
        
        $idAleatorio = Str::random(20);
        
        $response = $client->put("/v2/cob/{$txid}/devolucao/{$idAleatorio}", [
            'valor' => number_format($amount, 2, '.', '')
        ]);
        $data = $response->json();
        
        if (!$response->successful()) {
            $errResponse = GatewayResponse::error(
                $data['nome'] ?? 'EFI_ERROR',
                $data['mensagem'] ?? 'Error refunding PIX.',
                $data
            );
            return $client->enrich($errResponse);
        }

        $successResponse = GatewayResponse::success(
            gatewayReference: $txid,
            status: 'REFUND_REQUESTED',
            amount: $amount,
            rawResponse: $data,
            providerMetadata: ['refund_id' => $data['id'] ?? null],
            txid: $txid
        );
        return $client->enrich($successResponse);
    }

    protected function getBaseUrl(): string
    {
        if ($this->credentials->baseUrl) {
            return $this->credentials->baseUrl;
        }
        return $this->credentials->sandbox 
            ? 'https://pix-h.api.efipay.com.br' 
            : 'https://pix.api.efipay.com.br';
    }

    protected function getCertPath(): string
    {
        $certPathName = $this->credentials->certificate;
        if (empty($certPathName)) {
            throw new GatewayConfigurationException("Efi certificate not configured.");
        }

        if (!Str::endsWith($certPathName, '.pem')) {
            throw new GatewayConfigurationException("Efi certificate must be .pem format.");
        }

        $fullPath = storage_path('app/private/efi_certs/' . $certPathName);
        
        if (!file_exists($fullPath)) {
            throw new GatewayConfigurationException("Efi certificate not found: {$certPathName}");
        }

        if (!is_readable($fullPath)) {
            throw new GatewayConfigurationException("Efi certificate not readable: {$certPathName}");
        }

        if (filesize($fullPath) === 0) {
            throw new GatewayConfigurationException("Efi certificate file is empty: {$certPathName}");
        }

        return $fullPath;
    }

    protected function getAccessToken(): string
    {
        $driver = new EfiOAuthDriver(
            $this->credentials,
            $this->httpClient(),
            $this->getCertPath(),
            $this->getBaseUrl()
        );

        return $driver->getAccessToken();
    }

    protected function createEfiClient(string $token)
    {
        $client = clone $this->httpClient();
        
        return $client->withHeaders([
            'Authorization' => "Bearer {$token}"
        ])->withOptions([
            'cert' => $this->getCertPath()
        ])->setBaseUrl($this->getBaseUrl());
    }
}
