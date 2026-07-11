<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\FinancialReconciliationJob;
use App\Jobs\FinancialExportJob;
use Illuminate\Support\Facades\Artisan;
use App\Models\User;

class OpsSoakTestCommand extends Command
{
    protected $signature = 'ops:soak-test {--duration=60} {--intensity=standard}';
    protected $description = 'Dispara uma carga massiva de Jobs reais (Reconciliação, Exportação) para testar Endurance da fila (Soak Test).';

    public function handle()
    {
        $duration = (int) $this->option('duration'); // in minutes
        $intensity = $this->option('intensity');

        $jobsPerMinute = $intensity === 'extreme' ? 500 : ($intensity === 'standard' ? 100 : 20);

        $this->info("Iniciando Soak Test ($intensity) por $duration minutos.");
        $this->info("Serão despachados ~$jobsPerMinute Jobs operacionais pesados por minuto.");

        $adminId = User::where('id', 1)->value('id') ?? 1;

        $endTime = now()->addMinutes($duration);

        while (now() < $endTime) {
            for ($i = 0; $i < $jobsPerMinute; $i++) {
                // Alterna os tipos de job reais para misturar consumo de banco vs consumo de processador
                if ($i % 2 === 0) {
                    Artisan::queue('ledger:reconcile'); // Simulando disparo de job
                } else {
                    FinancialExportJob::dispatch($adminId, ['start_date' => now()->subDays(30)->toDateString()]);
                }
            }

            $this->info(now()->toDateTimeString() . " - Bateria de $jobsPerMinute Jobs despachada para o Horizon. Descansando 60s...");
            sleep(60);
        }

        $this->info("Soak Test finalizado. Verifique a memória dos Workers no painel do Horizon e se houve Jobs na fila de Falha (DLQ).");
    }
}
