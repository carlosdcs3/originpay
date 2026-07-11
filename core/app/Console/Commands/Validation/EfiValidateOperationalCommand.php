<?php

namespace App\Console\Commands\Validation;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;

class EfiValidateOperationalCommand extends Command
{
    protected $signature = 'efi:validate-operational';
    protected $description = 'Executes a comprehensive health and operational check for EFI Integration';

    public function handle()
    {
        $this->info("Starting EFI Operational Validation Checklist...");

        $checks = [
            'Env Config (EFI_CLIENT_ID)' => config('services.efi.client_id') !== null,
            'Env Config (EFI_PIX_KEY)' => config('services.efi.pix_key') !== null,
            'mTLS Certificate Exists' => file_exists(base_path(config('services.efi.certificate_path'))),
        ];

        // Circuit Breaker Check
        $cbStatus = Redis::get('emergency_circuit_breaker:withdraw');
        $checks['Withdraw Kill Switch (Off)'] = empty($cbStatus) || $cbStatus === 'false';

        // Horizon Check
        try {
            $horizonStatus = \Laravel\Horizon\Contracts\MasterSupervisorRepository::class;
            $masters = app($horizonStatus)->all();
            $checks['Horizon Running'] = count($masters) > 0;
        } catch (\Exception $e) {
            $checks['Horizon Running'] = false;
        }

        // Redis Check
        try {
            Redis::ping();
            $checks['Redis Online'] = true;
        } catch (\Exception $e) {
            $checks['Redis Online'] = false;
        }

        // DLQ Check
        try {
            $failedJobs = \Illuminate\Support\Facades\DB::table('failed_jobs')->count();
            $checks['DLQ Empty'] = $failedJobs === 0;
        } catch (\Exception $e) {
            $checks['DLQ Empty'] = false;
        }

        $allPassed = true;
        foreach ($checks as $name => $passed) {
            if ($passed) {
                $this->info("[PASS] {$name}");
            } else {
                $this->error("[FAIL] {$name}");
                $allPassed = false;
            }
        }

        if ($allPassed) {
            $this->info("\nAPROVADO PARA CERTIFICAÇÃO OPERACIONAL");
        } else {
            $this->error("\nBLOQUEADO POR DIVERGÊNCIAS");
            return 1;
        }

        return 0;
    }
}
