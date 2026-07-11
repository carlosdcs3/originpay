<!-- ORIGINPAY PRODUCTION CONSTITUTION | generated 2026-07-10T10:14:39 | source: docs/audit | scope: documentation only -->

# 05 — Alta Disponibilidade e Resiliência

## Objetivo
Travamento, lentidão ou falha de componente nunca pode causar perda financeira, duplicidade financeira ou estado irreconciliável.

## Redis
- Usar Redis para queue/cache/locks somente com configuração HA em produção.
- Locks distribuídos devem ter TTL, owner token e finally/release seguro.
- Falha de Redis deve degradar com segurança: bloquear operação crítica ou persistir para reprocessamento.
- Nunca depender só de cache para verdade financeira.

## Queue e Horizon
- Filas separadas por criticidade:
  - `webhooks_ingestion`
  - `payments`
  - `settlements`
  - `withdrawals`
  - `reconciliation`
  - `notifications`
  - `connect`
- Horizon deve ter supervisores por fila e limites de memória/tempo.
- Jobs financeiros devem ser idempotentes.
- Backlog/idade da fila deve gerar alerta.

## Retry e backoff
- Retry com backoff exponencial e jitter para PSPs.
- Nunca retry infinito em job financeiro sem estado/DLQ.
- Separar erro transitório de erro permanente.
- Registrar tentativa, erro e próximo retry.

## Circuit breaker
- Circuit breaker real por provider/operação.
- Estados: closed, open, half-open.
- Métricas por taxa de erro/timeout.
- Open circuit deve impedir novas chamadas não essenciais e acionar fallback/reconciliation.
- `DummyCircuitBreaker` é proibido em produção.

## DLQ
- DLQ única consolidada para eventos financeiros, com tipo/provider/origem/status/erro/tentativas.
- DLQ deve preservar payload bruto e headers seguros necessários.
- Reprocessamento deve ser interno, idempotente e auditado.
- DLQ age/count por provider gera alerta.
- DLQ não é arquivo morto; é fila operacional com SLA.

## Health checks
- Liveness: processo responde.
- Readiness: DB, Redis, queue, storage, migrations, config PSP crítica.
- Deep health: PSP sandbox/prod conforme ambiente, DLQ, backlog, scheduler freshness.
- Health checks não devem expor segredos.

## Observabilidade
Métricas obrigatórias:
- Webhook received/validated/rejected/processed/DLQ.
- Webhook latency e age.
- Queue backlog por fila.
- Failed jobs.
- PSP timeout/error rate por provider.
- Circuit breaker state.
- Idempotency conflicts.
- Ledger mismatch count.
- Wallet negative attempt count.
- Settlement aging.
- Withdrawal pending/failed.
- Admin sensitive actions.

## Alertas mínimos
- DLQ acima de limiar ou item financeiro crítico.
- Queue financeira parada/backlog alto.
- PSP erro/timeout elevado.
- Ledger/wallet divergence.
- Saldo negativo detectado.
- Webhook rejeitado em massa.
- Failed jobs financeiros.
- Health readiness false.
- Login/admin brute force.

## Timeouts
- Chamadas PSP com timeout curto e configurado por operação.
- Jobs com timeout menor que retry visibility timeout.
- HTTP inbound com limites de tamanho e tempo.
- Transações DB curtas.

## Failover
- PSP fallback somente se não violar idempotência e contrato financeiro.
- Gateway routing deve registrar motivo, provider escolhido e correlation id.
- Failover nunca pode criar duas cobranças pagáveis sem controle.

## Horizontal scaling
- Stateless web nodes.
- Workers escaláveis por fila.
- Locks/unique constraints protegem concorrência.
- Sessions/cache compatíveis com múltiplas instâncias.

## Recuperação de desastre
- Backups criptografados e testados.
- RPO/RTO definidos antes de go-live.
- Restore drill periódico.
- Runbook para reconstruir wallet a partir do ledger.
- Reconciliation pós-restore obrigatória.

## Regra de ouro
Se componente crítico está indisponível e não há caminho seguro/idempotente, bloquear operação financeira e informar estado operacional. Nunca “tentar mesmo assim” com dinheiro.
