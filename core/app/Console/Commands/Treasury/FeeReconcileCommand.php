<?php

namespace App\Console\Commands\Treasury;

use Illuminate\Console\Command;
use App\Models\WithdrawalRequest;
use App\Models\LedgerEntry;
use App\Models\FinancialAnomaly;
use App\Enums\SystemWalletUUID;

class FeeReconcileCommand extends Command
{
    protected $signature = 'fee:reconcile';
    protected $description = 'Reconciles fee snapshot data against the ledger (Read-only)';

    public function handle()
    {
        $this->info('Starting Fee Reconciliation...');
        
        $withdrawals = WithdrawalRequest::where('status', 'COMPLETED')->get();
        $anomaliesDetected = 0;

        foreach ($withdrawals as $withdraw) {
            $metadata = $withdraw->metadata;
            if (!isset($metadata['fee_snapshot'])) {
                continue;
            }

            $snapshot = (object) $metadata['fee_snapshot'];
            $platformFee = $snapshot->platform_fee_amount ?? 0;
            $providerFee = $snapshot->provider_fee_amount ?? 0;

            // Find credits to SYSTEM_REVENUE
            $revenueCredit = LedgerEntry::where('transaction_id', $withdraw->transaction_id)
                ->where('direction', 'credit')
                ->whereHas('wallet', function($q) {
                    $q->where('uuid', SystemWalletUUID::SYSTEM_REVENUE->value);
                })
                ->sum('amount');

            // Find credits to GATEWAY_EFI_FEE_HOLDING
            $gatewayCredit = LedgerEntry::where('transaction_id', $withdraw->transaction_id)
                ->where('direction', 'credit')
                ->whereHas('wallet', function($q) {
                    $q->where('uuid', SystemWalletUUID::GATEWAY_EFI_FEE_HOLDING->value);
                })
                ->sum('amount');

            $mismatch = false;
            $msg = [];

            if (abs($revenueCredit - $platformFee) > 0.001) {
                $mismatch = true;
                $msg[] = "Platform fee mismatch: snapshot={$platformFee}, ledger={$revenueCredit}";
            }

            if (abs($gatewayCredit - $providerFee) > 0.001) {
                $mismatch = true;
                $msg[] = "Gateway fee mismatch: snapshot={$providerFee}, ledger={$gatewayCredit}";
            }

            if ($mismatch) {
                $anomaliesDetected++;
                $this->error("Mismatch on withdrawal {$withdraw->id}: " . implode(' | ', $msg));
                
                $fingerprint = "fee_reconcile_mismatch_{$withdraw->id}";
                
                if (!FinancialAnomaly::where('fingerprint', $fingerprint)->exists()) {
                    FinancialAnomaly::create([
                        'type' => 'fee_reconcile_mismatch',
                        'severity' => 'CRITICAL',
                        'entity_type' => 'withdrawal',
                        'entity_id' => $withdraw->id,
                        'fingerprint' => $fingerprint,
                        'description' => implode(' | ', $msg),
                        'metadata' => [
                            'snapshot' => $snapshot,
                            'ledger_revenue' => $revenueCredit,
                            'ledger_gateway' => $gatewayCredit
                        ],
                        'suggested_actions' => ['manual_ledger_correction', 'audit_fee_service'],
                        'detected_at' => now(),
                    ]);
                }
            }
        }

        $this->info("Fee reconciliation completed. Anomalies detected: {$anomaliesDetected}");
        return 0;
    }
}
