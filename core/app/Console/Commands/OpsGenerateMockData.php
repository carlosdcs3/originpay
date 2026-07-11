<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Charge;
use App\Models\PlatformIncident;
use App\Models\WalletTransaction;
use App\Models\User;
use App\Models\Gateway;
use App\Enums\ChargeStatus;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Redis;
use Carbon\Carbon;

class OpsGenerateMockData extends Command
{
    protected $signature = 'ops:generate-mock-data {--volume=1000 : Quantidade de charges a gerar}';
    protected $description = 'Gera dados fictícios (Homologação) para validar os Dashboards e Observabilidade visual.';

    public function handle()
    {
        if (app()->environment('production')) {
            $this->error("ALERTA: Este comando só pode ser rodado em ambiente de homologação/staging.");
            return;
        }

        $volume = (int) $this->option('volume');
        $this->info("Gerando $volume transações fictícias...");

        $users = User::factory()->count(5)->create();
        $gateways = Gateway::where('status', 1)->get();
        if($gateways->isEmpty()) {
            $gateways = Gateway::factory()->count(2)->create(['status' => 1]);
        }

        for ($i = 0; $i < $volume; $i++) {
            $status = $i % 5 === 0 ? ChargeStatus::PENDING : ChargeStatus::PAID;
            $gw = $gateways->random();
            $user = $users->random();

            $charge = Charge::create([
                'user_id' => $user->id,
                'gateway_id' => $gw->id,
                'amount' => rand(10, 5000),
                'status' => $status,
                'trx' => Str::random(12),
                'created_at' => Carbon::now()->subMinutes(rand(1, 1440)),
                'updated_at' => Carbon::now(),
            ]);

            if ($status === ChargeStatus::PAID) {
                // Mock Wallet Tx (Omitindo HMAC exato pro mock, apenas para contar volume)
                WalletTransaction::create([
                    'user_id' => $user->id,
                    'wallet_id' => 1,
                    'type' => 'charge',
                    'amount' => $charge->amount,
                    'balance_before' => 0,
                    'balance_after' => $charge->amount,
                    'description' => 'Mock Data',
                    'reference_type' => Charge::class,
                    'reference_id' => $charge->id,
                    'correlation_id' => $charge->id,
                    'idempotency_key' => 'mock_wh_' . $charge->id,
                    'integrity_hash' => 'mock_hash_xyz',
                ]);
            }
        }

        $this->info("Gerando Incidentes Fictícios...");
        PlatformIncident::create([
            'title' => '[MOCK] Queda de latência PSP',
            'severity' => 'warning',
            'status' => 'active',
            'started_at' => now()->subMinutes(15),
            'root_cause' => 'Teste de Stress',
            'created_by' => 1,
        ]);

        $this->info("Sujando o Health Score dos Gateways...");
        foreach($gateways as $gw) {
            Redis::set("gateway:health_score:{$gw->code}", rand(-60, 100));
        }

        $this->info("Mock finalizado! Abra o Admin Dashboard e a aba de Compliance para validar a interface.");
    }
}
