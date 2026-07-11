<?php

namespace App\Gateway;

use App\Models\Charge;
use App\Models\PaymentGateway;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class EfiGatewayAdapter implements GatewayAdapter
{
    protected PaymentGateway $gatewayModel;
    protected array $credentials;

    public function __construct(PaymentGateway $gatewayModel, array $credentials)
    {
        $this->gatewayModel = $gatewayModel;
        $this->credentials = $credentials;
    }

    public function getIdentifier(): string
    {
        return $this->gatewayModel->code;
    }

    public function supportsPix(): bool { return true; }
    public function supportsCard(): bool { return false; }
    public function supportsBoleto(): bool { return true; }
    public function supportsSplit(): bool { return true; }
    public function supportsWithdraw(): bool { return false; }
    public function supportsRefund(): bool { return true; }
    public function supportsWebhookValidation(): bool { return true; }

    protected function getCertPath(): string
    {
        $certPathName = $this->credentials['cert_path'] ?? '';
        if (empty($certPathName)) {
            throw new Exception("Certificado Efí não configurado no painel Admin.");
        }

        // Bloquear certificados que não sejam .pem (risco de segurança e complexidade)
        if (!Str::endsWith($certPathName, '.pem')) {
            throw new Exception("O certificado Efí deve ser obrigatoriamente no formato .pem para produção. Converta o certificado Efí para .pem antes de ativar o gateway.");
        }

        $fullPath = storage_path('app/private/efi_certs/' . $certPathName);
        
        if (!file_exists($fullPath)) {
            throw new Exception("Certificado Efí não encontrado no caminho interno (storage). Faça o upload novamente.");
        }

        if (!is_readable($fullPath)) {
            throw new Exception("Certificado Efí sem permissão de leitura pelo sistema.");
        }

        return $fullPath;
    }

    protected function getBaseUrl(): string
    {
        return $this->gatewayModel->is_sandbox 
            ? 'https://pix-h.api.efipay.com.br' 
            : 'https://pix.api.efipay.com.br';
    }

    protected function getBoletoBaseUrl(): string
    {
        if (!empty($this->credentials['boleto_base_url'])) {
            return rtrim((string) $this->credentials['boleto_base_url'], '/');
        }

        return $this->gatewayModel->is_sandbox
            ? 'https://cobrancas-h.api.efipay.com.br'
            : 'https://cobrancas.api.efipay.com.br';
    }

    protected function getAccessToken(): string
    {
        $clientId = $this->credentials['client_id'] ?? '';
        $clientSecret = $this->credentials['client_secret'] ?? '';

        if (empty($clientId) || empty($clientSecret)) {
            throw new Exception("Credenciais (Client ID / Secret) Efí não configuradas no Admin.");
        }

        $cacheKey = 'efi_access_token_' . $this->gatewayModel->id;

        return cache()->remember($cacheKey, 3000, function () use ($clientId, $clientSecret) {
            $response = Http::timeout(5)->connectTimeout(2)
                ->retry(2, 100)
                ->withOptions(['cert' => $this->getCertPath()])
                ->withBasicAuth($clientId, $clientSecret)
                ->post($this->getBaseUrl() . '/oauth/token', [
                    'grant_type' => 'client_credentials'
                ]);

            if ($response->failed()) {
                throw new Exception("Falha na autenticação OAuth Efí: " . $response->json('error_description', $response->body()));
            }

            return $response->json('access_token');
        });
    }

    public function healthCheck(): GatewayHealth
    {
        $cacheKey = 'efi_health_check_' . $this->gatewayModel->id;
        
        return cache()->remember($cacheKey, 60, function () {
            $startTime = microtime(true);
            try {
                // We'll just fetch a token to check if it's online
                $this->getAccessToken();
                $isOnline = true;
                $latencyMs = (int) round((microtime(true) - $startTime) * 1000);
            } catch (\Exception $e) {
                $isOnline = false;
                $latencyMs = null;
            }
            
            return new GatewayHealth(
                $isOnline,
                $latencyMs,
                $this->gatewayModel->is_sandbox ? 'sandbox' : 'production'
            );
        });
    }

    public function createCharge(Charge $charge): void
    {
        if (($charge->payment_method->value ?? $charge->payment_method) === 'boleto') {
            $this->createBoleto($charge);
            return;
        }

        $token = $this->getAccessToken();
        
        $body = [
            'calendario' => [
                'expiracao' => 86400 // 1 dia
            ],
            'valor' => [
                'original' => number_format($charge->amount, 2, '.', '')
            ],
            'chave' => $this->credentials['pix_key'] ?? '', 
            'solicitacaoPagador' => substr($charge->description ?? 'Cobrança', 0, 140)
        ];

        // Validar chave pix antes
        if (empty($body['chave'])) {
            throw new Exception("Chave PIX da Efí não configurada no Admin.");
        }

        $response = Http::timeout(10)->connectTimeout(5)
            ->retry(3, 100, function ($exception, $request) {
                return $exception instanceof \Illuminate\Http\Client\ConnectionException ||
                       ($exception instanceof \Illuminate\Http\Client\RequestException && $exception->response->serverError());
            })
            ->withOptions(['cert' => $this->getCertPath()])
            ->withToken($token)
            ->withHeaders(['X-Correlation-ID' => $charge->correlation_id])
            ->post($this->getBaseUrl() . '/v2/cob', $body);

        if ($response->failed()) {
            throw new Exception("Erro ao criar cobrança PIX na Efí: " . $response->body());
        }

        $data = $response->json();
        
        $txid = $data['txid'];
        $locId = $data['loc']['id'] ?? null;
        
        $charge->gateway_charge_id = $txid;
        
        $metadata = $charge->metadata ?? [];
        $metadata['txid'] = $txid;
        if ($locId) {
            $metadata['loc_id'] = $locId;
        }
        
        if ($locId) {
            // Gerar QR Code via API Efí com o ID do location
            $qrResponse = Http::timeout(5)->connectTimeout(2)
                ->retry(2, 100)
                ->withOptions(['cert' => $this->getCertPath()])
                ->withToken($token)
                ->get($this->getBaseUrl() . '/v2/loc/' . $locId . '/qrcode');
                
            if ($qrResponse->successful()) {
                $qrData = $qrResponse->json();
                $charge->qr_code = $qrData['imagemQrcode'] ?? null;
                $charge->pix_copy_paste = $qrData['qrcode'] ?? null;
            }
        }
        
        $charge->metadata = $metadata;
        // Mock fallback link if UI wants to redirect
        $charge->payment_link = url("/mock-pay/{$charge->gateway_charge_id}"); 
    }

    public function createBoleto(Charge $charge): void
    {
        $token = $this->getAccessToken();
        $endpoint = $this->credentials['boleto_endpoint'] ?? '/v1/charge/one-step';
        $expiresAt = $charge->expires_at ?: now()->addDays((int) ($this->credentials['boleto_due_days'] ?? 3));

        $body = [
            'items' => [[
                'name' => substr($charge->description ?: 'Boleto OriginPay', 0, 255),
                'amount' => 1,
                'value' => (int) round(((float) $charge->amount) * 100),
            ]],
            'payment' => [
                'banking_billet' => [
                    'expire_at' => $expiresAt->format('Y-m-d'),
                    'customer' => [
                        'name' => $charge->customer_name ?: 'Cliente OriginPay',
                        'email' => $charge->customer_email ?: null,
                        'cpf' => $this->onlyDigits($charge->customer_document),
                    ],
                    'message' => substr($charge->description ?: 'Cobranca OriginPay', 0, 255),
                ],
            ],
            'metadata' => [
                'custom_id' => $charge->uuid,
                'notification_url' => url('/api/webhooks/gateway/efi'),
            ],
        ];

        if (strlen((string) $body['payment']['banking_billet']['customer']['cpf']) > 11) {
            $body['payment']['banking_billet']['customer']['juridical_person'] = [
                'corporate_name' => $charge->customer_name ?: 'Cliente OriginPay',
                'cnpj' => $body['payment']['banking_billet']['customer']['cpf'],
            ];
            unset($body['payment']['banking_billet']['customer']['cpf']);
        }

        $response = Http::timeout(10)->connectTimeout(5)
            ->retry(3, 100, function ($exception) {
                return $exception instanceof \Illuminate\Http\Client\ConnectionException ||
                       ($exception instanceof \Illuminate\Http\Client\RequestException && $exception->response->serverError());
            })
            ->withOptions(['cert' => $this->getCertPath()])
            ->withToken($token)
            ->withHeaders(['X-Correlation-ID' => $charge->correlation_id])
            ->post($this->getBoletoBaseUrl() . $endpoint, $body);

        if ($response->failed()) {
            throw new Exception("Erro ao criar boleto na Efi: " . $response->body());
        }

        $this->fillBoletoFields($charge, $response->json(), $expiresAt);
    }

    public function refreshBoleto(Charge $charge): void
    {
        $token = $this->getAccessToken();
        $reference = $charge->gateway_reference ?: $charge->gateway_charge_id;

        if (!$reference) {
            throw new Exception('Boleto sem referencia de gateway para segunda via.');
        }

        $endpoint = str_replace('{reference}', $reference, $this->credentials['boleto_refresh_endpoint'] ?? '/v1/charge/{reference}/billet');

        $response = Http::timeout(10)->connectTimeout(5)
            ->retry(3, 100)
            ->withOptions(['cert' => $this->getCertPath()])
            ->withToken($token)
            ->withHeaders(['X-Correlation-ID' => $charge->correlation_id])
            ->get($this->getBoletoBaseUrl() . $endpoint);

        if ($response->failed()) {
            throw new Exception("Erro ao emitir segunda via do boleto na Efi: " . $response->body());
        }

        $this->fillBoletoFields($charge, $response->json(), $charge->expires_at ?: now()->addDays(3));
    }

    private function fillBoletoFields(Charge $charge, array $data, \Carbon\CarbonInterface $expiresAt): void
    {
        $payload = $data['data'] ?? $data;
        $reference = $payload['charge_id']
            ?? $payload['id']
            ?? $payload['billet_id']
            ?? $payload['barcode']
            ?? $charge->gateway_charge_id;

        $charge->gateway_charge_id = (string) $reference;
        $charge->gateway_reference = (string) $reference;
        $charge->boleto_url = $payload['link']
            ?? $payload['billet_link']
            ?? $payload['url']
            ?? $payload['charge_url']
            ?? null;
        $charge->boleto_pdf_url = $payload['pdf']
            ?? $payload['pdf_url']
            ?? $payload['billet_pdf']
            ?? $charge->boleto_url;
        $charge->barcode = $payload['barcode'] ?? $payload['bar_code'] ?? null;
        $charge->digitable_line = $payload['digitable_line']
            ?? $payload['linha_digitavel']
            ?? $payload['line']
            ?? null;
        $charge->payment_link = $charge->boleto_url ?: $charge->payment_link;
        $charge->expires_at = $expiresAt;

        $metadata = $charge->metadata ?? [];
        $metadata['efi_boleto'] = $this->sanitizePayload($payload);
        $charge->metadata = $metadata;
    }

    public function cancelCharge(Charge $charge): void
    {
        // Cancelamento Efí (Not widely supported for pix, but possible via PATCH /v2/cob/{txid})
        throw new Exception("Cancelamento de PIX não implementado.");
    }

    public function getCharge(string $gatewayChargeId): array
    {
        $token = $this->getAccessToken();
        
        $response = Http::timeout(10)->connectTimeout(5)
            ->retry(3, 100, function ($exception) {
                return $exception instanceof \Illuminate\Http\Client\ConnectionException ||
                       ($exception instanceof \Illuminate\Http\Client\RequestException && $exception->response->serverError());
            })
            ->withOptions(['cert' => $this->getCertPath()])
            ->withToken($token)
            ->get($this->getBaseUrl() . '/v2/cob/' . $gatewayChargeId);
            
        if ($response->failed()) {
            throw new Exception("Falha ao consultar cobrança na Efí: " . $response->body());
        }
        
        $data = $response->json();
        
        return [
            'id' => $gatewayChargeId,
            'status' => $data['status'] === 'CONCLUIDA' ? 'paid' : 'pending',
            'raw' => $data
        ];
    }

    public function refund(Charge $charge): void
    {
        $token = $this->getAccessToken();
        $txid = $charge->gateway_charge_id;
        $amount = number_format($charge->amount, 2, '.', '');
        
        $response = Http::timeout(10)->connectTimeout(5)
            ->retry(3, 100)
            ->withOptions(['cert' => $this->getCertPath()])
            ->withToken($token)
            ->withHeaders(['X-Correlation-ID' => $charge->correlation_id])
            ->put($this->getBaseUrl() . "/v2/cob/{$txid}/devolucao/" . Str::random(20), [
                'valor' => $amount
            ]);
            
        if ($response->failed()) {
            throw new Exception("Falha ao solicitar estorno na Efí: " . $response->body());
        }
    }

    public function createWithdrawal(float $amount, string $pixKey): array
    {
        throw new Exception("Saque automático ainda não implementado na Efí.");
    }

    public function handleWebhook(\Illuminate\Http\Request $request): NormalizedEvent
    {
        if ($request->has('boleto') || $request->has('billet') || $request->has('charge')) {
            return $this->handleBoletoWebhook($request);
        }

        $pixArray = $request->input('pix');
        
        if (empty($pixArray) || !is_array($pixArray)) {
            throw new Exception("Payload Efí inválido.");
        }

        // Webhook Efí envia um array de transações pix recebidas
        $pixData = $pixArray[0] ?? [];
        $txid = $pixData['txid'] ?? 'unknown';
        $endToEndId = $pixData['endToEndId'] ?? null;

        // Limpeza de campos sensíveis para a rawPayload do NormalizedEvent (ex: remover se tiver algo que n queremos persistir)
        $cleanPayload = $this->sanitizePayload($request->all());

        // Na Efí, webhook em '/pix' indica pagamento concluído.
        $status = \App\Enums\ChargeStatus::PAID;

        return new NormalizedEvent($txid, $status, $cleanPayload, $endToEndId);
    }

    private function handleBoletoWebhook(\Illuminate\Http\Request $request): NormalizedEvent
    {
        $payload = $request->input('boleto')
            ?? $request->input('billet')
            ?? $request->input('charge')
            ?? [];

        if (isset($payload[0]) && is_array($payload[0])) {
            $payload = $payload[0];
        }

        if (!is_array($payload) || $payload === []) {
            throw new Exception("Payload Efi de boleto invalido.");
        }

        $reference = $payload['charge_id']
            ?? $payload['id']
            ?? $payload['gateway_charge_id']
            ?? $payload['custom_id']
            ?? null;

        if (!$reference) {
            throw new Exception("Payload Efi de boleto sem referencia.");
        }

        $statusValue = strtolower((string) ($payload['status'] ?? $payload['event'] ?? 'paid'));
        $status = match (true) {
            str_contains($statusValue, 'paid'),
            str_contains($statusValue, 'settled'),
            str_contains($statusValue, 'confirmed') => \App\Enums\ChargeStatus::PAID,
            str_contains($statusValue, 'expired'),
            str_contains($statusValue, 'venc') => \App\Enums\ChargeStatus::EXPIRED,
            str_contains($statusValue, 'cancel') => \App\Enums\ChargeStatus::CANCELLED,
            default => \App\Enums\ChargeStatus::WAITING_PAYMENT,
        };

        return new NormalizedEvent(
            (string) $reference,
            $status,
            $this->sanitizePayload($request->all()),
            (string) ($payload['event_id'] ?? $payload['id'] ?? $reference)
        );
    }

    private function onlyDigits(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $value);

        return $digits !== '' ? $digits : null;
    }
    
    /**
     * Helper para limpar payloads de logs antes de serem persistidos
     */
    protected function sanitizePayload(array $payload): array
    {
        // Embora o webhook não tenha auth ou credenciais, podemos usar este helper para outras áreas.
        $hiddenKeys = ['client_secret', 'client_id', 'Authorization', 'cert', 'cert_path', 'password'];
        
        array_walk_recursive($payload, function(&$value, $key) use ($hiddenKeys) {
            if (in_array($key, $hiddenKeys, true)) {
                $value = '********';
            }
        });
        
        return $payload;
    }
}
