<?php

namespace Database\Seeders;

use App\Enums\DisputeStatus;
use App\Enums\DisputeType;
use App\Models\Dispute;
use App\Models\DisputeEvent;
use App\Models\DisputeEvidenceItem;
use App\Models\DisputeMessage;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DisputeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Limpar disputas existentes (sendo mock)
        \Illuminate\Support\Facades\Schema::disableForeignKeyConstraints();
        DisputeEvent::truncate();
        DisputeEvidenceItem::truncate();
        DisputeMessage::truncate();
        Dispute::truncate();
        \Illuminate\Support\Facades\Schema::enableForeignKeyConstraints();

        // Tentar pegar um lojista e um usuário genérico, ou criar mock rápido se não tiver
        $merchant = User::first() ?? User::factory()->create(['firstname' => 'Lojista', 'lastname' => 'Teste']);

        $disputesData = [
            [
                'type' => DisputeType::MED,
                'status' => DisputeStatus::WAITING_MERCHANT_DOCS,
                'amount_cents' => 150000, // R$ 1.500,00
                'retained_amount_cents' => 150000,
                'gateway' => 'MercadoPago',
                'reason' => 'Suspeita de fraude reportada pelo banco de origem.',
                'source' => 'Itaú Unibanco',
                'due_at' => now()->addDays(2),
            ],
            [
                'type' => DisputeType::CHARGEBACK,
                'status' => DisputeStatus::EVIDENCE_SENT,
                'amount_cents' => 45050, // R$ 450,50
                'retained_amount_cents' => 45050,
                'gateway' => 'Stripe',
                'reason' => 'Transação não reconhecida',
                'source' => 'Mastercard',
                'due_at' => now()->addDays(5),
            ],
            [
                'type' => DisputeType::REFUND_REQUEST,
                'status' => DisputeStatus::RECEIVED,
                'amount_cents' => 8990, // R$ 89,90
                'retained_amount_cents' => 0,
                'gateway' => 'Pagar.me',
                'reason' => 'Produto não entregue',
                'source' => 'Cliente Final',
                'due_at' => now()->addDays(7),
            ],
            [
                'type' => DisputeType::CONTESTATION,
                'status' => DisputeStatus::WON,
                'amount_cents' => 25000, // R$ 250,00
                'retained_amount_cents' => 0,
                'recovered_amount_cents' => 25000,
                'gateway' => 'OriginPay Gen',
                'reason' => 'Fraude amigável',
                'source' => 'Visa',
                'due_at' => now()->subDays(10),
                'resolved_at' => now()->subDays(2),
            ],
        ];

        foreach ($disputesData as $data) {
            $dispute = Dispute::create(array_merge($data, [
                'uuid' => (string) Str::uuid(),
                'merchant_id' => $merchant->id,
            ]));

            // Criar eventos genéricos
            DisputeEvent::create([
                'dispute_id' => $dispute->id,
                'event_type' => 'created',
                'title' => 'Disputa Iniciada',
                'description' => 'Notificação recebida da adquirente.',
                'created_at' => now()->subDays(3),
            ]);

            // Se esperando docs do lojista
            if ($dispute->status === DisputeStatus::WAITING_MERCHANT_DOCS) {
                DisputeEvent::create([
                    'dispute_id' => $dispute->id,
                    'event_type' => 'status_change',
                    'title' => 'Aguardando Lojista',
                    'description' => 'Solicitamos as evidências para defesa.',
                    'created_at' => now()->subDays(1),
                ]);

                DisputeMessage::create([
                    'dispute_id' => $dispute->id,
                    'sender_type' => 'system',
                    'message' => 'Precisamos do comprovante de entrega até ' . $dispute->due_at->format('d/m/Y') . '.',
                ]);

                DisputeEvidenceItem::create([
                    'dispute_id' => $dispute->id,
                    'type' => 'invoice',
                    'label' => 'Nota Fiscal',
                    'required' => true,
                ]);
                DisputeEvidenceItem::create([
                    'dispute_id' => $dispute->id,
                    'type' => 'tracking',
                    'label' => 'Comprovante de Entrega',
                    'required' => true,
                ]);
            }

            // Se evidências enviadas
            if ($dispute->status === DisputeStatus::EVIDENCE_SENT) {
                DisputeEvidenceItem::create([
                    'dispute_id' => $dispute->id,
                    'type' => 'tracking',
                    'status' => 'validated',
                    'label' => 'Comprovante de Entrega',
                    'required' => true,
                    'file_path' => 'evidences/mock_file.pdf',
                    'reviewed_at' => now(),
                ]);

                DisputeMessage::create([
                    'dispute_id' => $dispute->id,
                    'sender_type' => 'merchant',
                    'sender_id' => $merchant->id,
                    'message' => 'Segue em anexo o comprovante assinado pelo cliente.',
                ]);

                DisputeMessage::create([
                    'dispute_id' => $dispute->id,
                    'sender_type' => 'admin',
                    'sender_id' => 1,
                    'message' => 'Documento validado. Evidências enviadas ao gateway, aguardando resposta.',
                ]);
            }
        }
    }
}
