<!-- ORIGINPAY AUDIT | generated 2026-07-10T03:24:28 | source: static code inspection | scope: documentation only -->

# 04 — Performance e Resiliência

## Estado observado
Componentes presentes: Redis/cache config, Queue, Horizon, Jobs, Scheduler, CircuitBreaker, Idempotency, DLQ, health checks, observability services, reconciliation commands. Runtime não executado nesta fase.

## Evidências por área
- Queue/Horizon: dependências Laravel Horizon; `config/horizon.php`; jobs como `ProcessGatewayWebhookJob`, `ProcessWebhookJob`, `WebhookProcessingJob`, jobs Connect e Treasury.
- Scheduler/commands: comandos de reconciliação EFI/webhooks/wallet reserves, system health, anomaly scan, validation.
- Cache/Redis: Laravel cache/queue configs; circuit breaker e token EFI usam cache.
- Circuit Breaker: `CircuitBreakerService`, `Gateway/CircuitBreaker/DummyCircuitBreaker.php`, `CircuitBreakerMiddleware.php`.
- Idempotência: `CheckIdempotency`, `IdempotencyMiddleware`, `EloquentIdempotencyRepository`, migrations de idempotency keys.
- DLQ: `WebhookDlq`, `WebhookDeadLetter`, `DlqMonitorService`, reprocess actions, admin routes.
- Observabilidade: `ApiMetricsService`, `GatewayMetricsService`, `QueueMonitorService`, `SchedulerMonitorService`, `SlaMonitorService`, `SystemHealthController`, `HealthCheckController`.
- Ledger/resiliência financeira: `LedgerService`, `VerifyLedgerIntegrityCommand`, reconciliation services/commands.

## Riscos de resiliência
### RES-01 — Múltiplas DLQs/pipelines de webhook
Há `WebhookDlq`, `WebhookDeadLetter`, `WebhookEvent DEAD_LETTER`, controllers e services diferentes. Risco de evento perdido em uma fila que não aparece no dashboard principal.

### RES-02 — Reprocessamento por request simulado
`GatewayWebhookController::reprocess` recria request sem headers originais. Risco de falha/reprocessamento inconsistente e dificuldade de auditoria.

### RES-03 — Circuit breaker dummy
Existe `DummyCircuitBreaker`. Se usado em produção, não protege contra cascata de falhas em PSP.

### RES-04 — Idempotência não comprovada em todos endpoints financeiros
Existem dois middlewares e endpoints diferentes. Risco de duplicidade em rotas que não usam o middleware correto.

### RES-05 — Falha de queue/Redis na ingestão webhook
`GatewayWebhookController` retorna erro interno se dispatch falha. Se PSP não retentar ou se timeout ocorrer, pode haver perda de evento. Necessário persistir evento antes de enfileirar.

## Como evitar perda financeira
1. Toda mutação financeira deve passar por `LedgerService` ou serviço transacional equivalente.
2. Persistir evento externo bruto antes de processar/enfileirar.
3. Reconciliação periódica PSP ↔ charges ↔ ledger ↔ wallet.
4. Alertas para diferença de saldo, evento sem ledger, charge succeeded sem settlement, withdrawal sem débito.
5. Modo read-only financeiro/emergency switch testado.

## Como evitar duplicidade
1. Unique constraints por provider + event_id/txid e por idempotency key + merchant + path + hash.
2. Locks pessimistas (`lockForUpdate`) em wallet/ledger/settlement.
3. Processamento idempotente por estado: eventos repetidos retornam 200 sem mutar novamente.
4. Outbox/inbox pattern para eventos internos.
5. Testes concorrentes obrigatórios.

## Como evitar perda de webhook
1. Assinar e armazenar payload+headers+received_at+provider+signature_valid antes de qualquer side effect.
2. Retornar 2xx somente após persistência durável.
3. Fila dedicada `webhooks_ingestion` com retries/backoff/DLQ.
4. DLQ única e dashboard consolidado.
5. Reconciliation job que busca eventos/transactions no PSP e reidrata ausências.

## Como evitar travamentos
1. Timeouts curtos por provider, retry com backoff e circuit breaker real.
2. Bulkheads por fila/provider: Pix/webhooks/settlement/Connect separados.
3. Horizon com autoscaling/limites e alertas de backlog.
4. Jobs idempotentes e pequenos; não prender DB transaction durante chamadas HTTP externas.
5. Cache e locks com TTL seguro.

## Como evitar race conditions
1. `DB::transaction` + `lockForUpdate` em wallet e ledger.
2. Índices únicos para invariantes financeiras.
3. Evitar `firstOrCreate` sem unique index real em caminhos concorrentes.
4. Ordem consistente de lock de carteiras para transferências.
5. Testes paralelos para charge/webhook/withdraw/settlement.

## Health checks mínimos de produção
- `/api/health/live`: processo vivo sem dependências pesadas.
- `/api/health/ready`: DB, Redis, queue, storage, migrations, PSP critical config.
- `/up`: padrão Laravel.
- Métricas: queue backlog, DLQ count/age, failed jobs, webhook latency, PSP error rate, ledger mismatch count, wallet negative balances, settlement aging.

## Critério de release
- Webhook pipeline durável antes da fila.
- DLQ única/completa, reprocessamento idempotente.
- Circuit breaker real por provider.
- Reconciliation automática verde por 7 dias em sandbox/staging.
- Soak/load test de checkout/webhook/withdraw sem divergência financeira.

## Autossuficiência
Este documento consolida a estratégia de resiliência e deve alimentar diretamente o roadmap de produção.
