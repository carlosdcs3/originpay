# R5 — RBAC, Autorização e Hardening dos Painéis

## Escopo e documentação canônica

Documentação lida antes de implementação: `R1-api-keys-secrets`, `R2-webhooks-dlq-idempotency`, `R2.5-gateway-architecture`, `R3-wallet-ledger-financial-invariants`, `R4-settlement-reconciliation`, `production/00-architecture`, `01-security-baseline`, `02-financial-invariants`, `03-testing-strategy`, `05-high-availability`, `08-coding-standards` e `09-gateway-architecture`.

Objetivo: elevar a OriginPay a autorização financeira enterprise: nenhuma ação crítica sem autorização explícita, auditoria e rastreabilidade.

## Diagnóstico antes das mudanças

### Arquitetura encontrada

- Admin é montado em `bootstrap/app.php` com middleware global `web`, `auth:admin`, `verified`, `XSS`, `lock_screen`, `2fa`, `demo` e `AdminAuditMiddleware` sobre `routes/admin.php`.
- RBAC admin usa Spatie Permission com guard `admin` e controllers que herdam `App\Http\Controllers\Backend\BaseController`, o qual converte `permissions()` em middleware `permission:*` por action.
- Há seeders de permissão legados (`PermissionTableSeeder`) e enterprise (`EnterpriseRbacSeeder`), com possível duplicidade semântica entre permissões antigas e granulares.
- Merchant/user panel fica sob `/user` com `auth`, `account.status.check`, `verified`, `2fa`, `block.ip`, `transaction.password`; mutações sensíveis geralmente adicionam `transaction.verified`.
- Developer Portal filtra API Keys, webhooks e logs por `user_id`/endpoint owner.
- API pública v1 usa `api.request_id`, `api.log`, `api.auth`, `throttle:api`, `api.idempotency`; endpoints de refund/payout exigem `api.transaction_password` e hoje retornam 501.
- Webhooks inbound públicos existem sem autenticação de sessão por desenho, mas dependem de validação de assinatura/idempotência descritas em R2; um endpoint administrativo de reprocessamento em `routes/api.php` permanece exposto sem `auth:admin`.

### Achados principais

| ID | Achado | Risco | Evidência | Status |
| --- | --- | --- | --- | --- |
| R5-01 | Rotas admin financeiras críticas dependem só do middleware global quando controllers não declaram permissões granulares | Alto | `PaymentGatewayController::permissions()` retorna `[]`; rotas de gateway credentials/taxes/routing alteram configuração PSP | Aberto |
| R5-02 | `routes/api.php` expõe `POST /api/admin/webhooks/dead-letters/{id}/reprocess` sem `auth:admin` | Crítico | Rota fora do grupo admin; reprocessamento DLQ financeiro | Aberto — requer correção prioritária compatível |
| R5-03 | `ApiKeyController::rotate()` gerava hash incompatível com middleware sha256, quebrando autenticação após rotação | Alto | Store usa sha256; rotate usava `Hash::make()` | Corrigido |
| R5-04 | Rotas admin de settlement, finance enterprise, gateway fees e API credentials não usam middleware explícito por rota/policy granular | Alto | `routes/admin.php` linhas de finance/api-credentials/gateway-fees | Aberto |
| R5-05 | Admin `loginAsUser` é permissão muito sensível e pode causar bypass operacional se audit trail/justificativa não for forte | Alto | `UserManageController` permissão `user-login-as` | Aberto |
| R5-06 | Ajuste manual de saldo existe como `admin.user.update-balance`; deve exigir motivo, permissão separada e ledger compensatório | Crítico | `UserManageController::updateBalance`; FI-18 | Aberto |
| R5-07 | Developer API keys criadas com `permissions => ['*']`, sem escopo granular por ação | Médio/Alto | Developer `ApiKeyController::store` | Aberto compatibilidade |
| R5-08 | Rotas merchant/user usam filtros por owner em pontos auditados, mas ausência de policies padronizadas dificulta prova formal anti-IDOR | Médio | API keys/webhooks/logs filtram `user_id`; wallet/charge/withdraw precisam policies | Aberto |
| R5-09 | Ausência de matriz única de permissões versus rotas dificulta segregação de funções | Médio | Permissões espalhadas em seeders/controllers | Documentado aqui |

## Plano, impacto e riscos

Plano mínimo compatível aplicado nesta fase:

1. Corrigir bug pontual de rotação de API Keys do Developer Portal para manter hash sha256 compatível com `AuthenticateApiKey`.
2. Adicionar teste regressivo específico para garantir rotação com hash sha256.
3. Documentar matriz de autorização e riscos residuais sem alterar amplamente RBAC para evitar quebra de compatibilidade.

Impacto: baixo; não altera contratos HTTP nem nomes de rotas. Apenas API keys rotacionadas passam a autenticar corretamente.

Risco da mudança: baixo; utiliza o mesmo algoritmo já usado no `store()` e esperado pelo middleware de autenticação.

## Matriz de permissões e acesso real

| Recurso | Quem pode acessar/deveria | Quem realmente acessa | Quem NÃO deveria acessar | Risco |
| --- | --- | --- | --- | --- |
| Admin dashboard | Operações/admin com `dashboard.view` ou equivalente | Qualquer admin autenticado que passe middleware global se rota sem permissão | Merchant/user/operador sem necessidade | Médio |
| Admin finance ledger/reconciliation/transactions | Financeiro, auditoria, owner | Admin autenticado; algumas controllers enterprise usam policies/permissões parcialmente | Suporte, marketing, dev sem need-to-know | Alto |
| Admin settlement pay | Financeiro/owner com permissão específica e motivo | Admin autenticado em rota sem middleware granular aparente | Suporte/compliance readonly | Alto |
| Admin withdraw approve/reject | Financeiro/ops autorizado | `WithdrawController` com `withdraw-action` | Suporte/marketing/dev | Médio se permissões bem atribuídas |
| Admin update balance | Somente owner/financeiro sênior, dual control e motivo | `user-balance-manage` | Suporte, operador, dev | Crítico |
| Admin gateway credentials | DevOps/owner com segregação e auditoria | Rotas `payment.gateway.update-credentials` sem permission mapping no controller | Financeiro/suporte/compliance | Alto |
| Admin gateway taxes/fees/limits | Financeiro/owner | Parte via `GatewayFeeController`/platform fee; gateway taxes sem granularidade suficiente | Suporte/dev sem aprovação | Alto |
| Admin API credentials generate/rotate/revoke | Owner/devops/compliance aprovado | Admin autenticado nas rotas `api-credentials` | Suporte/financeiro comum | Alto |
| Admin webhook endpoints | DevOps/owner | Admin autenticado nas rotas `webhook-endpoints` | Suporte/financeiro comum | Alto |
| Admin DLQ reprocess | Operações/financeiro técnico com auditoria | Admin web via `WebhookAdminController`; também API route sem auth | Público/merchant/user | Crítico |
| KYC | Compliance/suporte autorizado | `KycController` com `kyc-list`/`kyc-action`; documento precisa validação | Financeiro/dev/marketing | Médio |
| Support/inbox/chat | Suporte/owner | Admin autenticado; algumas rotas sem RBAC granular claro | Financeiro/dev sem necessidade | Médio |
| Merchant dashboard | Próprio merchant/user autenticado | `/user` autenticado | Outro merchant/admin impersonado sem trilha | Médio |
| Merchant API Keys | Próprio merchant com senha transacional | Query por `user_id`, store/revoke/rotate com senha transacional | Outro merchant | Baixo após correção; escopos `*` ainda médio |
| Merchant Webhooks | Próprio merchant com senha transacional para criar | Query por `user_id`/endpoint owner | Outro merchant | Baixo/Médio |
| Merchant API logs | Próprio merchant | Query por `user_id`; headers sanitizados | Outro merchant | Baixo |
| Wallet | Próprio usuário/merchant | Rotas `/user/wallet`; requer auditoria adicional de controller | Outro merchant | Médio |
| Charges dashboard | Próprio merchant | Rotas `/user/charge`; controller deve filtrar owner | Outro merchant | Médio |
| Withdraw user | Próprio usuário/merchant, KYC, transaction password | Route exige `transaction.verified`, `prevent.duplicate`, feature | Outro merchant | Médio |
| Public API charges | API key válida do merchant | `api.auth` injeta `api_user_id`; controllers filtram por user_id | Outro merchant/API key sem scope | Médio por falta de escopos granulares |
| Public API refunds/payouts | API key + senha transacional + escopo futuro | Protegido; retorna 501 | Acesso sem senha/escopo | Baixo atual, alto quando implementar |
| Connect | Merchant autorizado por capabilities | Controllers usam `$this->authorize(Capabilities::*)` | Merchant sem capability/outro tenant | Baixo/Médio |

## Rotas financeiras críticas mapeadas

| Rota | Middleware | Policy/Gate/Role/Permission | Controller/Service | Risco |
| --- | --- | --- | --- | --- |
| `POST /api/v1/charges`, `/payments` | `api.request_id`, `api.log`, `api.auth`, `throttle`, `api.idempotency`, `throttle:payments` | Sem scope granular; owner por API key | `Api\V1\PaymentController` -> `ChargeService` | Médio |
| `POST /api/v1/refunds` | API middleware + `api.transaction_password` | Sem scope granular | `RefundController` 501 | Baixo atual |
| `POST /api/v1/payouts` | API middleware + `api.transaction_password` | Sem scope granular | `PayoutController` 501 | Baixo atual |
| `GET /api/v1/balance` | API middleware | Owner por key | `BalanceController` | Médio |
| `POST /user/charge/store` | user group | Sem policy explícita | `Frontend\ChargeController` -> charge service | Médio |
| `POST /user/withdraw/store` | user group + `transaction.verified`, `prevent.duplicate`, `feature:withdraw` | Sem policy explícita | `WithdrawController` | Médio |
| `POST /user/developer/api-keys` | user group + `transaction.verified` | Owner por `user_id` | `ApiKeyController::store` | Médio (`*` scopes) |
| `POST /user/developer/api-keys/{id}/rotate` | user group + `transaction.verified` | Owner por `user_id`; corrigido hash | `ApiKeyController::rotate` | Baixo/Médio |
| `POST /user/developer/webhooks` | user group + `transaction.verified` | Owner por `user_id` | `WebhookController::store` | Baixo/Médio |
| `POST /admin/finance/settlements/{settlement}/pay` | admin global | Ausência granular clara | `Finance\SettlementController::pay` | Alto |
| `POST /admin/user/update-balance` | admin global | `user-balance-manage` se controller middleware aplicado | `UserManageController::updateBalance` | Crítico |
| `POST /admin/payment/gateway/{id}/credentials` | admin global | `PaymentGatewayController::permissions()` vazio | `PaymentGatewayController::updateCredentials` -> credential service | Alto |
| `POST /admin/payment/gateway/{id}/taxes` | admin global | `PaymentGatewayController::permissions()` vazio | `PaymentGatewayController::updateTaxes` | Alto |
| `POST /admin/gateway-fees/{id}` | admin global | Sem mapping confirmado | `GatewayFeeController::update` | Alto |
| `POST /api/admin/webhooks/dead-letters/{id}/reprocess` | `api` only | Nenhuma | `GatewayWebhookController::reprocess` | Crítico |

## Correção aplicada

- `app/Http/Controllers/User/Developer/ApiKeyController.php`
  - `rotate()` passou a persistir `hash('sha256', $newSecretKey)` em vez de `Hash::make($newSecretKey)`, alinhando rotação ao `store()` e ao middleware `AuthenticateApiKey`.

## Testes criados/ajustados

- `tests/Feature/ApiCredentialsSecurityTest.php`
  - Novo teste: `test_developer_portal_rotation_stores_sha256_hash_compatible_with_api_authentication`.

## Testes executados

```text
E:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe artisan test tests/Feature/ApiCredentialsSecurityTest.php
```

Resultado:

```text
PASS  Tests\Feature\ApiCredentialsSecurityTest
8 passed (39 assertions)
```

## Regressão mínima

A regressão mínima executada nesta fase foi a suíte de segurança de credenciais/API keys, pois a correção alterou somente rotação de Developer Portal/API Key. Regressão ampliada recomendada antes de release: `PaymentsAuthenticationTest`, `PaymentsApiSkeletonTest`, `PaymentsIdempotencyTest`, `GatewayWebhookIdempotencyTest`, testes de admin/RBAC a criar e suíte completa.

## Bloqueios para produção

1. Remover/proteger imediatamente `POST /api/admin/webhooks/dead-letters/{id}/reprocess` com `auth:admin`, RBAC e auditoria, ou migrar para rota admin web existente.
2. Adicionar permissões granulares a `PaymentGatewayController`, settlement pay, gateway fees, API credentials admin e webhook endpoints admin.
3. Exigir motivo estruturado e audit log específico para settlement pay, gateway credentials, alteração de taxas/limites, update balance, DLQ reprocess e login-as-user.
4. Implementar scopes de API Keys (`charges:read/write`, `refunds:write`, `payouts:write`, `webhooks:*`, `balance:read`) mantendo fallback compatível.
5. Padronizar policies anti-IDOR para wallet, charges, payment links, withdraw accounts, subscriptions, tickets, webhooks e API logs.
6. Consolidar seeders/permissões duplicadas e formalizar papéis: super admin/owner, financeiro, suporte, compliance, auditoria, desenvolvedor/ops.

## Notas de maturidade

- RBAC: 5/10
- Segurança de Painéis: 6/10
- Isolamento entre Merchants: 7/10
- Segurança Administrativa: 4/10
- Auditoria de Ações: 5/10
- Developer Portal: 7/10

Recomendação: **não liberar este módulo como enterprise/produção plena ainda**. Há controles bons de autenticação global, 2FA, senha transacional e owner filtering no Developer Portal, mas os bloqueios críticos de reprocessamento DLQ via API e ausência de permissões granulares em ações administrativas financeiras impedem aprovação de produção enterprise.
