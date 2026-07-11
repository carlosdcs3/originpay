<?php

namespace App\Jobs\Treasury;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\RollingReserve;
use App\Models\Wallet;
use App\Models\LedgerEntry;
use App\Models\FinancialAnomaly;
use Illuminate\Support\Facades\DB;
use App\Services\Security\TenantBypass;
use Exception;

class ReleaseRollingReserveJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle()
    {
        $dueReserves = RollingReserve::where('status', 'HELD')
            ->where('release_at', '<=', now())
            ->get();

        foreach ($dueReserves as $reserve) {
            try {
                DB::transaction(function () use ($reserve) {
                    $reserve = RollingReserve::where('id', $reserve->id)->lockForUpdate()->first();
                    
                    if ($reserve->status !== 'HELD') {
                        return; // idempotency
                    }

                    $wallet = TenantBypass::run(fn () => Wallet::where('id', $reserve->wallet_id)->lockForUpdate()->firstOrFail());

                    // Safety check
                    if ($wallet->rolling_reserve_balance < $reserve->amount) {
                        throw new Exception("Rolling reserve balance is less than amount to release.");
                    }

                    $wallet->rolling_reserve_balance -= $reserve->amount;
                    $wallet->available_balance += $reserve->amount;
                    $wallet->save();

                    $reserve->status = 'RELEASED';
                    $reserve->released_at = now();
                    $reserve->save();

                    $correlationId = \Illuminate\Support\Str::uuid()->toString();

                    // Débito da Reserva
                    LedgerEntry::create([
                        'transaction_id' => $reserve->transaction_id,
                        'wallet_id' => $wallet->id,
                        'direction' => 'debit',
                        'amount' => $reserve->amount,
                        'currency' => $wallet->currency->code ?? 'USD',
                        'balance_after' => $wallet->balance, // o balance em si não mudou, apenas os sub-saldos
                        'description' => 'Rolling Reserve Debit',
                        'metadata' => ['type' => 'ROLLING_RESERVE_RELEASE_DEBIT', 'reserve_id' => $reserve->id, 'correlation_id' => $correlationId],
                        'created_at' => now(),
                    ]);

                    // Crédito do Saldo Disponível
                    LedgerEntry::create([
                        'transaction_id' => $reserve->transaction_id,
                        'wallet_id' => $wallet->id,
                        'direction' => 'credit',
                        'amount' => $reserve->amount,
                        'currency' => $wallet->currency->code ?? 'USD',
                        'balance_after' => $wallet->balance,
                        'description' => 'Rolling Reserve Credit',
                        'metadata' => ['type' => 'ROLLING_RESERVE_RELEASE_CREDIT', 'reserve_id' => $reserve->id, 'correlation_id' => $correlationId],
                        'created_at' => now(),
                    ]);
                });
            } catch (Exception $e) {
                FinancialAnomaly::create([
                    'type' => 'rolling_reserve_release_failed',
                    'severity' => 'HIGH',
                    'entity_type' => 'rolling_reserve',
                    'entity_id' => $reserve->id,
                    'fingerprint' => "rolling_reserve_release_failed_{$reserve->id}",
                    'description' => "Failed to release rolling reserve: " . $e->getMessage(),
                    'detected_at' => now(),
                ]);
            }
        }
    }
}
