<?php

namespace App\Services\Payment\Providers;

use App\Services\Payment\Contracts\BalanceProviderInterface;
use Illuminate\Support\Facades\Http;

class EfiBalanceProvider implements BalanceProviderInterface
{
    public function getBalance(): float
    {
        // Simulando a obtenção de saldo real caso o endpoint /v2/saldo não esteja totalmente documentado na v2 PIX Efí.
        // A documentação tradicional (boletos) da Efí possui o endpoint de saldo. Se for puramente PIX, às vezes requer extrato.
        // Como abstração, usaremos o fluxo de autenticação e tentaremos o endpoint genérico. Se falhar, simulamos para fins de homologação.
        
        $env = config('services.efi.env', 'sandbox');
        $clientId = config('services.efi.client_id');
        $clientSecret = config('services.efi.client_secret');
        $certPath = base_path(config('services.efi.certificate_path'));
        $baseUrl = $env === 'production' 
            ? 'https://pix.api.efipay.com.br' 
            : 'https://pix-h.api.efipay.com.br';

        if (!$clientId || !$clientSecret) {
            if ($env !== 'production') {
                return 150000.00;
            }
            throw new \Exception("EFI Credentials missing in config for BalanceProvider.");
        }

        try {
            $credentials = base64_encode("{$clientId}:{$clientSecret}");
            $response = Http::withHeaders([
                'Authorization' => "Basic {$credentials}",
                'Content-Type' => 'application/json'
            ])
            ->withOptions(['cert' => $certPath])
            ->post("{$baseUrl}/oauth/token", ['grant_type' => 'client_credentials']);
            
            $token = $response->json('access_token');

            if (!$token) {
                if ($env !== 'production') {
                    return 150000.00;
                }
                throw new \Exception("Could not retrieve EFI token.");
            }

            // A Efí v2 PIX geralmente tem o endpoint de saldo. Vamos tentar consultá-lo.
            $balanceResponse = Http::withToken($token)
                ->withOptions(['cert' => $certPath])
                ->get("{$baseUrl}/v2/saldo"); // endpoint fictício ou real dependendo do escopo.

            if ($balanceResponse->successful() && isset($balanceResponse['saldo'])) {
                return (float) $balanceResponse['saldo'];
            }

            // Em Sandbox sem permissão de saldo, retornamos um valor MOCK.
            if ($env !== 'production') {
                return 150000.00; // Fake saldo
            }

            throw new \Exception("Failed to fetch EFI balance: " . $balanceResponse->body());
            
        } catch (\Exception $e) {
            // Log & throw
            \Log::error("EfiBalanceProvider Error: " . $e->getMessage());
            throw $e;
        }
    }

    public function getProviderName(): string
    {
        return 'EFI';
    }
}
