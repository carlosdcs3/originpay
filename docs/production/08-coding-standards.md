<!-- ORIGINPAY PRODUCTION CONSTITUTION | generated 2026-07-10T10:14:39 | source: docs/audit | scope: documentation only -->

# 08 — Coding Standards e Regras de Engenharia

## Status normativo
Estes padrões são obrigatórios. Se uma implementação futura violar qualquer regra, interromper e reportar inconsistência antes de continuar.

## Nunca
- Nunca implementar sem testes.
- Nunca alterar fluxo financeiro sem validar invariantes.
- Nunca criar bypass de segurança para “ganhar tempo”.
- Nunca expor segredo em response, log, exception, dump ou frontend.
- Nunca misturar legado e moderno sem adaptador e plano de migração.
- Nunca duplicar lógica financeira, assinatura webhook ou idempotência.
- Nunca alterar saldo diretamente em controller, job ou model sem serviço aprovado.
- Nunca editar ledger histórico.
- Nunca confiar em valor vindo do frontend para dinheiro.
- Nunca retornar erro detalhado que permita enumeração de credenciais.
- Nunca executar scripts `fix_*`/`scratch_*` em produção sem runbook e aprovação.

## Sempre
- Sempre usar Open GSD Core para planejamento/execução incremental.
- Sempre usar Impeccable quando houver interface visual/admin/checkout/dashboard.
- Sempre escrever ou atualizar testes automatizados.
- Sempre validar segurança e regressão.
- Sempre documentar mudança relevante em `docs/production`.
- Sempre preservar request_id/correlation_id.
- Sempre usar transações/locks em mutações financeiras.
- Sempre mascarar dados sensíveis.
- Sempre adicionar teste regressivo para bug.
- Sempre revisar impacto em filas, retries, DLQ e reconciliation.

## Padrões PHP/Laravel
- Controllers finos; regra de negócio em services/domain.
- Form Requests ou validators explícitos para entrada.
- Policies/gates para autorização de recursos.
- DTOs para contratos internos críticos.
- Enums/state machines para status financeiros.
- DB transactions curtas.
- Não fazer chamada HTTP externa dentro de transação longa.
- Jobs idempotentes e pequenos.
- Exceptions específicas para domínio/gateway.

## Padrões financeiros
- Dinheiro como decimal seguro/minor units; evitar float em cálculo crítico.
- Arredondamento explícito por moeda.
- Fees versionadas/snapshotadas.
- Ledger append-only.
- Reversal em vez de update/delete.
- Unique constraints para referências externas.
- State transitions explícitas e testadas.

## Padrões de segurança
- Validação allowlist.
- Output escaping.
- Sanitização para HTML customizado.
- Secrets por secret manager/env seguro.
- API keys hash-based.
- Webhook validators por provider com contract tests.
- Rate limit em endpoints sensíveis.
- Audit log para ações sensíveis.

## Padrões de testes
- Teste vermelho antes de mudança crítica.
- Unit para regra pura.
- Feature para fluxo HTTP.
- Integration para DB/queue/provider fake.
- Contract para API/webhook/provider.
- Concurrency para saldo/evento financeiro.
- Security para auth/RBAC/input.
- Regression para bug.

## Padrões de revisão
Todo PR/plano deve responder:
1. Quais invariantes financeiras são afetadas?
2. Quais ameaças de segurança são relevantes?
3. Quais testes provam comportamento?
4. Qual rollback?
5. Quais métricas/logs mudam?
6. Há impacto em docs/production?
7. Há legado sendo tocado? Qual plano?

## Padrões de interface — Impeccable
Para admin, checkout, dashboard e developer portal:
- Estados loading/empty/error/success.
- Feedback claro para operações financeiras pendentes.
- Não exibir segredos após criação, exceto uma vez.
- Confirmar ações destrutivas/sensíveis.
- Acessibilidade básica.
- Responsividade.
- Não depender de frontend para autorização/valor.

## Definition of Done
Uma mudança está pronta somente se:
- Implementação segue arquitetura oficial.
- Testes obrigatórios passam.
- Segurança revisada.
- Invariantes preservadas.
- Observabilidade adequada.
- Documentação atualizada.
- Rollback conhecido.
- Nenhum crítico/alto aberto.
