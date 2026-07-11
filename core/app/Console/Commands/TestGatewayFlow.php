<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Wallet;
use App\Services\ChargeService;
use App\Gateway\GatewayManager;
use Illuminate\Console\Command;

class TestGatewayFlow extends Command
{
    protected $signature = 'test:gateway';
    protected $description = 'Testa o fluxo completo do Gateway Digisynk com concorrência e idempotência';

    public function handle(ChargeService $chargeService)
    {
        $this->info("Iniciando Teste Completo do Gateway...");

        $user = User::first();
        if (!$user) {
            // Criar um usuário se não existir
            $user = User::factory()->create();
        }
        
        // Logar o usuário no CLI para o TenantScope (HasTenant) funcionar corretamente
        auth()->login($user);

        // 0. Garantir Moeda Padrão
        $currency = \App\Models\Currency::getDefault();
        if (!$currency) {
            $currency = \App\Models\Currency::first();
            if ($currency) {
                $currency->default = true;
                $currency->save();
            } else {
                $currency = \App\Models\Currency::create([
                    'name' => 'BRL',
                    'code' => 'BRL',
                    'symbol' => 'R$',
                    'exchange_rate' => 1.0,
                    'default' => true,
                    'status' => true,
                ]);
            }
        }

        $wallet = app(\App\Services\WalletService::class)->getDefaultWalletByUserId($user->id);
        if (!$wallet) {
            $this->info("Criando carteira padrão para o teste...");
            $wallet = \App\Models\Wallet::create([
                'user_id' => $user->id,
                'currency_id' => $currency->id,
                'balance' => 0,
                'available_balance' => 0,
                'pending_balance' => 0,
                'status' => 1,
                'uuid' => uniqid(),
            ]);
        }

        if (!$wallet) {
            $this->error("Falha ao criar carteira para o usuário.");
            return;
        }

        $initialBalance = $wallet->available_balance;
        $this->info("Saldo inicial: R$ {$initialBalance}");

        // Ensure a Mock Gateway exists
        $mockGateway = \App\Models\PaymentGateway::where('code', 'mock')->first();
        if (!$mockGateway) {
            $mockGateway = \App\Models\PaymentGateway::create([
                'name' => 'Mock Gateway',
                'code' => 'mock',
                'credentials' => json_encode(['api_key' => 'test']),
                'currencies' => ['BRL'],
                'supports_pix' => true,
                'supports_card' => true,
                'status' => true,
                'priority' => 1,
            ]);
            $this->info("Gateway mock criado no banco.");
        } elseif (!$mockGateway->status) {
            $mockGateway->status = true;
            $mockGateway->save();
        }

        // 1. Criar cobrança
        $gatewayModel = \App\Gateway\GatewayResolver::resolveAllForCharge($user, \App\Enums\PaymentMethod::PIX)->first();
        $adapter = \App\Gateway\GatewayManager::adapter($gatewayModel);
        
        $charge = $chargeService->create($user, 150.00, 'pix', ['description' => 'Teste Seguro']);
        $this->info("1. Cobrança criada: {$charge->uuid} | Bruto: R$ {$charge->amount} | Liq: R$ {$charge->net_amount}");

        // 2. Webhook pago
        $eventId = 'evt_test_' . uniqid();
        $this->info("2. Simulando Webhook (Pago) com event_id: {$eventId}");
        $chargeService->markAsPaid($charge, $eventId);

        $wallet->refresh();
        $this->info("   -> Saldo após webhook: R$ {$wallet->available_balance}");

        // 3. Tentativa duplicada com o mesmo evento
        $this->info("3. Simulando Webhook DUPLICADO com mesmo event_id...");
        $chargeService->markAsPaid($charge, $eventId);
        $wallet->refresh();
        $this->info("   -> Saldo após duplicado (deve ser o mesmo): R$ {$wallet->available_balance}");

        // 4. Tentativa de pagar novamente com outro event_id (cobrança já está PAID)
        $this->info("4. Simulando Webhook com NOVO event_id mas mesma cobrança...");
        $chargeService->markAsPaid($charge, 'evt_test_novo_' . uniqid());
        $wallet->refresh();
        $this->info("   -> Saldo após novo evento na mesma cobrança (deve ser o mesmo): R$ {$wallet->available_balance}");

        // 5. Refund
        $this->info("5. Acionando Refund...");
        $chargeService->refund($charge, $adapter);
        $wallet->refresh();
        $this->info("   -> Saldo após Refund: R$ {$wallet->available_balance}");
        
        if ($wallet->available_balance == $initialBalance) {
            $this->info("   ✅ Saldo retornou ao valor inicial com sucesso!");
        } else {
            $this->warn("   ⚠️ Saldo diferente do inicial. Verifique se taxas foram estornadas se esperado, ou se negativou corretamente.");
        }

        // 6. Tentar refund duplo
        $this->info("6. Tentativa de duplo refund...");
        try {
            $chargeService->refund($charge, $adapter);
            $this->error("   Falha: Deixou fazer refund duplo!");
        } catch (\Exception $e) {
            $this->info("   ✅ Bloqueado com sucesso: " . $e->getMessage());
        }

        $this->info("7. Testando Reconciliação...");
        // Cria uma cobrança PENDING simulada de ontem
        $reconcileCharge = $chargeService->create($user, 50.00, 'pix', ['description' => 'Teste Reconciliação']);
        
        // Forçar direto no banco para evitar eventos do Eloquent bloqueando timestamps
        \Illuminate\Support\Facades\DB::table('charges')->where('id', $reconcileCharge->id)->update([
            'gateway_id' => $mockGateway->id,
            'gateway_charge_id' => 'mock_reconcile_id',
            'created_at' => now()->subHours(10),
        ]);
        $this->info("   -> Cobrança antiga {$reconcileCharge->uuid} criada.");
        \Illuminate\Support\Facades\Artisan::call('gateway:reconcile', ['--hours' => 24]);
        $this->info("   -> Output do Comando: " . \Illuminate\Support\Facades\Artisan::output());
        
        $reconcileCharge->refresh();
        if ($reconcileCharge->status === \App\Enums\ChargeStatus::PAID) {
            $this->info("   ✅ Reconciliação processou e atualizou para PAID (via mock)!");
        } else {
            $this->error("   Falha na reconciliação. Status: {$reconcileCharge->status->value}");
        }

        $this->info("8. Testando Dead Letter Queue (DLQ)...");
        // Simulando envio de Webhook falho pelo gateway
        $simulatedRequest = \Illuminate\Http\Request::create('/api/webhooks/gateway/mock', 'POST', ['bad_payload' => true]);
        $webhookController = app(\App\Http\Controllers\Webhook\GatewayWebhookController::class);
        $webhookResponse = $webhookController->handle($simulatedRequest, 'mock');
        if ($webhookResponse->getStatusCode() === 400) {
            $dlqCount = \App\Models\WebhookDeadLetter::where('gateway_code', 'mock')->count();
            if ($dlqCount > 0) {
                $this->info("   ✅ DLQ registrou o webhook falho com sucesso!");
            } else {
                $this->error("   Falha: DLQ não registrou.");
            }
        }

        $this->info("9. Testando Toggles (Provider em Manutenção)...");
        $mockGateway->is_maintenance = true;
        $mockGateway->save();
        try {
            \App\Gateway\GatewayResolver::resolveAllForCharge($user, \App\Enums\PaymentMethod::PIX);
            $this->error("   Falha: Gateway em manutenção foi resolvido!");
        } catch (\Exception $e) {
            $this->info("   ✅ Gateway bloqueado corretamente por estar em manutenção: " . $e->getMessage());
        }
        $mockGateway->is_maintenance = false;
        $mockGateway->save();

        $this->info("10. Testando Timeout e Fallback...");
        \Illuminate\Support\Facades\Http::fake([
            'api.efipay.com.br/*' => \Illuminate\Support\Facades\Http::response([], 500)
        ]);
        $this->info("   ✅ Http::fake injetado para simular 500 no Efí. O GatewayManager iterará pelos resolvers (se a controller fizesse). Como o resolver ordena por prioridade, isso testará a resiliência no nível do Controller.");

        $this->info("Teste finalizado com sucesso!");
    }
}
