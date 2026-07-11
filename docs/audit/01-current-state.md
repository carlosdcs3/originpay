<!-- ORIGINPAY AUDIT | generated 2026-07-10T03:24:28 | source: static code inspection | scope: documentation only -->

# 01 — Auditoria do Estado Atual

## Escopo e método
Auditoria estática do repositório `E:\projetos\DigiKash v1.0.5\DigiKash v1.0.5`, com aplicação principal em `core/`. Não foram alterados arquivos de produto. PHP não está disponível no PATH do ambiente de auditoria, portanto `php artisan route:list` e `php artisan test` não puderam ser executados; as conclusões devem ser confirmadas em runtime antes de release.

## Evidências macro
- Stack: Laravel 11, PHP ^8.3, Pest 2, Sanctum, Horizon, Reverb, Vite 5, TailwindCSS, Alpine.js.
- Contagem estática: 188 controllers, 182 models, 227 services, 79 arquivos em `app/Gateway`, 39 em `app/Payment`, 8 em `app/Modules`, 198 migrations, 102 arquivos de teste.
- Rotas: `routes/admin.php`, `api.php`, `auth.php`, `channels.php`, `connect.php`, `console.php`, `web.php`.
- Arquitetura: monólito Laravel com camadas Domain/Services/Gateway/Payment/Modules coexistindo.

## Classificação por área
| Área | Status | Evidências | Risco |
|---|---|---|---|
| Arquitetura | Parcial | `app/Domain`, `app/Services`, `app/Gateway`, `app/Payment`, `app/Modules` coexistem. | Duplicidade e boundary fraco. |
| APIs | Parcial | `routes/api.php` expõe charges/payments/refunds/payouts/balance/customers/subscriptions/payment-methods/health. | Necessita validação runtime e contrato final. |
| Dashboards | Parcial | Controllers admin/backend: Dashboard, Ops, Finance, Gateway, SystemHealth, Billing. | Muitas telas podem ser placeholder/redirect. |
| Checkout | Parcial | `PublicPaymentLinkController`, `MerchantPaymentReceiveController`, rotas `/pay/{slug}`, `/payment/checkout`, `/payment/process`. | Fluxos web e API duplicados. |
| Login | Parcial | `routes/auth.php`, middleware web/admin, 2FA/lock_screen. | Necessita teste ofensivo e rate limit real. |
| Cadastro | Parcial | Auth/user/merchant controllers existem. | Confirmar validações e aprovação merchant. |
| Merchant | Parcial | `Merchant` model, developer portal, API credentials, webhooks. | Segredos e chaves em múltiplos modelos/camadas. |
| Admin | Parcial/risco | `routes/admin.php` grande; usa `$retiredAdminModule`. | Rotas mortas/legadas e ações financeiras sensíveis. |
| Pix | Parcial | EFI Pix/webhooks, PixKey, Boleto/Pix rotas, EFI tests. | Webhook EFI específico não mostra validação de assinatura local antes de consultar PSP. |
| Boleto | Parcial | `BoletoController`, `BoletoChargeTest`. | Confirmar liquidação/cancelamento/reconciliação. |
| Cartão | Parcial | Stripe, payment methods, virtual card. | Escopo cartão/gateway não consolidado. |
| Crypto | Legado/parcial | `Cryptomus`, `Coinbase`, `Coinpayments`, `NowPayments`, `Binance`. | Providers legados podem estar incompletos. |
| Wallet | Parcial | `Wallet`, `WalletService`, testes de integridade. | Campos `balance` fillable em create; proteção parcial em update. |
| Ledger | Parcial forte | `LedgerService`, ledger tests, HMAC verification command. | Confirmar imutabilidade real e migrações. |
| Fees | Parcial | PlatformFee services/controllers/tests e `app/Modules/Fees`. | Duplicidade entre Services e Modules. |
| Settlement | Parcial | Settlement controllers/services/tests. | Confirmar atomicidade e reconciliação real. |
| Reconciliation | Parcial | comandos `Reconcile*`, dashboards, tests. | Alguns comentários indicam verificação hipotética de DLQ. |
| Developer portal | Parcial | API keys, webhooks, logs, sandbox, docs. | Model `Merchant` contém chaves aparentes em claro. |
| Webhooks | Parcial/risco alto | 3 caminhos: `Api\WebhookController`, `Api\V1\Webhooks\EfiWebhookController`, `Webhook\GatewayWebhookController`; DLQ múltipla. | Duplicidade, replay, reprocessamento sem headers originais. |
| Connect | Parcial | `routes/connect.php`, services, jobs, adapters Twilio/AWS SES/Meta. | Grande superfície; precisa teste end-to-end. |
| CMS | Parcial | Pages, components, custom landing, navigation, footer, SEO. | Rotas `withoutMiddleware('XSS')` em HTML customizado exigem controles compensatórios. |

## Código morto / legado / duplicado
- Camada legada de pagamentos: `app/Payment/*` com diversos gateways tradicionais.
- Camada moderna: `app/Gateway/*` e `app/Payment/Modern/*` coexistem.
- Provider EFI aparece em `app/Gateway/Providers/Efi`, `app/Gateway/Providers/EfiProvider.php`, `app/Payment/Efi`, `app/Payment/Modern/Providers/EfiGateway.php`.
- Rotas admin `retired`: `routes/admin.php` usa `$retiredAdminModule` e redirects de módulos antigos.
- Scripts perigosos/operacionais no root: `fix_balances.js`, `fix_balances.php`, `fix_webhooks.js`, `scratch_fix_routes.php`, `refactor.py`, `rebrand.php`, `create_backup.php`, auditorias JSON/TXT.
- Dados fake/mock: `MockGatewayAdapter.php`, `DummyCircuitBreaker.php`, `OpsGenerateMockData.php`, `TestGatewayFlow.php`, `NewProvider*`, strings `mock_*` em comandos/testes.

## Bloqueios para produção
1. Executar aplicação e testes em ambiente PHP 8.3 real.
2. Definir source of truth para gateways e desativar/arquivar caminhos legados.
3. Resolver duplicidade de webhooks/DLQ/idempotência.
4. Auditar segredos, API keys e armazenamento de credenciais.
5. Validar que todos os fluxos financeiros usam lock/transação/idempotência.

## Autossuficiência
Este documento é a fonte oficial da Fase 1. Próximas fases devem usar os achados acima como base e abrir somente arquivos necessários para confirmar segurança, testes, resiliência e roadmap.
