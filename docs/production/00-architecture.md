<!-- ORIGINPAY PRODUCTION CONSTITUTION | generated 2026-07-10T10:14:39 | source: docs/audit | scope: documentation only -->

# 00 — Arquitetura Oficial de Produção

## Status normativo
Este documento define a arquitetura oficial alvo da OriginPay para produção financeira real. Ele substitui decisões implícitas do código legado. Qualquer implementação futura que contradiga este documento deve ser interrompida até que a arquitetura seja atualizada por decisão explícita.

## Fonte oficial de contexto
- `docs/audit/EXECUTIVE_SUMMARY.md`
- `docs/audit/01-current-state.md`
- `docs/audit/02-security.md`
- `docs/audit/03-testing.md`
- `docs/audit/04-performance-resilience.md`
- `docs/audit/05-product-roadmap.md`

## Princípios arquiteturais
1. Segurança e consistência financeira têm prioridade sobre velocidade de entrega.
2. Nenhuma mutação financeira pode ocorrer fora de serviços transacionais aprovados.
3. Todo evento externo deve ser durável, auditável, idempotente e reconciliável.
4. O sistema deve preferir falhar fechado: se não há prova de integridade, não processar dinheiro.
5. Código legado não é fonte de verdade. Legado só pode operar atrás de adaptadores controlados.
6. Cada módulo deve ter dono, contrato, invariantes, testes e métricas.

## Arquitetura oficial alvo
A OriginPay é um monólito Laravel modularizado por domínios, com boundaries internos explícitos:

```text
HTTP/API/Admin/Checkout
  -> Application Services
    -> Domain Services / Domain Models
      -> Ledger / Wallet / Gateway / Webhook / Settlement
        -> Persistence / Queue / External Providers
```

Camadas oficiais:

1. **Interface**
   - API pública v1.
   - Checkout público.
   - Admin operacional.
   - Developer portal.
   - Webhooks inbound.
   - Dashboards.

2. **Application Services**
   - Orquestram casos de uso.
   - Validam autorização de alto nível.
   - Iniciam transações quando necessário.
   - Nunca devem conter lógica criptográfica ou financeira duplicada.

3. **Domain/Core**
   - Define regras de charge, payment, wallet, ledger, settlement, refund, chargeback e reconciliation.
   - Contém invariantes e state machines.

4. **Infrastructure**
   - Banco, filas, Redis, Horizon, cache, storage, logs, métricas, provedores externos.

5. **Observability/SRE**
   - Health checks, métricas, alertas, DLQ, audit logs e runbooks.

## Source of truth por domínio
| Domínio | Source of truth oficial | Observação |
|---|---|---|
| Gateway/PSP | `app/Gateway` ou módulo sucessor consolidado | `app/Payment` é legado até migração formal. |
| Pagamentos modernos/API | Controllers API v1 + services aprovados | Contrato público deve ser documentado e testado. |
| Webhooks inbound | Pipeline único a ser consolidado | Caminhos atuais são duplicados e devem ser harmonizados. |
| Wallet | Serviço transacional de wallet + ledger | Model não pode ser fonte isolada de alteração de saldo. |
| Ledger | `LedgerService`/ledger domain aprovado | Ledger é autoridade contábil. |
| Idempotência | Middleware/serviço único consolidado | Dois middlewares atuais não são aceitáveis em produção final. |
| API Keys | Modelo hash-based de credenciais | Chaves em claro em `Merchant` são legado. |
| Admin RBAC | Matriz de permissões por ação | Rotas retired não são produção. |
| DLQ | DLQ única consolidada | Múltiplas DLQs atuais exigem consolidação. |
| Observabilidade | Health/metrics/logs centralizados | Métricas devem ser acionáveis. |

## Módulos ativos alvo
Ativos para produção após hardening e testes:
- API pública v1 de payments/charges/refunds/payouts/balance/customers/subscriptions.
- Checkout público.
- Developer portal com API keys hash-based e webhooks.
- Gateway provider registry moderno.
- Ledger/wallet/settlement/reconciliation.
- Webhook pipeline único.
- Admin operacional com RBAC e audit.
- Observabilidade, Horizon, queues e DLQ.
- Connect/CMS somente após hardening separado.

## Módulos legados ou sob quarentena
- `app/Payment/*`: legado. Não adicionar novos providers aqui.
- `app/Payment/Modern/*`: transição; deve convergir para source of truth oficial.
- Providers duplicados EFI em múltiplos diretórios: devem ser consolidados antes de go-live.
- Rotas `$retiredAdminModule`: não podem ser consideradas produção.
- Scripts root `fix_*`, `scratch_*`, `refactor*`, `rebrand*`, `create_backup*`: quarentena operacional; não executar em produção sem runbook, revisão e backup.
- Mocks/dummies: `MockGatewayAdapter`, `DummyCircuitBreaker`, comandos de mock/test flow não podem ser ativados em produção.

## Fluxos oficiais
### Charge/Pix/Boleto/Card
1. Merchant autentica via API key hash-based.
2. Request passa por rate limit, idempotência e validação de contrato.
3. Charge é criada em estado inicial sem side effect irreversível fora de transação.
4. Gateway provider cria cobrança externa.
5. Resposta é persistida com referência externa e correlação.
6. Webhook/reconciliation confirma estado.
7. Ledger/wallet é alterado uma única vez por evento idempotente.

### Webhook inbound
1. Receber payload bruto e headers.
2. Verificar provider, assinatura, timestamp e replay antes de qualquer mutação financeira.
3. Persistir evento bruto de forma durável.
4. Responder 2xx somente após persistência durável.
5. Enfileirar processamento idempotente.
6. Processar state transition validada.
7. Gerar ledger/wallet/settlement conforme invariantes.
8. Enviar para DLQ única se falhar.

### Settlement/Withdraw
1. Validar KYC, limites, saldo disponível, antifraude e permissões.
2. Bloquear saldo/reserva em transação.
3. Criar ledger entries correspondentes.
4. Executar provider fora de transação longa, com estado intermediário seguro.
5. Reconciliar confirmação externa.
6. Liberar, completar ou reverter segundo state machine.

### Admin financeiro
1. Admin autentica com 2FA e sessão segura.
2. Cada ação exige permissão específica.
3. Toda ação sensível gera audit log imutável.
4. Ações de risco alto exigem dual control quando aplicável.
5. Nenhuma tela admin pode alterar saldo diretamente fora de serviço financeiro aprovado.

## Dependências críticas
- PHP 8.3 e Laravel 11.
- Banco transacional com locks corretos.
- Redis para queue/cache/locks distribuídos quando aplicável.
- Horizon para workers supervisionados.
- Storage seguro para uploads/certificados.
- Provedores PSP com timeouts, retry e circuit breaker.
- Observabilidade com alertas financeiros.

## Regras de evolução
- Nova feature financeira exige atualização de `02-financial-invariants.md`, testes e documentação de fluxo.
- Novo provider exige contract tests, sandbox verification, webhook validation, reconciliation e runbook.
- Qualquer código legado tocado deve ter plano de migração ou isolamento.
- Nenhum módulo periférico pode compartilhar fila crítica financeira sem justificativa.
