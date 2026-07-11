# Production Blocking Reproduction Suite

Data: 2026-06-27  
Escopo: execução controlada dos bloqueadores restantes, sem correção de código, sem migration, sem alteração persistente de banco.

## Resumo Executivo

| ID | Reproduzido | Evidência | Bloqueia Produção |
| --- | --- | --- | --- |
| CR-03 | Sim | `ReplayWebhookJob` falha com `ValueError: "RECEIVED" is not a valid backing value for enum App\Enums\WebhookEventStatus`; webhooks modernos/API também não chegam ao processamento normal. | Sim |
| CR-04 | Sim | `/api/webhooks/gateway/efi` respondeu HTTP 200 e enfileirou job sem assinatura e com assinatura inválida. | Sim |
| CR-06 | Sim | `wallet_balances` não existe; `WalletBalance::where(...)` falha com SQLSTATE `42S02`. | Sim |
| HI-02 | Sim, com dependência de ambiente | Ambiente CLI falha antes por Redis ausente; com Redis controlado, `ChargeService::create` faz `insert into charges` e quebra em `GatewayManager::adapter()` inexistente, deixando charge pendente na transação. | Sim |
| HI-04 | Configuração + evidência crítica | `schedule:list` não agenda os comandos financeiros; execução manual mostrou `ledger:verify-integrity` com 43 violações. | Sim para integridade do Ledger; scheduler é configuração |
| HI-11 | Sim | Dispatch real de `ChargePaidEvent` enfileirou 4 jobs: 2 `DispatchWebhooksListener` e 2 `SendChargePaidEmailListener`. | Sim |

## CR-03 — WebhookEvent

### Fluxo Executado

1. `POST /api/webhooks/efi`
2. `POST /api/webhook/modern/stripe`
3. Caminho de reprocessamento admin: `POST /admin/webhooks/reprocess/single/{id}` via `ReplayWebhookJob` com `WebhookDlq` criado em transação e rollback.

### Request

`POST /api/webhooks/efi`

```json
{"pix":[{"txid":"tx-cr03","valor":"1.00","endToEndId":"e2e-cr03"}]}
```

`POST /api/webhook/modern/stripe`

```json
{"id":"evt-cr03","type":"charge.paid","data":{"object":{"id":"ch_123"}}}
```

### Controller, Service, Job, Event, Listener

- Controller: `App\Http\Controllers\Api\WebhookController@handle`
- Controller: `App\Http\Controllers\Gateway\ModernWebhookController@handle`
- Controller admin: `App\Http\Controllers\Backend\WebhookAdminController@reprocessSingle`
- Job: `App\Jobs\ReplayWebhookJob`
- Job destino: `App\Jobs\ProcessWebhookJob`
- Event/Listener: não chegou a disparar no reprocessamento por falha de criação do evento.

### Banco e SQL

Tabela existente:

```text
webhook_events columns:
id, provider, event_id, external_reference, event_type, payload, headers, status, attempts,
processed_at, last_error, metadata, created_at, updated_at, resolution_admin_id, resolution_reason
```

SQL do replay:

```sql
select * from `webhook_events` where (`provider` = ? and `event_id` = ?) limit 1
bindings: ["efi","evt-dlq-cr03-model"]
```

### Logs, Queue, Resultado

`POST /api/webhooks/efi`:

```text
HTTP_STATUS=400
BODY={"error":"Webhook validator not implemented for this provider."}
WEBHOOK_EVENTS_COUNT_TX=0
QUEUE_ProcessGatewayWebhookJob=0
```

`POST /api/webhook/modern/stripe`:

```text
HTTP_STATUS=500
BODY={"error":"Internal Server Error"}
LOG=Webhook Controller Error for STRIPE: Modern payment gateway adapter not implemented for provider: STRIPE
WEBHOOK_EVENTS_COUNT_TX=0
```

Admin reprocess:

```text
EXCEPTION=ValueError: "RECEIVED" is not a valid backing value for enum App\Enums\WebhookEventStatus
```

### Stack Trace

```text
App\Enums\WebhookEventStatus::from('RECEIVED')
Illuminate\Database\Eloquent\Model->setEnumCastableAttribute('status', 'RECEIVED')
Illuminate\Database\Eloquent\Builder->create(...)
App\Jobs\ReplayWebhookJob->handle()
```

### Classificação

## ✅ Confirmado

Existe erro: **SIM**. O reprocessamento admin é incompatível com o enum atual (`RECEIVED` maiúsculo contra `received` minúsculo). As rotas públicas testadas também não chegam a um processamento válido: EFI retorna validator não implementado e modern retorna adapter não implementado.

## CR-04 — Gateway Webhook Validation

### Fluxo Executado

`POST /api/webhooks/gateway/efi` com três variações:

- Sem assinatura
- Assinatura inválida
- Assinatura "válida" sintética

### Request

```json
{"pix":[{"txid":"tx-cr04-no_signature","valor":"1.00","endToEndId":"e2e-cr04-no_signature"}]}
```

### Controller, Service, Job, Event, Listener

- Controller: `App\Http\Controllers\Webhook\GatewayWebhookController@handle`
- Service: lookup direto de `PaymentGateway`
- Job: `App\Jobs\ProcessGatewayWebhookJob`
- Queue: `webhooks_ingestion`
- Event/Listener: não executado; `Queue::fake` confirmou enqueue.

### Banco e SQL

```sql
select * from `payment_gateways` where `code` = ? limit 1
bindings: ["efi"]
```

Nenhum registro em `webhook_events` foi criado neste endpoint antes da fila.

### Logs, Queue, Resultado

```text
CASE=no_signature HTTP_STATUS=200 BODY={"status":"received"} QUEUE_ProcessGatewayWebhookJob=1 DB_WEBHOOK_EVENTS=0
CASE=invalid_signature HTTP_STATUS=200 BODY={"status":"received"} QUEUE_ProcessGatewayWebhookJob=1 DB_WEBHOOK_EVENTS=0
CASE=valid_like_signature HTTP_STATUS=200 BODY={"status":"received"} QUEUE_ProcessGatewayWebhookJob=1 DB_WEBHOOK_EVENTS=0
```

### Classificação

## ✅ Confirmado

Existe aceitação indevida: **SIM**. Webhook sem assinatura e com assinatura inválida foi aceito com HTTP 200 e entrou na fila.

## CR-06 — Wallet Balance

### Fluxo Executado

1. Fluxo base de saque equivalente ao core de `POST /user/withdraw/store`, chamando `PaymentService::withdrawMoney`.
2. Consulta runtime direta ao model usado pelo serviço: `App\Models\WalletBalance`.

### Request

Fluxo controlado:

```text
amount=1.00
user_id=1
wallet_id=1
withdraw_method_id=1
```

### Controller, Service, Job, Event, Listener

- Controller esperado: `App\Http\Controllers\Frontend\WithdrawController@store`
- Service executado: `App\Services\PaymentService::withdrawMoney`
- Model: `App\Models\WalletBalance`
- Job/Event/Listener: não chegou a disparar.

### Banco e SQL

Schema:

```text
SCHEMA_wallet_balances_exists=NO
```

Consulta direta:

```text
EXCEPTION=Illuminate\Database\QueryException:
SQLSTATE[42S02]: Base table or view not found: 1146 Table 'digikash.wallet_balances' doesn't exist
SQL: select * from `wallet_balances` where `wallet_id` = 1 and `available` >= 1
```

### Logs, Queue, Resultado

No fluxo de saque, antes de chegar ao `wallet_balances`, o serviço registrou:

```text
local.ERROR: Withdrawal failed {"error":"Undefined array key \"conversion_rate_live\"","amount":1.0,"user_id":1}
```

Resultado direto do model:

```text
SCHEMA_wallet_balances_exists=NO
SQLSTATE[42S02]: Base table or view not found
```

### Stack Trace

```text
Illuminate\Database\Connection->runQueryCallback(...)
Illuminate\Database\Query\Builder->runSelect()
Illuminate\Database\Eloquent\Builder->getModels()
App\Models\WalletBalance::where(...)->get()
```

### Classificação

## ✅ Confirmado

A tabela realmente não existe: **SIM**. O fluxo de saque atual ainda tem um erro anterior em `WithdrawMethod`, mas o model usado pelo saque para saldo por gateway quebra no banco com `42S02`.

## HI-02 — ChargeService

### Fluxo Executado

1. Execução real de `ChargeService::create`, caminho core de `POST /user/charge/store`.
2. Primeira execução no ambiente atual.
3. Segunda execução controlada com Redis fake no container do processo, sem alteração de código, para ultrapassar a dependência de Redis e testar o trecho PSP/gateway.

### Request

```text
POST /user/charge/store
payment_method=pix
amount=12.34
name=Controlled Buyer
email=buyer@example.com
document=12345678909
description=HI-02 reproduction
```

### Controller, Service, Job, Event, Listener

- Controller esperado: `App\Http\Controllers\Frontend\ChargeController@store`
- Service executado: `App\Services\ChargeService::create`
- Services internos: `FraudEngineService`, `PlatformFeeService`, `WalletService`, `CircuitBreakerService`
- Gateway resolver: `App\Gateway\GatewayResolver`
- Gateway manager chamado: `App\Gateway\GatewayManager::adapter`
- Job/Event/Listener: não chegou a disparar.

### Banco e SQL

Primeira execução, ambiente atual:

```text
EXCEPTION=Error: Class "Redis" not found
STACK: FraudEngineService->evaluateRisk(...) -> ChargeService->create(...)
CHARGES_IN_TX=16
```

Segunda execução, com Redis fake:

```sql
insert into `charges`
(`uuid`, `correlation_id`, `idempotency_key`, `user_id`, `payment_method`, `amount`,
 `platform_fee`, `gateway_fee`, `net_amount`, `description`, `customer_name`,
 `customer_email`, `customer_document`, `status`, `expires_at`, `updated_at`, `created_at`)
values (...)
bindings include: ["pix",12.34,0.5468,0,11.7932,"HI-02 reproduction","Controlled Buyer","buyer@example.com","12345678909","pending"]
```

Depois do insert:

```text
EXCEPTION=Error: Call to undefined method App\Gateway\GatewayManager::adapter()
LAST_CHARGE_IN_TX={"id":18,"status":"pending","amount":"12.34","gateway_id":null,"gateway_charge_id":null}
CHARGES_IN_TX=17
ORPHAN_CREATED_IN_TX=YES
CHARGES_AFTER_ROLLBACK=16
```

### Logs, Queue, Resultado

- Ambiente atual: depende de Redis PHP extension ou cliente Redis alternativo instalado.
- Com Redis disponível: a charge é persistida antes da chamada ao gateway e um `Error` não capturado por `catch (Exception)` interrompe o fluxo sem remover a charge.
- Queue: nada enfileirado.
- Scheduler: não há recuperação automática observada neste fluxo.

### Stack Trace

```text
ChargeService->create(...)
CodexFakeRedisFunnel2->then(...)
Error: Call to undefined method App\Gateway\GatewayManager::adapter()
```

### Classificação

## ✅ Confirmado

Existe charge órfã: **SIM**, em ambiente controlado com Redis disponível. No ambiente CLI atual, o mesmo fluxo depende de ambiente porque falha antes com `Class "Redis" not found`.

## HI-04 — Scheduler

### Fluxo Executado

1. `php artisan schedule:list`
2. `php artisan gateway:reconcile --hours=1 -vvv`
3. `php artisan ledger:verify-integrity -vvv`
4. `php artisan reconcile:webhooks --days=1 -vvv`

### Request

Comandos Artisan locais, sem request HTTP.

### Controller, Service, Job, Event, Listener

- Commands:
  - `gateway:reconcile`
  - `ledger:verify-integrity`
  - `reconcile:webhooks`
- Queue/Event/Listener: não aplicável nestes testes.

### Banco e SQL

SQL não foi instrumentado nos comandos Artisan. A evidência é o output runtime dos comandos.

### Logs, Queue, Resultado

Scheduler atual:

```text
0 * * * *  php artisan inspire
0 2 * * *  Closure at: bootstrap\app.php:110
```

Comando gateway:

```text
Iniciando reconciliação de cobranças pendentes (últimas 1h)...
Encontradas 0 cobranças PENDING elegíveis.
Nenhuma cobrança pendente elegível encontrada.
Exit code: 0
```

Comando webhook:

```text
Starting Webhook Reconciliation for the last 1 days...
No anomalies found in Webhooks.
Exit code: 0
```

Comando ledger:

```text
Iniciando varredura de integridade do Ledger...
Violação Mutante: Transação ID 1 teve os dados alterados silenciosamente. Hash inválido.
...
Violação Mutante: Transação ID 43 teve os dados alterados silenciosamente. Hash inválido.
CRÍTICO: A integridade do Ledger foi comprometida. 43 violações detectadas!
Exit code: 1
```

### Classificação

## ⚠ Configuração

A ausência no scheduler é configuração: os comandos existem e dois rodam manualmente sem erro. Porém há um achado crítico de runtime: `ledger:verify-integrity` detectou 43 violações reais. Isso bloqueia produção por integridade de dados, ainda que o item "Scheduler" em si dependa de configuração.

## HI-11 — ChargePaidEvent

### Fluxo Executado

Dispatch real:

```php
Event::dispatch(new ChargePaidEvent($charge, (float) $charge->amount));
```

`Queue::fake` foi usado para capturar os listeners `ShouldQueue` sem enviar e-mail/webhook externo.

### Request

Evento interno com charge existente:

```text
CHARGE_ID=1
UUID=f055f3a4-c498-4d8c-845f-79d54ca040c2
```

### Controller, Service, Job, Event, Listener

- Event: `App\Events\ChargePaidEvent`
- Listener: `App\Listeners\DispatchWebhooksListener`
- Listener: `App\Listeners\SendChargePaidEmailListener`
- Queue job wrapper: `Illuminate\Events\CallQueuedListener`

### Banco e SQL

```sql
select * from `charges` limit 1
```

Nenhuma alteração de banco foi persistida.

### Logs, Queue, Resultado

```text
QUEUED_TOTAL=4
LISTENER=App\Listeners\DispatchWebhooksListener COUNT=2
LISTENER=App\Listeners\SendChargePaidEmailListener COUNT=2
QUEUED_CLASSES=[
  "App\\Listeners\\DispatchWebhooksListener",
  "App\\Listeners\\SendChargePaidEmailListener",
  "App\\Listeners\\DispatchWebhooksListener",
  "App\\Listeners\\SendChargePaidEmailListener"
]
```

### Stack Trace

Sem exception.

### Classificação

## ✅ Confirmado

Duplicou: **SIM**. Execução real do evento enfileirou cada listener duas vezes.

## Bloqueadores Confirmados

1. CR-03: incompatibilidade real de `WebhookEvent` no reprocessamento admin e webhooks modernos/API sem caminho válido de processamento.
2. CR-04: endpoint gateway aceita webhook sem assinatura/inválido e enfileira processamento.
3. CR-06: tabela `wallet_balances` ausente e model quebra em runtime.
4. HI-02: criação de charge pode deixar registro órfão; além disso, ambiente atual quebra antes por Redis ausente.
5. HI-11: `ChargePaidEvent` despacha listeners duplicados.
6. Integridade Ledger: `ledger:verify-integrity` detectou 43 violações reais.

## Bloqueadores Descartados

Nenhum dos seis itens testados foi descartado integralmente. O item HI-04 como "ausência de scheduler" foi reclassificado como configuração, mas a execução manual revelou uma falha crítica real de integridade.

## Dependem Apenas de Configuração

- HI-04 Scheduler: os comandos financeiros não estão agendados em `schedule:list`.

## Dependem Apenas do Ambiente

- HI-02 no ambiente CLI atual: `Class "Redis" not found` impede o fluxo de charge antes da criação da cobrança. Sem extensão `phpredis` ou `predis`, o ambiente não reproduz a chamada gateway real.

## Certificação

Hoje, com base em execução real e não em análise estática:

- Bloqueadores restantes: **6**
- Quais são:
  - CR-03 WebhookEvent/reprocessamento
  - CR-04 validação de webhook gateway
  - CR-06 wallet balances ausente
  - HI-02 charge órfã/charge flow quebrado
  - HI-11 listeners duplicados
  - Ledger integrity com 43 violações
- Eles impedem Go Live: **SIM**
- Nova nota de Production Readiness: **58/100**

Correções obrigatórias antes da produção:

1. Corrigir compatibilidade `WebhookEvent`/enum/jobs/controllers e validar rotas reais de webhook.
2. Bloquear webhook gateway sem assinatura válida antes de enfileirar job.
3. Resolver `wallet_balances`: migration aplicada ou remover dependência runtime de tabela inexistente.
4. Corrigir fluxo `ChargeService::create`: Redis operacional, `GatewayManager::adapter()` válido e proteção transacional contra `Throwable` após o `insert`.
5. Remover duplicidade real de listeners de `ChargePaidEvent`.
6. Investigar e corrigir as 43 violações de integridade do Ledger antes do Go Live.

Pode ficar para versão futura:

- Estratégias avançadas de scheduler além do agendamento mínimo dos comandos críticos.
- Providers modernos não usados, desde que as rotas públicas sejam desativadas/protegidas ou respondam de forma explícita sem 500.
