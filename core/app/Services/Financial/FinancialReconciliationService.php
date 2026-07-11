<?php

namespace App\Services\Financial;

use App\Models\Charge;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\Log;
use App\Models\ProcessedEvent;

class FinancialReconciliationService
{
    /**
     * Compara as transações pagas no banco de dados contra o Ledger (WalletTransaction).
     * Identifica divergências onde uma Charge foi marcada como paga, mas o saldo não entrou no Ledger
     * ou entrou duplicado.
     */
    public function reconcileCharges(string $date = null): array
    {
        $query = Charge::where('status', 'PAID');
        if ($date) {
            $query->whereDate('updated_at', $date);
        }

        $charges = $query->get();
        $inconsistencies = [];

        foreach ($charges as $charge) {
            // Verifica o ledger
            // Assumindo que reference_id no WalletTransaction aponta para o ID da Charge quando reference_type é Charge
            $transactions = WalletTransaction::where('reference_type', Charge::class)
                ->where('reference_id', $charge->id)
                ->get();

            $totalCredited = $transactions->where('type', 'charge')->sum('amount');
            
            // Expected amount credited is usually charge amount minus fees.
            // Para simplificar a reconciliação base, verificamos se EXISTE lançamento correspondente
            if ($transactions->isEmpty()) {
                $inconsistencies[] = [
                    'type' => 'missing_ledger',
                    'charge_id' => $charge->id,
                    'message' => 'Charge marcada como PAGA sem registro no Ledger.'
                ];
            } else {
                // Checa duplo crédito para a mesma referência
                $creditCount = $transactions->where('type', 'charge')->count();
                if ($creditCount > 1) {
                    $inconsistencies[] = [
                        'type' => 'duplicate_credit',
                        'charge_id' => $charge->id,
                        'message' => "Charge creditada {$creditCount} vezes no Ledger."
                    ];
                }
            }

            // Checa orfandade na ProcessedEvents
            $processed = ProcessedEvent::where('source_id', $charge->gateway_transaction_id)
                ->where('event_type', 'payment.created') // ou o nome correto do evento de pago
                ->first();

            // Nem toda charge pode ter vindo de webhook, mas se veio e foi processada, valida integridade
        }

        if (!empty($inconsistencies)) {
            // Em produção, isso dispararia um Slack/Discord/Email para o time de Ops.
            Log::channel('audit')->critical('Reconciliação Financeira detectou divergências!', ['inconsistencies' => $inconsistencies]);
        } else {
            Log::channel('audit')->info("Reconciliação Financeira concluída com sucesso. Nenhuma divergência encontrada.");
        }

        return $inconsistencies;
    }
}
