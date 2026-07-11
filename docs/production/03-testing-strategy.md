<!-- ORIGINPAY PRODUCTION CONSTITUTION | generated 2026-07-10T10:14:39 | source: docs/audit | scope: documentation only -->

# 03 — Estratégia de Testes

## Status normativo
Nenhuma feature financeira pode ser aprovada sem atender esta estratégia. Compilar não é suficiente. Passar happy path não é suficiente.

## Pirâmide de testes obrigatória
1. Unit tests: regras puras, calculadoras, state machines, validators.
2. Feature tests: controllers, middlewares, policies, flows HTTP.
3. Integration tests: DB, queue, Redis, storage, jobs e providers fake/sandbox.
4. Contract tests: API pública, webhooks inbound/outbound, provider adapters.
5. Concurrency/stress tests: wallet, ledger, withdraw, webhooks, settlement.
6. Security tests: auth, RBAC, OWASP, abuso API.
7. Chaos/resilience tests: Redis/queue/provider down, timeout, retry.
8. Mutation/negative tests: validações financeiras e segurança.

## Cobertura mínima
- Domínio financeiro/gateway/webhook: >= 90% branch coverage relevante.
- Código crítico sem coverage é bloqueador.
- Coverage global menor pode ser aceito temporariamente apenas com mapa de risco.
- Toda correção de bug deve adicionar teste regressivo.

## TDD obrigatório
Para mudanças em fluxos críticos:
1. Escrever teste vermelho reproduzindo regra/bug/ameaça.
2. Implementar mínimo necessário.
3. Refatorar sem mudar comportamento.
4. Rodar suíte específica e regressão crítica.
5. Documentar impacto em `docs/production` se alterar regra.

## Testes obrigatórios por domínio
### Autenticação/API Keys
- Chave válida, inválida, expirada, revogada.
- Sandbox vs produção.
- Escopos insuficientes.
- Rotação e old key grace period.
- Não vazamento de segredo em response/log.

### Webhooks
- Assinatura válida/inválida.
- Timestamp expirado.
- Replay mesmo event id.
- Mesmo event id com payload diferente.
- PSP timeout ao confirmar status.
- Queue down após persistência.
- DLQ e reprocessamento idempotente.

### Wallet/Ledger
- Crédito/débito básico.
- Saldo insuficiente.
- Concurrent double spend.
- Double webhook não duplica crédito.
- Ledger hash/imutabilidade.
- Reversal/refund/chargeback.

### Checkout
- Valor imutável.
- Sessão expirada.
- Merchant desabilitado.
- Método indisponível.
- Alteração client-side rejeitada.
- Pagamento duplicado.

### Admin/RBAC
- Usuário comum não acessa admin.
- Admin sem permissão não executa action financeira.
- CSRF em POST sensível.
- Audit log obrigatório.
- Rotas retired bloqueadas.

### Settlement/Withdraw
- Elegibilidade.
- Reserva de saldo.
- KYC/limite/antifraude.
- PSP sucesso/falha/timeout.
- Concorrência de múltiplos saques.
- Reconciliação pós-falha.

## Contract tests
- API pública deve ter contrato versionado.
- Webhooks outbound para merchants devem ter schema, assinatura e exemplos.
- Provider adapters devem passar suite comum: create charge, get status, refund, payout, webhook normalize.
- Contratos quebrados exigem versionamento ou migração.

## Stress e performance
- Webhook burst por provider.
- 50+ requisições concorrentes para mesma charge/idempotency key.
- Saques concorrentes no mesmo wallet.
- Settlement batch grande.
- Dashboard com volume realista.
- Soak test mínimo antes de release candidate.

## Chaos testing
- Redis indisponível.
- Queue worker morto.
- PSP lento/timeouts.
- DB lock contention.
- DLQ overflow.
- Falha durante deploy.
- Reconciliation detectando divergência.

## Mutation/negative testing
Obrigatório em:
- Cálculo de fees.
- Arredondamento monetário.
- State transitions.
- Assinatura webhook.
- Idempotência.
- RBAC admin.

## CI/CD gates
- Lint/style.
- Unit + feature tests.
- Security tests críticos.
- Migration fresh em testing.
- Static analysis quando disponível.
- Dependency audit.
- Coverage report.
- Artefatos de teste anexados ao release.

## Definição de pronto
Uma mudança só está pronta quando:
- Testes novos e antigos passam.
- Invariantes financeiras continuam válidas.
- Segurança revisada.
- Logs/métricas atualizados.
- Documentação atualizada.
- Rollback conhecido.
