<!-- ORIGINPAY AUDIT | generated 2026-07-10T03:24:28 | source: static code inspection | scope: documentation only -->

# 03 — Auditoria de Testes e Estratégia TDD

## Estado atual
Há 102 arquivos de teste em `core/tests`, usando Pest. A suíte não foi executada porque PHP não está disponível no PATH da sessão de auditoria. Portanto este documento avalia cobertura nominal/estática, não resultado real.

## Cobertura existente observada
Áreas com testes explícitos:
- Gateway: `EfiGatewayTest`, `EfiGatewayAdapterTest`, `GatewayManagerTest`, `GatewayWebhookValidationTest`, `GatewayConcurrencyTest`, `NewProvider*`, `ModernPaymentGatewayTest`.
- Financeiro: `LedgerTest`, `LedgerIntegrityHardeningTest`, `WalletIntegrityTest`, `WalletBalanceRuntimeTest`, `FinancialConcurrencyTest`, `Finance/*Concurrency*`, `ChaosTest`.
- Webhooks: `WebhookSignatureTest`, `WebhookEventContractTest`, `WebhookAsyncProcessingTest`, `WebhookStressTest`, `GatewayWebhookOfflineTest`, `WebhookAdminPanelTest`.
- API: `PaymentsApiSkeletonTest`, `PaymentsAuthenticationTest`, `PaymentsIdempotencyTest`, `CustomerSubscriptionApiTest`.
- Produto: payment link, boleto, subscriptions, KYC, compliance, platform fees, withdrawals, support/admin UI.
- Connect: access control, campaign engine, execution pipeline, contacts, segments, templates.

## Lacunas críticas
1. Resultado real desconhecido: suíte não executada.
2. Ausência confirmada de contrato único para gateways/webhooks: testes podem cobrir caminhos isolados mas não impedir divergência entre `app/Payment`, `app/Gateway`, `app/Payment/Modern`.
3. Login/register/admin bypass precisam testes ofensivos completos.
4. API keys: testar tanto camada antiga `MerchantApiAuth` quanto nova `AuthenticateApiKey`.
5. Reprocessamento DLQ: testar headers/assinatura/contexto original.
6. CMS/custom HTML: testes XSS/CSP insuficientes até provar contrário.
7. Uploads: KYC, support attachments, image-upload, file download precisam testes de path traversal/MIME.
8. Migrations: precisa `migrate:fresh` em banco descartável.

## Estratégia TDD recomendada
### Princípios
- Nenhuma mudança financeira sem teste vermelho primeiro.
- Testes por contrato para API pública e webhooks.
- Testes concorrentes obrigatórios para wallet/ledger/withdraw/settlement.
- Factories determinísticas e fixtures de gateways fake assinadas.
- Separar testes unitários de domínio, feature HTTP e integração com provider sandbox.

## Plano de testes funcionais obrigatórios
| Área | Testes mínimos |
|---|---|
| Login | sucesso, senha errada, usuário bloqueado, 2FA, lock screen, brute force, sessão expirada. |
| Cadastro | usuário, merchant, validação email/documento, duplicados, aprovação/rejeição. |
| Checkout | sessão válida/expirada, valor imutável, merchant inválido, Pix/boleto/card, cancelamento. |
| Pix | criação cobrança, txid único, webhook válido, webhook duplicado, webhook inválido, reconciliação. |
| Webhook | assinatura, timestamp, replay, evento fora de ordem, DLQ, reprocessamento idempotente. |
| Wallet | crédito/débito, saldo insuficiente, locks, multi-gateway balance, invariantes. |
| Ledger | dupla entrada, imutabilidade, hash, reversão/refund/chargeback, export/audit. |
| Settlement | elegibilidade, reserva, payout, falha provider, retry, conciliação. |
| Withdraw | KYC, transaction password, limite, antifraude, fila/admin approval, race. |
| API | auth, scopes/permissões, idempotency, rate limit, payload inválido, versionamento. |
| Dashboard | RBAC, dados agregados corretos, sem vazamento entre merchants. |
| Merchant | API keys, webhook endpoints, sandbox/prod, rotação/revogação. |
| Admin | permissões por action, update balance proibido sem role, auditoria. |

## Testes ofensivos planejados
- Brute force login/admin/API keys.
- Replay webhook mesmo `event_id`, timestamp antigo, assinatura válida mas payload alterado.
- Race condition em 50 requisições simultâneas para charge paid, withdraw e settlement.
- Alteração de valores no checkout/client-side/API.
- Alteração de `user_id`, `merchant_id`, `wallet_id` em payloads.
- Bypass admin: usuário comum acessando rotas admin e actions POST.
- API inválida: chave revogada, expirada, sandbox em produção, sem escopo.
- CSRF em rotas web sensíveis: withdraw, API key rotate, webhook endpoint update.
- XSS armazenado em CMS/custom landing/support messages.
- SQL injection em filtros search/daterange/admin reports.
- Upload: polyglot, SVG script, path traversal, extensão dupla, arquivo grande.

## Comandos de validação quando PHP estiver disponível
```bash
cd core
php -v
composer install
php artisan about
php artisan migrate:fresh --env=testing
php artisan test
./vendor/bin/pest --coverage
./vendor/bin/pest tests/Feature/Gateway tests/Feature/Finance tests/Feature/*Webhook*
```

## Critério mínimo de produção
- 100% dos testes críticos acima verdes.
- Cobertura de domínio financeiro/gateway/webhook >= 90% por branch relevante.
- Testes concorrentes reproduzíveis no CI.
- Mutation testing ou casos negativos para validações financeiras.
- Contract tests publicados para merchants.

## Autossuficiência
Este documento define o plano TDD e lacunas. Deve ser usado no roadmap sem depender da conversa.
