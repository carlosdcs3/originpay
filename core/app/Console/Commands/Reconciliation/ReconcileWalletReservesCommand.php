<?php

namespace App\Console\Commands\Reconciliation;

use Illuminate\Console\Command;
use App\Models\Wallet;
use App\Models\FinancialAnomaly;
use App\Services\Security\TenantBypass;

class ReconcileWalletReservesCommand extends Command
{
    protected $signature = 'reconcile:wallet-reserves';
    protected $description = 'Strict Read-Only: Scans all wallets to ensure balance = available_balance + reserved_balance';

    public function handle()
    {
        $this->info("Scanning Wallet Reserves Integrity...");

        // Compare using BCMath or simply round to 8 decimals as defined in schema
        $wallets = TenantBypass::run(fn () => Wallet::all());
        $mismatches = [];

        foreach ($wallets as $wallet) {
            $expectedTotal = round($wallet->available_balance + $wallet->reserved_balance, 8);
            $actualTotal = round($wallet->balance, 8);

            if (abs($expectedTotal - $actualTotal) > 0.00000001) {
                $mismatches[] = [
                    'wallet_id' => $wallet->id,
                    'user_id' => $wallet->user_id,
                    'balance' => $wallet->balance,
                    'available_balance' => $wallet->available_balance,
                    'reserved_balance' => $wallet->reserved_balance,
                    'diff' => $actualTotal - $expectedTotal,
                ];

                $fingerprint = "wallet_reserve_mismatch:{$wallet->id}";
                $this->registerAnomaly($fingerprint, $wallet->id, $wallet->balance, $wallet->available_balance, $wallet->reserved_balance, $actualTotal - $expectedTotal);
            }
        }

        if (count($mismatches) > 0) {
            $this->error("Found " . count($mismatches) . " wallet(s) with reserve mismatches.");
            // Write to CSV
            $csvPath = storage_path('logs/reconcile-wallet-reserves-' . date('Y-m-d-His') . '.csv');
            $fp = fopen($csvPath, 'w');
            fputcsv($fp, ['Wallet ID', 'User ID', 'Total Balance', 'Available', 'Reserved', 'Difference']);
            foreach ($mismatches as $m) {
                fputcsv($fp, array_values($m));
            }
            fclose($fp);
            $this->info("Mismatch details saved to {$csvPath}");
        } else {
            $this->info("All wallets passed reserve integrity check.");
        }

        return count($mismatches) > 0 ? 1 : 0;
    }

    private function registerAnomaly($fingerprint, $walletId, $balance, $available, $reserved, $diff)
    {
        $anomaly = FinancialAnomaly::where('fingerprint', $fingerprint)->whereNull('resolved_at')->first();

        if ($anomaly) {
            $anomaly->detected_at = now();
            $anomaly->save();
        } else {
            FinancialAnomaly::create([
                'type' => 'wallet_reserve_mismatch',
                'severity' => 'CRITICAL',
                'entity_type' => 'wallet',
                'entity_id' => $walletId,
                'fingerprint' => $fingerprint,
                'description' => "Wallet {$walletId} fails balance equation. balance != available + reserved",
                'metadata' => [
                    'balance' => $balance,
                    'available' => $available,
                    'reserved' => $reserved,
                    'diff' => $diff
                ],
                'suggested_actions' => ['investigate_ledger', 'rebuild_wallet_balance'],
                'detected_at' => now(),
            ]);
        }
    }
}
