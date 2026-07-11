<!-- ORIGINPAY AUDIT | generated 2026-07-10T03:24:28 | source: static code inspection | scope: documentation only -->

# 05 — Roadmap de Produto até Produção

## Premissas
- Não implementar antes de validar baseline com PHP 8.3, Composer, DB e Redis.
- Priorizar risco financeiro/segurança antes de features.
- Manter documentação em `docs/audit` como memória oficial.

## Fase R0 — Baseline executável
Prioridade: Crítica. Impacto: desbloqueia auditoria runtime.
Dependências: PHP 8.3, Composer, Node, banco testing, Redis.
Arquivos: `composer.json`, `.env.example`, `phpunit.xml`, configs.
Testes obrigatórios: `php artisan about`, `php artisan route:list`, `php artisan migrate:fresh --env=testing`, `php artisan test`.
Aceite: ambiente reproduzível e relatório de testes real salvo em docs/audit/runtime-baseline.md.

## Fase R1 — Segurança de autenticação e credenciais
Prioridade: Crítica.
Objetivo: unificar API keys e segredos.
Arquivos: `AuthenticateApiKey`, `MerchantApiAuth`, `ApiAuthenticationService`, `Merchant`, `ApiKey`, migrations de credentials.
Testes: chave válida/inválida/revogada/expirada/sandbox/prod; leak de JSON; rotação.
Aceite: nenhuma API secret armazenada/retornada em claro; caminho legado desativado ou encapsulado.

## Fase R2 — Webhook pipeline único e seguro
Prioridade: Crítica.
Objetivo: assinatura, anti-replay, persistência durável, DLQ única.
Arquivos: `Api\WebhookController`, `GatewayWebhookController`, `EfiWebhookController`, validators, jobs, DLQ models/services.
Testes: assinatura inválida, timestamp antigo, replay, duplicado, PSP down, queue down, reprocess.
Aceite: todo webhook persiste antes de processar; reprocessamento idempotente preserva contexto.

## Fase R3 — Núcleo financeiro: wallet/ledger/withdraw/settlement
Prioridade: Crítica.
Objetivo: provar invariantes financeiras.
Arquivos: `LedgerService`, `Wallet`, `WalletService`, withdrawal/settlement/charge services.
Testes: concorrência, saldo insuficiente, double credit, chargeback/refund, reconciliation.
Aceite: nenhum teste concorrente gera saldo negativo/divergência; alteração de saldo fora do ledger bloqueada.

## Fase R4 — Admin/RBAC/hardening operacional
Prioridade: Alta.
Objetivo: reduzir superfície administrativa.
Arquivos: `routes/admin.php`, controllers admin/backend, permissions, admin menus.
Testes: bypass por role, CSRF, actions financeiras, retired routes.
Aceite: matriz de permissões aprovada; rotas retired removidas ou bloqueadas.

## Fase R5 — Checkout e API pública contrato v1
Prioridade: Alta.
Objetivo: estabilizar merchant-facing API e checkout.
Arquivos: `routes/api.php`, `PaymentController`, `ChargeController`, `SessionController`, checkout controllers, docs.
Testes: contrato OpenAPI, valores imutáveis, idempotência, rate limit, Pix/boleto/card.
Aceite: documentação e contract tests verdes; sem divergência entre dashboard/API.

## Fase R6 — Observabilidade, SRE e DR
Prioridade: Alta.
Objetivo: produção operável.
Arquivos: health controllers, metrics services, Horizon config, logging config, commands.
Testes: Redis down, DB down, queue backlog, DLQ overflow, PSP timeout, incident drill.
Aceite: dashboards/alertas definidos, runbooks, RTO/RPO, backup/restore testados.

## Fase R7 — Connect/CMS/áreas não-core
Prioridade: Média.
Objetivo: endurecer módulos periféricos.
Arquivos: `routes/connect.php`, Connect services/jobs, CMS controllers/custom landing.
Testes: XSS, access control, campaign quotas, provider failures, uploads.
Aceite: sem XSS armazenado, sem vazamento cross-tenant, filas separadas.

## Fase R8 — Release Candidate
Prioridade: Crítica para go-live.
Objetivo: congelar escopo e validar produção.
Testes: full suite, load/soak, pentest, reconciliation sandbox, UAT merchant/admin, rollback.
Aceite: zero críticos/altos abertos; checklist produção assinado.

## Ordem recomendada
R0 → R1 → R2 → R3 → R4 → R5 → R6 → R7 → R8.

## Arquivos/documentos complementares a criar
- `docs/audit/runtime-baseline.md`
- `docs/audit/security-remediation-plan.md`
- `docs/audit/webhook-contract.md`
- `docs/audit/financial-invariants.md`
- `docs/audit/production-runbook.md`

## Autossuficiência
Este roadmap é derivado das Fases 1–4 e serve como plano oficial até produção.
