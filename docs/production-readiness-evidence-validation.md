# Production Readiness - Evidence Validation Audit

Data: 2026-06-27  
Objetivo: validar os achados criticos/altos por fluxo real de execucao e eliminar falsos positivos.  
Regra aplicada: um item so permanece bloqueador de Go Live quando ha rota, comando, scheduler, listener, job ou binding que leve o codigo ao runtime atual.

## Resumo Executivo

A auditoria anterior tinha achados corretos por leitura estatica, mas nem todos afetam o runtime atual.

Bloqueadores reais comprovados:

- `CR-03` - WebhookEvent/schema/jobs/controllers incompatíveis em rotas ativas.
- `CR-04` - `/api/webhooks/gateway/{provider}` aceita webhook sem validacao sincronica antes de enfileirar.
- `CR-06` - Fluxos ativos acessam `wallet_balances`, mas a migration da tabela esta `Pending` no banco atual.
- `HI-02` - `ChargeService::create` e runtime real via `user/charge/store`; cria charge antes do PSP e reconciliador existe apenas manualmente.
- `HI-04` - Scheduler nao executa comandos financeiros criticos.
- `HI-11` - `ChargePaidEvent` tem listeners duplicados no runtime.

Itens rebaixados/removidos como bloqueadores atuais:

- `CR-01` - `ProcessWithdrawalJob` nao tem entrada HTTP/command/scheduler atual comprovada.
- `CR-02` - migration nova duplicada de `webhook_events` esta `Pending`; nao afeta o banco atual, mas afeta fresh deploy.
- `CR-05` - middleware de idempotencia roda, mas APIs v1 financeiras atuais sao sandbox mock/live 501, sem efeito financeiro live.
- `HI-03` - dupla movimentacao em `WithdrawalService` nao entra no fluxo atual de saque.
- `HI-18` - pipeline com `DummyCircuitBreaker` nao esta bindada nem usada no fluxo atual de charge; runtime usa `App\Services\CircuitBreakerService`.

## Tabela Final

| ID | Status | Categoria | Bloqueador Go Live |
|---|---|---|---|
| CR-01 | Feature futura | C | Nao |
| CR-02 | Codigo/migration pendente | B | Nao no runtime atual; sim para fresh deploy |
| CR-03 | Confirmado em runtime | A | Sim |
| CR-04 | Confirmado em runtime | A | Sim |
| CR-05 | Feature futura/parcial | C | Nao |
| CR-06 | Confirmado em runtime | A | Sim |
| HI-02 | Confirmado em runtime | A | Sim |
| HI-03 | Feature futura | C | Nao |
| HI-04 | Confirmado em runtime | A | Sim |
| HI-11 | Confirmado em runtime | A | Sim |
| HI-18 | Feature futura | C | Nao |

## CR-01 - ProcessWithdrawalJob

Categoria: **C - Feature futura**  
Bloqueador atual: **Nao**

### Fluxo investigado

Fluxo atual de saque do usuario:

```text
POST /user/withdraw/store
-> routes/web.php:246-249
-> Frontend\WithdrawController@store
-> Payment facade
-> PaymentService::withdrawMoney
-> Wallet::debitGateway
-> Transaction::create
-> paymentGateway->withdraw se metodo AUTOMATIC
```

Fluxo do job investigado:

```text
WithdrawalService::approveWithdrawal
-> dispatch(new ProcessWithdrawalJob)
-> ProcessWithdrawalJob::handle
-> WithdrawalService::processWithdrawal
-> simulatedEfiTxId = 'EFI_' . time()
-> WithdrawalService::completeWithdrawal
```

### Evidencias

- Rota atual de saque: `core/routes/web.php:246-249`.
- Controller atual: `core/app/Http/Controllers/Frontend/WithdrawController.php:33-95`.
- Service atual: `core/app/Services/PaymentService.php:107-204`.
- `ProcessWithdrawalJob` e despachado por `WithdrawalService::approveWithdrawal` em `core/app/Services/Payment/WithdrawalService.php:123-149`.
- `ProcessWithdrawalJob` tambem e despachado por `WithdrawalBatchService` em `core/app/Services/Payment/WithdrawalBatchService.php:10-25`.
- Busca por chamadores mostrou apenas `WithdrawalService` e `WithdrawalBatchService`; nao foi encontrada rota, controller, command ou scheduler chamando esses services.
- `ProcessWithdrawalJob` contem PSP simulado em `core/app/Jobs/ProcessWithdrawalJob.php:39-44`.

### Respostas

- Este codigo e carregado? **Nao no fluxo HTTP atual.** A classe existe, mas nao ha rota/command/scheduler que dispare o service que a enfileira.
- Existe uso real? **Nao comprovado.**
- Pode acontecer em producao? **Nao pelo fluxo atual mapeado.**
- E codigo morto? **Nao.** E codigo de feature futura/parcial.
- Pode ser removido? **Nao agora**, sem decisao de produto sobre o modulo `WithdrawalRequest`.

## CR-02 - Webhook Migrations

Categoria: **B - Codigo/migration pendente**  
Bloqueador atual: **Nao no banco atual; sim para fresh deploy**

### Fluxo investigado

```text
php artisan migrate:status
-> migrations table
-> 2026_06_24_000004_create_webhook_events_table = Ran
-> 2026_06_27_000001_create_webhook_events_table = Pending
-> Schema atual do banco
```

### Evidencias

- `php artisan migrate:status`:
  - `2026_06_24_000004_create_webhook_events_table` = **Ran**
  - `2026_06_27_000001_create_webhook_events_table` = **Pending**
- Schema atual via `Schema::getColumnListing('webhook_events')`:
  - `id,provider,event_id,external_reference,event_type,payload,headers,status,attempts,processed_at,last_error,metadata,created_at,updated_at,resolution_admin_id,resolution_reason`
- Migration antiga: `core/database/migrations/2026_06_24_000004_create_webhook_events_table.php:11-28`.
- Migration nova pendente: `core/database/migrations/2026_06_27_000001_create_webhook_events_table.php:11-30`.

### Respostas

- Este codigo e carregado? **Nao no runtime HTTP.** Migration e executada em deploy.
- Existe uso real? **Sim para a migration antiga. Nao para a migration nova no banco atual.**
- Pode acontecer em producao? **No banco atual, nao. Em fresh migrate, sim.**
- E codigo morto? **Nao.** E migration pendente conflitante.
- Pode ser removido? **Nao sem plano de migration.**

## CR-03 - WebhookEvent

Categoria: **A - Confirmado em runtime**  
Bloqueador atual: **Sim**

### Fluxos completos

Fluxo moderno:

```text
POST /api/webhook/modern/{provider}
-> routes/api.php:22
-> Gateway\ModernWebhookController@handle
-> ModernPaymentGatewayFactory::getGateway
-> gateway->verifyWebhook
-> WebhookEvent::create(provider, payload, headers)
-> ProcessWebhookJob::dispatch
-> ProcessWebhookJob lê event->provider/event->payload/event->headers
```

Fluxo API webhook:

```text
POST /api/webhooks/{gateway}
-> routes/api.php:60
-> Api\WebhookController@handle
-> App\Services\Gateway\GatewayManager::webhookValidator
-> WebhookEvent(gateway, provider_reference, payload_hash, raw_payload)
-> WebhookProcessingJob::dispatch
```

Fluxo admin replay:

```text
POST /admin/webhooks/reprocess/single/{id}
-> routes/admin.php:444...
-> Backend\WebhookAdminController@reprocessSingle
-> ReplayWebhookJob::dispatch
-> ReplayWebhookJob::handle
-> WebhookEvent::firstOrCreate(provider, payload, headers, metadata)
-> ProcessWebhookJob::dispatch
```

### Evidencias

- Rotas ativas:
  - `POST api/webhook/modern/{provider}`.
  - `POST api/webhooks/{gateway}`.
  - `POST admin/webhooks/reprocess/single/{id}`.
- Schema atual tem colunas antigas (`provider`, `payload`, `headers`), comprovado via `Schema::getColumnListing`.
- Model atual `WebhookEvent` tem fillable/casts novos: `gateway`, `provider_reference`, `payload_hash`, `raw_payload`, `failed_at`, `error_message` em `core/app/Models/WebhookEvent.php:13-34`.
- `ModernWebhookController` usa campos antigos `provider`, `payload`, `headers` em `core/app/Http/Controllers/Gateway/ModernWebhookController.php:65-72`.
- `Api\WebhookController` usa campos novos `gateway`, `payload_hash`, `raw_payload` em `core/app/Http/Controllers/Api/WebhookController.php:42-57`, mas essas colunas nao existem no banco atual.
- `ProcessWebhookJob` usa campos antigos `provider`, `payload`, `headers`, `metadata`, `last_error` em `core/app/Jobs/ProcessWebhookJob.php:47-79`.
- `WebhookProcessingJob` usa enum/status novo e `failed_at/error_message` em `core/app/Jobs/WebhookProcessingJob.php:37-61`, colunas nao presentes no schema atual.
- `WebhookAdminController` usa `event->payload` e `event->headers` em `core/app/Http/Controllers/Backend/WebhookAdminController.php:54-63`, coerente com schema antigo, mas incoerente com model fillable novo.

### Respostas

- Este codigo e carregado? **Sim.** Rotas ativas e admin ativo.
- Existe uso real? **Sim.** Ha rotas e jobs registrados.
- Pode acontecer em producao? **Sim.** Qualquer chamada aos webhooks/admin replay usa estes fluxos.
- E codigo morto? **Nao.**
- Pode ser removido? **Nao.** Deve ser corrigido/alinhado.

## CR-04 - Webhook Validation

Categoria: **A - Confirmado em runtime**  
Bloqueador atual: **Sim**

### Fluxo completo

```text
POST /api/webhooks/gateway/{provider}
-> routes/api.php:23
-> middleware: api
-> Webhook\GatewayWebhookController@handle
-> PaymentGateway::where('code', provider)
-> ProcessGatewayWebhookJob::dispatch(provider, request->all(), headers)
-> ProcessGatewayWebhookJob::handle
-> GatewayManager::adapter
-> adapter->handleWebhook
-> ChargeService::markAsPaid
```

### Evidencias

- Rota ativa: `php artisan route:list -v --path=api/webhook`.
- Middleware da rota: apenas `api`.
- Controller enfileira sem validar assinatura em `core/app/Http/Controllers/Webhook/GatewayWebhookController.php:32-39`.
- `ProcessGatewayWebhookJob` processa o payload em `core/app/Jobs/ProcessGatewayWebhookJob.php:45-82`.
- `EfiGatewayAdapter::handleWebhook` valida formato do payload, mas nao assinatura/header em `core/app/Gateway/EfiGatewayAdapter.php:249-268`.
- Banco atual tem gateways `mock,efi,efi_2`, comprovado por query read-only em `PaymentGateway::pluck('code')`.

### Respostas

- Este codigo e carregado? **Sim.**
- Existe uso real? **Sim.** Rota publica API ativa para providers existentes.
- Pode acontecer em producao? **Sim.** `efi` existe no banco.
- E codigo morto? **Nao.**
- Pode ser removido? **Nao.** Deve ser protegido.

Observacao: a rota `POST /api/webhook/modern/{provider}` valida `gateway->verifyWebhook` antes de salvar em `core/app/Http/Controllers/Gateway/ModernWebhookController.php:37-43`. O bloqueador CR-04 se aplica especificamente a `/api/webhooks/gateway/{provider}`.

## CR-05 - Idempotencia

Categoria: **C - Feature futura/parcial**  
Bloqueador atual: **Nao**

### Fluxo completo

```text
POST /api/v1/payments|refunds|payouts
-> routes/api.php:31-42
-> AssignRequestId
-> LogApiRequests
-> AuthenticateApiKey
-> throttle:api
-> CheckIdempotency
-> Controller V1
-> sandbox mock ou live 501
```

### Evidencias

- Rota `api/v1/payments` possui `CheckIdempotency` em `php artisan route:list -v --path=api/v1/payments`.
- `AuthenticateApiKey` injeta `api_user_id`, `api_environment`, `api_key_id` em `core/app/Http/Middleware/AuthenticateApiKey.php:60-65`.
- `CheckIdempotency` so aplica se houver header `Idempotency-Key` em `core/app/Http/Middleware/CheckIdempotency.php:13-18`.
- Lock e criado por read-before-write em `core/app/Http/Middleware/CheckIdempotency.php:29-31,70-87`.
- `PaymentController@store` retorna mock para sandbox e `501` para live em `core/app/Http/Controllers/Api/V1/PaymentController.php:34-53`.
- `RefundController@store` retorna mock para sandbox e `501` para live em `core/app/Http/Controllers/Api/V1/RefundController.php:19-30`.
- `PayoutController@store` retorna mock para sandbox e `501` para live em `core/app/Http/Controllers/Api/V1/PayoutController.php:20-31`.

### Respostas

- Este codigo e carregado? **Sim.** Middleware ativo nas rotas v1.
- Existe uso real? **Sim para o middleware; nao para efeito financeiro live.**
- Pode acontecer em producao? **Nao como duplicidade financeira no estado atual**, porque live retorna 501.
- E codigo morto? **Nao.**
- Pode ser removido? **Nao.** E contrato futuro/parcial da API v1.

## CR-06 - WalletBalance

Categoria: **A - Confirmado em runtime**  
Bloqueador atual: **Sim**

### Fluxos completos

Fluxo saque atual:

```text
POST /user/withdraw/store
-> routes/web.php:246-249
-> Frontend\WithdrawController@store
-> PaymentService::withdrawMoney
-> App\Models\WalletBalance::where(...)
-> Wallet::debitGateway
-> Banco
```

Fluxo action services:

```text
Webhook/Admin action
-> ChargeActionService|ChargebackActionService|SettlementActionService
-> Financial\WalletBalanceService
-> WalletBalance::firstOrCreate/where
-> Banco
```

### Evidencias

- Rota ativa de saque: `POST user/withdraw/store`.
- `PaymentService::withdrawMoney` consulta `WalletBalance` em `core/app/Services/PaymentService.php:135-138`.
- `PaymentService::withdrawMoney` chama `Wallet::debitGateway` em `core/app/Services/PaymentService.php:156-157`.
- `WalletBalanceService` usa `balance` e `blocked_balance` em `core/app/Services/Financial/WalletBalanceService.php:23-35,74-82,129-171`.
- `ChargeActionService` injeta `WalletBalanceService` e chama `creditGateway` em `core/app/Services/ChargeActionService.php:14-16,35-46`.
- `ChargebackActionService` chama `blockFunds` em `core/app/Services/ChargebackActionService.php:13-27`.
- `SettlementActionService` chama wallet gateway debit em `core/app/Services/SettlementActionService.php:29-37`.
- `php artisan migrate:status` mostra `2026_06_27_181001_create_wallet_balances_table` = **Pending**.
- `Schema::hasTable('wallet_balances')` retornou **NO** no banco atual.

### Respostas

- Este codigo e carregado? **Sim.** Rota atual de saque chama `PaymentService::withdrawMoney`.
- Existe uso real? **Sim.**
- Pode acontecer em producao? **Sim.** Uma tentativa de saque atual acessa tabela ausente.
- E codigo morto? **Nao.**
- Pode ser removido? **Nao.** Deve ser migrado/alinhado.

Nota: o achado original falava em colunas incompatíveis. A validacao runtime provou um problema ainda mais imediato no banco atual: a tabela `wallet_balances` nao existe porque a migration esta pendente. Se a migration for aplicada, a incompatibilidade de colunas do `WalletBalanceService` permanece como proximo erro a corrigir.

## HI-02 - ChargeService e charge orfa

Categoria: **A - Confirmado em runtime**  
Bloqueador atual: **Sim**

### Fluxo completo

```text
POST /user/charge/store
-> routes/web.php:185-189
-> Frontend\ChargeController@store
-> ChargeService::create
-> FraudEngineService
-> PlatformFeeService
-> WalletService::getDefaultWalletByUserId
-> Charge::save
-> GatewayResolver::resolveAllForCharge
-> CircuitBreakerService::attemptRequest
-> GatewayManager::adapter
-> adapter->createCharge
-> Charge status WAITING_PAYMENT
-> response JSON
```

### Evidencias

- Rota ativa: `POST user/charge/store` em `php artisan route:list -v --path=charge`.
- Controller chama `ChargeService::create` em `core/app/Http/Controllers/Frontend/ChargeController.php:22-47`.
- `ChargeService` salva a charge antes do PSP em `core/app/Services/ChargeService.php:66-87`.
- PSP/adquirente e chamado depois do save em `core/app/Services/ChargeService.php:105-130`.
- Se nenhum provider tiver sucesso, a charge e deletada em `core/app/Services/ChargeService.php:198-201`.
- Reconciliador existe como comando `gateway:reconcile` em `core/app/Console/Commands/GatewayReconcileCommand.php:17-20`.
- `schedule:list` nao agenda `gateway:reconcile`.

### Respostas

- Este codigo e carregado? **Sim.**
- Existe uso real? **Sim.**
- Pode acontecer em producao? **Sim.** O fluxo HTTP ativo salva antes do PSP e o reconciliador nao e automatico.
- E codigo morto? **Nao.**
- Pode ser removido? **Nao.**

## HI-03 - WithdrawalService dupla movimentacao

Categoria: **C - Feature futura**  
Bloqueador atual: **Nao**

### Fluxo investigado

Fluxo acusado:

```text
WithdrawalService::completeWithdrawal
-> reduz reserved_balance/balance
-> TransactionService::create
-> WithdrawHandler::handleSuccess
-> LedgerService::transfer
```

Fluxo atual real:

```text
POST /user/withdraw/store
-> Frontend\WithdrawController@store
-> PaymentService::withdrawMoney
-> WalletBalance query
-> Wallet::debitGateway
-> Transaction::create
```

### Evidencias

- `WithdrawalService::completeWithdrawal` existe em `core/app/Services/Payment/WithdrawalService.php:206-252`.
- `WithdrawHandler::handleSuccess` transfere fee/net em `core/app/Services/Handlers/WithdrawHandler.php:45-71`.
- Nao foi encontrada rota, controller, command ou scheduler chamando `WithdrawalService::requestWithdrawal`, `approveWithdrawal` ou `WithdrawalBatchService::processBatches`.
- Rota atual de saque usa `PaymentService::withdrawMoney` em `core/app/Http/Controllers/Frontend/WithdrawController.php:91`.

### Respostas

- Este codigo e carregado? **Nao no fluxo atual de saque.**
- Existe uso real? **Nao comprovado.**
- Pode acontecer em producao? **Nao pelo fluxo atual mapeado.**
- E codigo morto? **Nao.** E feature futura/parcial.
- Pode ser removido? **Nao agora.**

## HI-04 - Scheduler

Categoria: **A - Confirmado em runtime**  
Bloqueador atual: **Sim**

### Fluxo completo

```text
php artisan schedule:list
-> bootstrap/app.php withSchedule
-> routes/console.php
-> lista efetiva de tarefas automaticas
```

### Evidencias

`php artisan schedule:list` retorna apenas:

- `0 * * * * php artisan inspire`
- `0 2 * * * Closure at: bootstrap\app.php:110`

Comandos criticos existentes mas nao agendados automaticamente:

- `gateway:reconcile`
- `reconcile:efi`
- `reconcile:efi-balance`
- `reconcile:efi-settlement`
- `reconcile:efi-withdraws`
- `reconcile:ledger`
- `reconcile:transactions`
- `reconcile:wallet-reserves`
- `reconcile:webhooks`
- `ledger:verify-integrity`
- `wallet:rebuild-balances`
- `wallets:audit-gateway-balances`
- `finance:release-reserve`
- `finance:metrics-refresh`

### Respostas

- Este codigo e carregado? **Sim.** Scheduler e runtime operacional.
- Existe uso real? **Sim, mas apenas para inspire e limpeza temporaria.**
- Pode acontecer em producao? **Sim.** Rotinas financeiras nao rodam automaticamente.
- E codigo morto? **Nao.**
- Pode ser removido? **Nao.**

## HI-11 - ChargePaidEvent

Categoria: **A - Confirmado em runtime**  
Bloqueador atual: **Sim**

### Fluxo completo

```text
ChargeService::markAsPaid
-> Event::dispatch(new ChargePaidEvent)
-> DispatchWebhooksListener@handle
-> SendChargePaidEmailListener@handle
-> listeners aparecem duplicados no event:list
```

### Evidencias

- `ChargeService::markAsPaid` dispara `ChargePaidEvent` em `core/app/Services/ChargeService.php:276`.
- `AppServiceProvider` registra manualmente dois listeners em `core/app/Providers/AppServiceProvider.php:91-99`.
- `php artisan event:list` mostra para `App\Events\ChargePaidEvent`:
  - `DispatchWebhooksListener@handle`
  - `SendChargePaidEmailListener@handle`
  - `DispatchWebhooksListener@handle`
  - `SendChargePaidEmailListener@handle`
- `core/app/Providers/EventServiceProvider.php` nao existe.
- `core/bootstrap/cache/events.php` nao existe.

### Respostas

- Este codigo e carregado? **Sim.**
- Existe uso real? **Sim.** `markAsPaid` e chamado por webhook, reconciliador e testes.
- Pode acontecer em producao? **Sim.** Pagamento marcado como pago pode disparar webhook/e-mail duplicado.
- E codigo morto? **Nao.**
- Pode ser removido? **Nao remover evento; corrigir duplicidade.**

## HI-18 - Circuit Breaker

Categoria: **C - Feature futura**  
Bloqueador atual: **Nao**

### Fluxo completo real

```text
POST /user/charge/store
-> ChargeController@store
-> ChargeService::create
-> app(App\Services\CircuitBreakerService::class)
-> attemptRequest
-> recordSuccess/recordFailure
```

### Fluxo da pipeline nova

```text
GatewayPipelineFactory::__construct
-> GatewayCircuitBreakerInterface
-> CircuitBreakerMiddleware
-> DummyCircuitBreaker existe
```

### Evidencias

- `ChargeService` usa `App\Services\CircuitBreakerService` diretamente em `core/app/Services/ChargeService.php:102-110,148,191`.
- `GatewayResolver` tambem usa `App\Services\CircuitBreakerService` em `core/app/Gateway/GatewayResolver.php:213-217`.
- Script read-only de bootstrap do container retornou:
  - `GatewayCircuitBreakerInterface bound=NO`
  - `resolve_error=BindingResolutionException: Target [App\Gateway\CircuitBreaker\GatewayCircuitBreakerInterface] is not instantiable.`
  - `CircuitBreakerService bound=NO`
  - `service_resolved=App\Services\CircuitBreakerService`
- `DummyCircuitBreaker` existe em `core/app/Gateway/CircuitBreaker/DummyCircuitBreaker.php:5-20`, mas nao ha binding encontrado.
- `GatewayIntegrationServiceProvider` existe, mas nao consta em `core/bootstrap/providers.php:3-11`.

### Respostas

- Este codigo e carregado? **CircuitBreakerService sim. Pipeline/Dummy nao no fluxo atual.**
- Existe uso real? **Sim para `App\Services\CircuitBreakerService`; nao comprovado para `DummyCircuitBreaker`.**
- Pode acontecer em producao? **O dummy nao pelo fluxo atual.**
- E codigo morto? **Nao.** E pipeline futura/parcial.
- Pode ser removido? **Nao agora sem decisao arquitetural.**

## Bloqueadores Reais do Go Live

1. **WebhookEvent/schema/jobs/controllers incompatíveis (`CR-03`)**
   - Rotas ativas usam contratos diferentes para a mesma tabela.
   - Banco atual tem schema antigo; model atual esta no contrato novo.

2. **Webhook gateway sem validacao sincronica (`CR-04`)**
   - `/api/webhooks/gateway/{provider}` aceita provider existente e enfileira payload sem validar assinatura.

3. **WalletBalance ausente/incompatível (`CR-06`)**
   - `wallet_balances` nao existe no banco atual.
   - Rota ativa de saque acessa `WalletBalance`.

4. **ChargeService cria registro antes do PSP sem reconciliacao automatica (`HI-02`)**
   - Fluxo HTTP ativo.
   - Reconciliador existe, mas e manual e nao agendado.

5. **Scheduler financeiro ausente (`HI-04`)**
   - Comandos criticos existem, mas nao rodam automaticamente.

6. **ChargePaidEvent duplicado (`HI-11`)**
   - `event:list` comprova quatro listeners para dois handlers.
   - Side effects de webhook/e-mail podem duplicar.

## Itens que nao devem permanecer como bloqueadores atuais

- `CR-01 ProcessWithdrawalJob`: sem cadeia de execucao atual.
- `CR-02 migration duplicada de webhook_events`: pendente no banco atual; tratar em hardening de deploy/fresh migrate.
- `CR-05 idempotencia`: middleware real, mas sem efeito financeiro live nas APIs v1 atuais.
- `HI-03 WithdrawalService`: fluxo acusado nao e o fluxo atual de saque.
- `HI-18 DummyCircuitBreaker`: nao esta bindado nem usado no fluxo atual.

## Parecer Final

Depois da validacao de evidencias, o numero de bloqueadores reais diminuiu, mas os bloqueadores restantes sao suficientes para impedir Go Live financeiro. A prioridade deve ser corrigir somente os itens Categoria A acima. Os itens Categoria B/C devem ir para backlog de cleanup ou evolucao, sem entrar no caminho critico de producao atual.
