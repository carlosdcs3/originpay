<?php

namespace App\Console\Commands\Reconciliation;

use Illuminate\Console\Command;
use App\Models\Transaction;
use App\Models\FinancialAnomaly;
use Illuminate\Support\Facades\Http;
use App\Enums\ProviderType;
use App\Enums\TrxStatus;
use App\Services\Security\TenantBypass;

class ReconcileEfiCommand extends Command
{
    protected $signature = 'reconcile:efi {--days=1 : Number of days to look back}';
    protected $description = 'Reconciles local transactions with Efí gateway data. Strictly Read-Only.';

    public function handle()
    {
        $this->info("Starting EFI Reconciliation...");
        
        $days = (int) $this->option('days');
        $inicio = now()->subDays($days)->toRfc3339String();
        $fim = now()->toRfc3339String();

        $env = config('services.efi.env', 'sandbox');
        $clientId = config('services.efi.client_id');
        $clientSecret = config('services.efi.client_secret');
        $certPath = base_path(config('services.efi.certificate_path'));
        $baseUrl = $env === 'production' 
            ? 'https://pix.api.efipay.com.br' 
            : 'https://pix-h.api.efipay.com.br';

        if (!$clientId || !$clientSecret) {
            $this->error("EFI Credentials missing in config.");
            return 1;
        }

        // Get token
        try {
            $credentials = base64_encode("{$clientId}:{$clientSecret}");
            $response = Http::withHeaders([
                'Authorization' => "Basic {$credentials}",
                'Content-Type' => 'application/json'
            ])
            ->withOptions(['cert' => $certPath])
            ->post("{$baseUrl}/oauth/token", ['grant_type' => 'client_credentials']);
            
            $token = $response->json('access_token');
        } catch (\Exception $e) {
            $this->error("Failed to authenticate with EFI: " . $e->getMessage());
            return 1;
        }

        // Fetch transactions from EFI
        // Documentação Efí: GET /v2/cob?inicio=...&fim=...
        $page = 0;
        do {
            $cobResponse = Http::withToken($token)
                ->withOptions(['cert' => $certPath])
                ->get("{$baseUrl}/v2/cob", [
                    'inicio' => $inicio,
                    'fim' => $fim,
                    'paginacao.paginaAtual' => $page
                ]);

            if (!$cobResponse->successful()) {
                $this->error("Failed to fetch COBs: " . $cobResponse->body());
                return 1;
            }

            $data = $cobResponse->json();
            $cobs = $data['cobs'] ?? [];

            foreach ($cobs as $cob) {
                $txid = $cob['txid'];
                $status = $cob['status'];
                $amount = (float) $cob['valor']['original'];
                
                // Compare with Local Database
                $localTrx = TenantBypass::run(fn () => Transaction::where('trx_id', $txid)
                    ->orWhere('trx_reference', $txid)
                    ->first());

                if (!$localTrx) {
                    if ($status === 'CONCLUIDA') {
                        $this->registerAnomaly(
                            'efi_missing_local', 'HIGH', "EFI transaction paid but missing locally. TXID: {$txid}"
                        );
                    }
                    continue;
                }

                // Status Mismatch
                $localStatus = $localTrx->status->value ?? $localTrx->status;
                if ($status === 'CONCLUIDA' && $localStatus !== TrxStatus::COMPLETED->value) {
                    $this->registerAnomaly(
                        'efi_status_mismatch', 'CRITICAL', "EFI is CONCLUIDA but local is {$localStatus}. TXID: {$txid}"
                    );
                } elseif ($status !== 'CONCLUIDA' && $localStatus === TrxStatus::COMPLETED->value) {
                    $this->registerAnomaly(
                        'efi_status_mismatch_reverse', 'CRITICAL', "Local is COMPLETED but EFI is {$status}. TXID: {$txid}"
                    );
                }

                // Amount Mismatch
                if ($localTrx->amount != $amount) {
                    $this->registerAnomaly(
                        'efi_amount_mismatch', 'CRITICAL', "Amount mismatch for TXID: {$txid}. Local: {$localTrx->amount}, EFI: {$amount}"
                    );
                }
            }
            
            $totalPages = $data['parametros']['paginacao']['quantidadeDePaginas'] ?? 1;
            $page++;
        } while ($page < $totalPages);

        $this->info("EFI Reconciliation completed.");
        return 0;
    }

    protected function registerAnomaly($type, $severity, $description)
    {
        $this->warn("ANOMALY FOUND: {$description}");
        
        $fingerprint = md5("{$type}:" . date('Y-m-d')); // Simplistic fingerprint for this command

        FinancialAnomaly::updateOrCreate(
            ['fingerprint' => $fingerprint],
            [
                'type' => $type,
                'severity' => $severity,
                'entity_type' => 'reconciliation_efi',
                'entity_id' => '0',
                'description' => $description,
                'detected_at' => now(),
            ]
        );
    }
}
