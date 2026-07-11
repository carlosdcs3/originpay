<?php

namespace App\Payment\Modern\Providers;

use App\Payment\Modern\DTO\DepositDTO;
use App\Payment\Modern\DTO\GatewayResponseDTO;
use App\Payment\Modern\DTO\GatewayTransactionDTO;
use App\Payment\Modern\DTO\RefundDTO;
use App\Payment\Modern\DTO\WebhookDTO;
use App\Payment\Modern\DTO\WithdrawDTO;
use App\Payment\Modern\ModernPaymentGatewayInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class EfiGateway implements ModernPaymentGatewayInterface
{
    protected string $env;
    protected string $clientId;
    protected string $clientSecret;
    protected string $certPath;
    protected string $pixKey;
    protected string $baseUrl;

    public function __construct()
    {
        $this->env = (string) (config('services.efi.env') ?: 'sandbox');
        $this->clientId = (string) (config('services.efi.client_id') ?: '');
        $this->clientSecret = (string) (config('services.efi.client_secret') ?: '');
        $this->certPath = base_path((string) (config('services.efi.certificate_path') ?: 'storage/app/private/efi/prod.pem'));
        $this->pixKey = (string) (config('services.efi.pix_key') ?: '');

        $this->baseUrl = $this->env === 'production'
            ? 'https://pix.api.efipay.com.br'
            : 'https://pix-h.api.efipay.com.br';
    }

    protected function getAccessToken(): string
    {
        $this->ensureCredentialsConfigured();

        return Cache::remember('efi_access_token', 3500, function () {
            $credentials = base64_encode("{$this->clientId}:{$this->clientSecret}");

            $response = Http::withHeaders([
                'Authorization' => "Basic {$credentials}",
                'Content-Type' => 'application/json',
            ])
                ->withOptions(['cert' => $this->certPath])
                ->post("{$this->baseUrl}/oauth/token", [
                    'grant_type' => 'client_credentials',
                ]);

            if ($response->successful()) {
                return $response->json('access_token');
            }

            throw new \Exception('Failed to get EFI Access Token: ' . $response->body());
        });
    }

    protected function client()
    {
        return Http::withToken($this->getAccessToken())
            ->withOptions(['cert' => $this->certPath]);
    }

    public function createPix(DepositDTO $dto): GatewayResponseDTO
    {
        $this->ensurePixConfigured();

        $response = $this->client()->post("{$this->baseUrl}/v2/cob", [
            'calendario' => ['expiracao' => 3600],
            'valor' => ['original' => number_format($dto->amount, 2, '.', '')],
            'chave' => $this->pixKey,
            'solicitacaoPagador' => "Deposit for {$dto->internalTrxId}",
        ]);

        if (! $response->successful()) {
            return new GatewayResponseDTO(false, null, null, null, $response->body());
        }

        $data = $response->json();
        $qrCode = $data['pixCopiaECola'] ?? '';
        $locId = $data['loc']['id'] ?? null;

        if ($locId) {
            $qrResponse = $this->client()->get("{$this->baseUrl}/v2/loc/{$locId}/qrcode");
            if ($qrResponse->successful()) {
                $qrData = $qrResponse->json();
                $qrCode = $qrData['imagemQrcode'] ?? ($qrData['qrcode'] ?? $qrCode);
            }
        }

        return new GatewayResponseDTO(
            isSuccess: true,
            providerTransactionId: $data['txid'] ?? null,
            qrCode: $qrCode,
            rawResponse: $data
        );
    }

    public function createDeposit(DepositDTO $dto): GatewayResponseDTO
    {
        return $this->createPix($dto);
    }

    public function withdraw(WithdrawDTO $dto): GatewayResponseDTO
    {
        $this->ensurePixConfigured();

        $response = $this->client()->post("{$this->baseUrl}/v2/gn/pix/envio", [
            'valor' => number_format($dto->amount, 2, '.', ''),
            'pagador' => ['chave' => $this->pixKey],
            'favorecido' => ['chave' => $dto->destinationAccount],
        ]);

        if (! $response->successful()) {
            return new GatewayResponseDTO(false, null, null, null, $response->body());
        }

        $data = $response->json();

        return new GatewayResponseDTO(
            isSuccess: true,
            providerTransactionId: $data['e2eId'] ?? ($data['idEnvio'] ?? null),
            rawResponse: $data
        );
    }

    public function createCheckout(DepositDTO $dto): GatewayResponseDTO
    {
        throw new \Exception('Not implemented for EFI Checkout yet.');
    }

    public function verifyWebhook(Request $request): bool
    {
        return true;
    }

    public function parseWebhook(Request $request): WebhookDTO
    {
        $payload = $request->all();

        if (! isset($payload['pix']) || ! is_array($payload['pix']) || empty($payload['pix'][0])) {
            throw new \Exception('Invalid EFI Webhook format');
        }

        $pix = $payload['pix'][0];

        return new WebhookDTO(
            providerTransactionId: $pix['txid'],
            externalReference: $pix['endToEndId'] ?? null,
            status: 'PAID',
            amount: (float) $pix['valor'],
            currency: 'BRL',
            rawPayload: $payload
        );
    }

    public function refund(RefundDTO $dto): GatewayResponseDTO
    {
        $this->ensureCredentialsConfigured();

        $refundId = $dto->metadata['refund_id'] ?? uniqid('refund_', true);
        $response = $this->client()->put(
            "{$this->baseUrl}/v2/cob/{$dto->providerTransactionId}/devolucao/{$refundId}",
            ['valor' => number_format($dto->amount, 2, '.', '')]
        );

        if (! $response->successful()) {
            return new GatewayResponseDTO(false, null, null, null, $response->body());
        }

        $data = $response->json();

        return new GatewayResponseDTO(
            isSuccess: true,
            providerTransactionId: $data['id'] ?? $refundId,
            rawResponse: $data
        );
    }

    public function getTransaction(string $providerTrxId): GatewayTransactionDTO
    {
        $this->ensureCredentialsConfigured();

        $response = $this->client()->get("{$this->baseUrl}/v2/cob/{$providerTrxId}");

        if (! $response->successful()) {
            return new GatewayTransactionDTO($providerTrxId, 'FAILED', 0, 'BRL');
        }

        $data = $response->json();
        $statusMap = [
            'CONCLUIDA' => 'PAID',
            'ATIVA' => 'PENDING',
            'REMOVIDA_PELO_USUARIO_RECEBEDOR' => 'FAILED',
            'REMOVIDA_PELO_PSP' => 'FAILED',
        ];

        return new GatewayTransactionDTO(
            providerTransactionId: $data['txid'] ?? $providerTrxId,
            status: $statusMap[$data['status'] ?? ''] ?? 'PENDING',
            amount: (float) ($data['valor']['original'] ?? 0),
            currency: 'BRL',
            rawResponse: $data
        );
    }

    public function healthCheck(): string
    {
        try {
            $this->getAccessToken();

            return 'CONNECTED';
        } catch (\Exception) {
            return 'OFFLINE';
        }
    }

    protected function ensureCredentialsConfigured(): void
    {
        if ($this->clientId === '' || $this->clientSecret === '') {
            throw new \RuntimeException('EFI credentials are not configured.');
        }
    }

    protected function ensurePixConfigured(): void
    {
        $this->ensureCredentialsConfigured();

        if ($this->pixKey === '') {
            throw new \RuntimeException('EFI PIX key is not configured.');
        }
    }
}
