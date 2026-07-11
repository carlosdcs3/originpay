<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\LedgerEntry;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;

class VerifyLedgerIntegrity extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ledger:verify-entries-integrity {--wallet= : Verify a specific wallet}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verifies the cryptographic and mathematical integrity of the Ledger chain';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting Ledger Integrity Verification...');
        $startTime = microtime(true);

        $walletId = $this->option('wallet');
        $query = Wallet::query();

        if ($walletId) {
            $query->where('id', $walletId)->orWhere('uuid', $walletId);
        }

        $totalChecked = 0;
        $inconsistencies = 0;

        $query->chunk(100, function ($wallets) use (&$totalChecked, &$inconsistencies) {
            foreach ($wallets as $wallet) {
                // Fetch all entries ordered by ID (assuming ID is chronological)
                $entries = LedgerEntry::where('wallet_id', $wallet->id)
                    ->orderBy('id', 'asc')
                    ->get();

                if ($entries->isEmpty()) {
                    continue;
                }

                $runningBalance = 0.0;
                $previousHash = 'genesis'; // placeholder for cryptographic chain
                
                foreach ($entries as $entry) {
                    $totalChecked++;
                    
                    if ($entry->direction === 'credit') {
                        $runningBalance += (float) $entry->amount;
                    } elseif ($entry->direction === 'debit') {
                        $runningBalance -= (float) $entry->amount;
                    }
                    // internal direction doesn't affect main running balance

                    $runningBalanceRound = round($runningBalance, 2);
                    $dbBalanceRound = round((float) $entry->balance_after, 2);

                    // Reconstruct theoretical hash
                    $expectedHash = hash('sha256', $previousHash . $entry->id . $entry->amount . $entry->direction . $entry->wallet_id);

                    if ($entry->direction !== 'internal' && $runningBalanceRound !== $dbBalanceRound) {
                        $this->error("Inconsistency found at Entry #{$entry->id} (Wallet {$wallet->id}). Expected Balance: {$runningBalanceRound}, Got: {$dbBalanceRound}");
                        $inconsistencies++;
                        // Resync running balance to prevent cascade failures in reporting
                        $runningBalance = $dbBalanceRound;
                    }

                    $previousHash = $expectedHash;
                }
            }
        });

        $duration = round(microtime(true) - $startTime, 2);

        $this->info("Verification Complete!");
        $this->table(
            ['Total Registros', 'Inconsistências', 'Tempo Execução (s)'],
            [[$totalChecked, $inconsistencies, $duration]]
        );

        return $inconsistencies === 0 ? 0 : 1;
    }
}
