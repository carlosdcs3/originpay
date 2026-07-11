# OriginPay Architecture Blueprint

Data: 2026-07-09  
Escopo: blueprint arquitetural para refatoração futura do OriginPay.  
Regra desta etapa: **não mover arquivos, não refatorar código, não alterar regra de negócio**.

---

## 1. Objetivo

Este documento define a arquitetura final recomendada para o OriginPay.

Ele deve servir como guia definitivo para reorganizar o repositório em módulos claros, com responsabilidades explícitas, dependências controladas e separação limpa entre HTTP, domínio, aplicação, infraestrutura, interface administrativa, interface pública e assets.

O objetivo não é reproduzir a estrutura atual. O objetivo é propor o desenho final para a próxima refatoração.

---

## 2. Diagnóstico arquitetural atual

A auditoria de leitura identificou um monólito Laravel funcional, porém com responsabilidades misturadas.

### 2.1 Sinais principais do estado atual

- `app/` tem mais de 1000 arquivos PHP.
- `app/Http/Controllers` tem cerca de 188 arquivos.
- `app/Services` tem cerca de 227 arquivos, com serviços de domínio, infraestrutura, dashboard, workflow e integração no mesmo nível.
- `app/Models` concentra cerca de 182 models Eloquent.
- `resources/views` tem cerca de 664 views.
- `routes/admin.php` e `routes/web.php` concentram grande parte da definição de interface.
- Há módulos novos parcialmente organizados em `app/Domain`, mas o restante ainda usa organização Laravel tradicional.
- Há duplicidade conceitual entre `Gateway`, `Payment`, `Payments`, `Finance`, `Financial`, `Fees`, `Ledger`, `GatewayService`, `GatewayProvider`, etc.
- O backend/admin usa namespaces mistos: `Backend`, `Admin`, `Gateway`, `Finance`.
- Views públicas, views de usuário, views merchant, views admin e componentes ainda estão agrupados mais por origem histórica do que por bounded context.

### 2.2 Problemas que a arquitetura final deve resolver

1. Controllers grandes e acoplados a Models/Services diretamente.
2. Services com responsabilidades ambíguas: alguns são application services, outros domain services, outros infraestrutura, outros dashboard/read-model.
3. Duplicação de conceitos entre namespaces antigos e novos.
4. Ausência de camada `Actions` para casos de uso transacionais.
5. Policies, Events e Jobs existem, mas não seguem uma organização modular consistente.
6. Views e assets não estão claramente separados por superfície: Public, App, Admin, Checkout, Docs.
7. Rotas monolíticas dificultam leitura e ownership por módulo.
8. Integrações externas de PSP/gateway ainda aparecem em múltiplos lugares.
9. SDK/API/developer hub ainda mistura apresentação, documentação e operação.
10. Falta contrato claro entre módulos.

---

## 3. Princípios da arquitetura final

### 3.1 Monólito modular

O OriginPay deve continuar como monólito Laravel, mas organizado como **monólito modular por domínio**.

Motivo:

- O produto ainda compartilha autenticação, banco, filas, admin e modelos transacionais.
- Separar em microserviços agora aumentaria complexidade operacional.
- Modularizar internamente já resolve a maior parte do acoplamento.

### 3.2 Camadas

Cada módulo deve seguir esta separação:

```txt
Interface HTTP/UI
    -> Application / Actions
        -> Domain
            -> Infrastructure
```

Regras:

- Controller não contém regra de negócio.
- Controller chama Action/Application Service.
- Action orquestra transação, valida estado, dispara eventos/jobs.
- Domain contém entidades, policies de domínio, value objects, enums e regras puras.
- Infrastructure implementa banco, gateways, APIs externas, storage, queue drivers e adapters.
- Views só renderizam dados já preparados por ViewModels/DTOs/read models.

### 3.3 Dependências permitidas

```txt
Http -> Application -> Domain
Http -> ViewModels
Application -> Domain
Application -> Infrastructure contracts
Infrastructure -> Domain contracts
Jobs -> Application
Listeners -> Application
Policies -> Domain/Models
Views -> ViewModels only
```

Dependências proibidas:

```txt
Domain -> Http
Domain -> Views
Domain -> Controllers
Domain -> Infrastructure concrete
Models -> Controllers
Views -> Services complexos
Controllers -> Gateway adapters diretos
Controllers -> Jobs com montagem de regra complexa
```

---

## 4. Estrutura final recomendada

Estrutura alvo:

```txt
app/
  Modules/
    Core/
    Identity/
    Accounts/
    Merchants/
    Wallets/
    Payments/
    PaymentLinks/
    Checkout/
    Pix/
    Cards/
    Withdrawals/
    Ledger/
    Finance/
    Fees/
    Gateways/
    Webhooks/
    Risk/
    Compliance/
    Billing/
    Subscriptions/
    Notifications/
    Support/
    Connect/
    Platform/
    Cms/
    Developer/
  Shared/
    Application/
    Domain/
    Infrastructure/
    Http/
    UI/
```

Cada módulo pode seguir:

```txt
app/Modules/{Module}/
  Application/
    Actions/
    Queries/
    DTOs/
    Data/
    Services/
  Domain/
    Models/
    Entities/
    ValueObjects/
    Enums/
    Events/
    Policies/
    Contracts/
    Exceptions/
  Infrastructure/
    Persistence/
    Providers/
    Adapters/
    Clients/
    Repositories/
  Interface/
    Http/
      Controllers/
      Requests/
      Resources/
      Middleware/
    Console/
      Commands/
    Jobs/
    Listeners/
  Presentation/
    ViewModels/
    Components/
```

Observação: Laravel permite manter Models em `app/Models` durante a migração. O alvo final é que models centrais passem para módulos ou tenham aliases controlados.

---

## 5. Módulos finais

## 5.1 Core

Responsabilidade:

- Bootstrap de aplicação.
- Configuração global.
- Feature flags.
- Health check.
- Helpers compartilhados mínimos.
- Observability base.
- App settings.

Inclui:

- AppConfig.
- Settings.
- Health status.
- Platform status.
- Global enums.
- Shared exceptions.

Não inclui:

- Regra de pagamento.
- Regra financeira.
- Gateways.
- Views específicas.

Diretório alvo:

```txt
app/Modules/Core/
  Application/
  Domain/
  Infrastructure/
  Interface/Http/
```

---

## 5.2 Identity

Responsabilidade:

- Login.
- Guards.
- 2FA.
- Session/device management.
- API credentials.
- Password/transaction password.
- Auth logs.

Superfícies:

- Admin identity/security.
- User auth.
- Merchant auth.
- API/Sanctum credentials.

Actions recomendadas:

```txt
AuthenticateUserAction
EnableTwoFactorAction
DisableTwoFactorAction
RotateApiCredentialAction
RevokeApiCredentialAction
ValidateTransactionPasswordAction
```

Models relacionados:

```txt
User
Admin
Merchant
ApiCredential
LoginLog
DeviceSession
```

---

## 5.3 Accounts

Responsabilidade:

- Perfil de usuário.
- Conta normal.
- Preferências.
- Status da conta.
- Conversão user -> merchant quando aplicável.

Não deve conter:

- Ledger.
- Wallet transaction.
- Pagamentos.
- KYC profunda.

Actions:

```txt
UpdateUserProfileAction
ChangeAccountStatusAction
ConvertUserToMerchantAction
UpdateAccountFeatureStatusAction
```

---

## 5.4 Merchants

Responsabilidade:

- Cadastro/gestão de lojistas.
- Aprovação/rejeição.
- Perfil comercial.
- Configurações comerciais.
- Merchant dashboard read models.

Depende de:

- Identity para autenticação.
- Compliance para KYC/risk.
- Fees para precificação.
- Wallets/Ledger para saldos.

Actions:

```txt
ApproveMerchantAction
RejectMerchantAction
UpdateMerchantSettingsAction
CalculateMerchantDashboardAction
```

---

## 5.5 Wallets

Responsabilidade:

- Carteiras.
- Saldos disponíveis/reservados.
- Operações internas de wallet.
- Disponibilidade por moeda/método.

Regra crítica:

- Wallet não decide cobrança, saque ou pagamento.
- Wallet executa movimentos autorizados por Ledger/Payments/Withdrawals.

Actions:

```txt
CreateWalletAction
ReserveBalanceAction
ReleaseReservedBalanceAction
DebitWalletAction
CreditWalletAction
```

Contratos:

```txt
WalletBalanceReader
WalletMovementWriter
```

---

## 5.6 Ledger

Responsabilidade:

- Livro razão.
- Imutabilidade contábil.
- Lançamentos.
- Reconciliação base.
- Timeline financeira.
- Reservas e liberações.

Este módulo deve ser o centro da consistência financeira.

Regras:

- Nenhum módulo altera saldo diretamente sem passar por Ledger/Wallet contracts.
- Ledger events são fonte para read models financeiros.
- Ledger entries são append-only sempre que possível.

Actions:

```txt
RecordLedgerEntryAction
RecordChargeCapturedAction
RecordWithdrawalReservedAction
RecordWithdrawalReleasedAction
RecordFeeCollectedAction
RecordSettlementPaidAction
```

Events:

```txt
LedgerEntryRecorded
BalanceReserved
BalanceReleased
SettlementRecorded
```

---

## 5.7 Payments

Responsabilidade:

- Ciclo de vida de cobranças.
- Status de pagamento.
- Pix/card/boleto como métodos.
- Orquestração com Gateways.
- Idempotência de criação/captura.

Submódulos possíveis:

```txt
Payments/Charges
Payments/Refunds
Payments/Captures
Payments/Methods
```

Actions:

```txt
CreateChargeAction
CancelChargeAction
CaptureChargeAction
MarkChargeAsPaidAction
FailChargeAction
RefundChargeAction
```

Events:

```txt
ChargeCreated
ChargePaid
ChargeFailed
ChargeRefunded
```

Depende de:

- Gateways via contracts.
- Ledger para registros financeiros.
- Fees para cálculo de tarifas.
- Webhooks para entrega externa.
- Risk para decisão antifraude.

---

## 5.8 Pix

Responsabilidade:

- Pix charge.
- Pix key snapshot.
- Pix withdraw rail.
- QR Code.
- Webhook Pix/provider.

Pode ser submódulo de Payments ou módulo separado. Recomendado: módulo separado se regras Pix forem extensas.

Actions:

```txt
CreatePixChargeAction
GeneratePixQrCodeAction
ValidatePixKeyAction
SnapshotPixKeyAction
ProcessPixWebhookAction
```

---

## 5.9 Cards

Responsabilidade:

- Cartões virtuais.
- Cardholders.
- Card network.
- Solicitação/aprovação de cartão.
- Fees de cartão.

Actions:

```txt
RequestVirtualCardAction
ReviewVirtualCardRequestAction
UpdateVirtualCardStatusAction
ConfigureVirtualCardProviderAction
```

Depende de:

- Merchants/Accounts.
- Compliance.
- Fees.
- Gateways/Provider adapters.

---

## 5.10 Withdrawals

Responsabilidade:

- Solicitação de saque.
- Reserva de saldo.
- Aprovação automática/manual.
- Falha/liberação.
- Agendamento.
- Métodos de saque.

Actions:

```txt
CreateWithdrawalAction
ReserveWithdrawalBalanceAction
ApproveWithdrawalAction
RejectWithdrawalAction
FailWithdrawalAction
ReleaseWithdrawalReservationAction
ScheduleWithdrawalAction
```

Events:

```txt
WithdrawalRequested
WithdrawalReserved
WithdrawalApproved
WithdrawalFailed
WithdrawalReleased
```

Depende de:

- Wallets.
- Ledger.
- Risk.
- Gateways.
- Notifications.

---

## 5.11 Gateways

Responsabilidade:

- Contratos de PSP.
- Adapters de provedores.
- Health check de gateway.
- Routing/fallback.
- Capabilities.
- Credenciais.
- Normalização de responses.

Estrutura alvo:

```txt
app/Modules/Gateways/
  Domain/
    Contracts/
      PaymentGateway.php
      WithdrawalGateway.php
      GatewayHealthProbe.php
    ValueObjects/
      GatewayResponse.php
      GatewayCapability.php
  Infrastructure/
    Providers/
      Stripe/
      Efi/
      Cryptomus/
      Mollie/
    Routing/
    Credentials/
  Application/
    Actions/
      RoutePaymentAction.php
      CheckGatewayHealthAction.php
      UpdateGatewayCredentialsAction.php
```

Regras:

- Payments nunca chama SDK externo direto.
- Withdrawals nunca chama SDK externo direto.
- Gateway adapter retorna DTO normalizado.
- Credenciais ficam isoladas em Infrastructure/Credentials.

---

## 5.12 Fees

Responsabilidade:

- Tarifas globais.
- Tarifas por merchant.
- Tarifas por método.
- Tarifas de gateway.
- Simulação.
- Versionamento de regras de taxa.

Actions:

```txt
CalculateChargeFeeAction
CalculateWithdrawalFeeAction
UpdatePlatformFeeRuleAction
SimulateFeeAction
```

Depende de:

- Merchants.
- Payments/Withdrawals como consumidores.

Não depende de:

- Controllers.
- Gateways concretos.

---

## 5.13 Finance

Responsabilidade:

- Dashboards financeiros.
- Repasses.
- Liquidação.
- Chargebacks/disputes quando financeiro.
- Conciliação.
- Relatórios financeiros.

Importante:

- Finance não deve ser fonte de escrita primária do Ledger.
- Finance consome Ledger, Payments, Withdrawals, Fees e Gateways para montar visão operacional.

Submódulos:

```txt
Finance/Reconciliation
Finance/Settlements
Finance/Disputes
Finance/Reports
Finance/Dashboards
```

Actions:

```txt
RunReconciliationAction
CreateSettlementAction
PaySettlementAction
OpenDisputeAction
CloseDisputeAction
GenerateFinancialReportAction
```

---

## 5.14 Risk

Responsabilidade:

- Score de risco.
- Anomalias.
- Blacklist/whitelist.
- Regras de aprovação automática.
- Detecção de fraude.

Actions:

```txt
CalculateRiskScoreAction
EvaluatePaymentRiskAction
EvaluateWithdrawalRiskAction
ResolveAnomalyAction
AddToBlacklistAction
```

Depende de:

- Accounts.
- Merchants.
- Payments.
- Withdrawals.
- Ledger read models.

---

## 5.15 Compliance

Responsabilidade:

- KYC.
- Templates/documentos.
- Auditoria de compliance.
- LGPD/data access.
- Evidências regulatórias.

Actions:

```txt
SubmitKycAction
ReviewKycAction
DownloadKycDocumentAction
RecordComplianceAuditAction
```

---

## 5.16 Webhooks

Responsabilidade:

- Webhook endpoints de merchants.
- Assinatura.
- Dispatch.
- Retry.
- DLQ.
- Admin panel de webhooks.
- Ingestão de webhooks de PSP, quando aplicável.

Submódulos:

```txt
Webhooks/Outbound
Webhooks/Inbound
Webhooks/DLQ
Webhooks/Admin
```

Actions:

```txt
RegisterWebhookEndpointAction
RotateWebhookSecretAction
DispatchWebhookAction
VerifyWebhookSignatureAction
ReplayWebhookEventAction
ResolveWebhookDlqAction
```

Jobs:

```txt
DispatchWebhookJob
ReplayWebhookJob
ProcessInboundWebhookJob
```

Events consumidos:

```txt
ChargePaid
ChargeFailed
WithdrawalApproved
WithdrawalFailed
SettlementPaid
```

---

## 5.17 Billing

Responsabilidade:

- Planos.
- Assinaturas da plataforma.
- Faturamento SaaS.
- Uso de funcionalidades.
- Cobrança de plano.

Actions:

```txt
CreatePlanAction
UpdatePlanAction
SubscribeMerchantAction
CancelSubscriptionAction
RecordFeatureUsageAction
```

---

## 5.18 Subscriptions

Responsabilidade:

- Assinaturas de clientes finais/links recorrentes.
- Invoices de assinatura.
- Ciclo de cobrança recorrente.

Separar de Billing:

- Billing = cobrança da plataforma OriginPay para seus clientes/merchants.
- Subscriptions = produto de recorrência oferecido pelo merchant ao cliente final.

---

## 5.19 Notifications

Responsabilidade:

- Notificações internas.
- Templates.
- Email/SMS/Twilio.
- Central de notificações.
- Preferences.

Actions:

```txt
SendNotificationAction
RenderNotificationTemplateAction
MarkNotificationReadAction
NotifyUsersAction
```

---

## 5.20 Support

Responsabilidade:

- Tickets.
- Inbox.
- Support chat.
- Categorias.
- Macros/knowledge base.

Actions:

```txt
OpenTicketAction
ReplyTicketAction
CloseTicketAction
SendSupportMessageAction
MarkConversationResolvedAction
```

---

## 5.21 Connect

Responsabilidade:

- Campanhas.
- Jornadas.
- Templates.
- Delivery adapters.
- Operações de comunicação conectada.

Esse módulo já aparece mais organizado atualmente e deve virar referência de modularização.

Estrutura alvo:

```txt
Connect/
  Campaign/
  Journey/
  Template/
  Delivery/
  Operations/
```

---

## 5.22 Platform

Responsabilidade:

- Admin platform.
- Feature flags.
- Versioning.
- Changelog.
- Command Center.
- Alerts.
- Disaster recovery.
- Queue/Scheduler monitoring.

Actions:

```txt
ToggleFeatureFlagAction
CreatePlatformAlertAction
RunHealthCheckAction
TriggerChaosAction
```

---

## 5.23 Cms / Public Site

Responsabilidade:

- Landing page pública.
- Páginas institucionais.
- Docs públicas.
- Blog.
- Footer/navigation/SEO.
- Custom landing admin, se mantido.

Separar:

```txt
Cms/PublicSite
Cms/Docs
Cms/MarketingPages
Cms/CustomLandings
```

Regra:

- A landing atual OriginPay deve ser tratada como PublicSite, não como produto financeiro.
- Custom landings continuam suspeitas/legado até decisão futura.

---

## 5.24 Developer

Responsabilidade:

- Developer hub.
- API docs.
- API Explorer/Sandbox.
- SDK público.
- Logs de API do merchant.
- Webhook simulator.

Depende de:

- Identity/API credentials.
- Webhooks.
- Payments API.

Assets relacionados:

```txt
resources/js/originpay-sdk
public/sdk
resources/views/frontend/pages/docs
```

---

## 6. Organização de Controllers

### 6.1 Estrutura final

```txt
app/Http/Controllers/
  Public/
  App/
  Admin/
  Api/
  Webhooks/
```

Ou, preferencialmente dentro dos módulos:

```txt
app/Modules/Payments/Interface/Http/Controllers/
app/Modules/Gateways/Interface/Http/Controllers/
app/Modules/Webhooks/Interface/Http/Controllers/
```

### 6.2 Responsabilidade dos Controllers

Controller deve:

- Receber request.
- Validar via FormRequest.
- Chamar Action/Query.
- Retornar View/Json/Redirect.

Controller não deve:

- Calcular saldo.
- Montar regra de taxa.
- Chamar SDK externo.
- Fazer reconciliação.
- Criar muitos Models diretamente.
- Controlar fluxo transacional complexo.

### 6.3 Padrão recomendado

```php
public function store(CreateChargeRequest $request, CreateChargeAction $action)
{
    $charge = $action->execute($request->toDto(), $request->user());

    return redirect()->route('user.charge.show', $charge);
}
```

---

## 7. Organização de Services

### 7.1 Problema atual

`app/Services` mistura:

- Application services.
- Domain services.
- Dashboard services.
- Gateway services.
- Infrastructure services.
- Helpers transacionais.
- Orquestradores.

### 7.2 Regra final

Usar `Services` apenas quando o objeto representa uma capacidade duradoura, não um caso de uso único.

Preferir:

```txt
Actions/ para casos de uso
Queries/ para leitura complexa
DTOs/Data/ para transporte de dados
Domain/Services/ para regra pura de domínio
Infrastructure/Services/ para integrações e adapters
```

### 7.3 Classificação final

```txt
Application/Actions
  CreateChargeAction
  ApproveWithdrawalAction
  DispatchWebhookAction

Application/Queries
  ListChargesQuery
  MerchantDashboardQuery
  GatewayHealthQuery

Domain/Services
  FeeCalculator
  RiskScorer
  LedgerInvariantChecker

Infrastructure/Services
  StripeClient
  EfiPixClient
  TwilioNotificationSender
```

---

## 8. Organização de Actions

`app/Actions` atualmente está vazio. A arquitetura final deve introduzi-lo como camada central de casos de uso.

Padrão:

```txt
app/Modules/{Module}/Application/Actions/{Verb}{Object}Action.php
```

Exemplos:

```txt
Payments/Application/Actions/CreateChargeAction.php
Withdrawals/Application/Actions/ApproveWithdrawalAction.php
Gateways/Application/Actions/RoutePaymentAction.php
Webhooks/Application/Actions/ReplayWebhookEventAction.php
Ledger/Application/Actions/RecordLedgerEntryAction.php
```

Regras:

- Uma Action representa um caso de uso completo.
- Actions podem abrir transação de banco.
- Actions podem disparar Events.
- Actions podem enfileirar Jobs.
- Actions não renderizam view.
- Actions não conhecem Controller.

---

## 9. Organização de Jobs

### 9.1 Estrutura final

```txt
app/Modules/{Module}/Interface/Jobs/
```

Ou temporariamente:

```txt
app/Jobs/{Module}/
```

### 9.2 Regra

Job deve ser fino.

Job pode:

- Receber IDs ou DTO serializável.
- Chamar Action.
- Controlar retry/backoff.

Job não deve:

- Conter regra de negócio extensa.
- Montar queries complexas de dashboard.
- Chamar view.

Exemplo:

```php
class ReplayWebhookJob implements ShouldQueue
{
    public function handle(ReplayWebhookEventAction $action): void
    {
        $action->execute($this->eventId);
    }
}
```

---

## 10. Organização de Events e Listeners

### 10.1 Events

Events devem representar fatos de domínio já ocorridos:

```txt
ChargePaid
WithdrawalApproved
LedgerEntryRecorded
WebhookDispatchFailed
KycApproved
MerchantApproved
```

Não usar Events para comandos.

Errado:

```txt
ProcessChargeRequested
```

Melhor:

```txt
ChargeCreated
```

### 10.2 Listeners

Listeners devem reagir a fatos:

- Enviar notificação.
- Atualizar read model.
- Disparar webhook.
- Registrar auditoria.

Não devem conter regra principal do caso de uso.

---

## 11. Organização de Policies

Policies devem ficar junto do módulo dono.

```txt
app/Modules/Webhooks/Domain/Policies/WebhookEndpointPolicy.php
app/Modules/Payments/Domain/Policies/ChargePolicy.php
app/Modules/Withdrawals/Domain/Policies/WithdrawalPolicy.php
```

Regras:

- Policy decide permissão, não executa ação.
- Policy pode consultar ownership, role, permission e estado simples.
- Regra operacional complexa vai para Domain/Application.

---

## 12. Organização de Components

### 12.1 Blade components

Separar componentes por superfície:

```txt
resources/views/components/
  shared/
  public/
  admin/
  app/
  checkout/
  docs/
```

Componentes PHP:

```txt
app/View/Components/
  Shared/
  Admin/
  App/
```

### 12.2 Regra para ícones

A falha recente mostrou colisão entre `<x-icon>` local e packages WireUI/Phosphor.

Decisão arquitetural:

- `x-icon` deve ser componente local oficial.
- Registrar explicitamente no `AppServiceProvider`.
- Packages externos não devem capturar alias genérico usado pelo produto.
- Se usar WireUI/Phosphor, preferir aliases específicos, nunca `x-icon`.

Padrão final:

```php
Blade::component('icon', \App\View\Components\Icon::class);
```

---

## 13. Organização de Views

### 13.1 Estrutura final

```txt
resources/views/
  public/
    landing/
    pages/
    docs/
    blog/
  app/
    dashboard/
    wallet/
    charges/
    payment-links/
    withdrawals/
    settings/
    developer/
  merchant/
    dashboard/
    onboarding/
    settings/
  admin/
    dashboard/
    finance/
    gateways/
    payments/
    withdrawals/
    webhooks/
    compliance/
    risk/
    platform/
    support/
    cms/
  checkout/
    payment-link/
    charge/
  components/
  emails/
  errors/
```

### 13.2 Naming final

Evitar mistura atual:

```txt
frontend.user
frontend.merchant
backend.*
admin.*
general.*
```

Alvo:

```txt
public.*
app.*
merchant.*
admin.*
checkout.*
components.*
```

### 13.3 ViewModels

Views complexas devem receber ViewModels/DTOs.

```txt
AdminFinanceDashboardViewModel
MerchantChargeListViewModel
WebhookEventDetailViewModel
```

Controller não deve montar array grande manualmente.

---

## 14. Organização de Assets

### 14.1 Estrutura final em resources

```txt
resources/
  js/
    app/
    admin/
    public/
    checkout/
    sdk/
  css/
    app.css
    admin.css
    public.css
    checkout.css
  assets/
    icons/
    images/
    models/
```

### 14.2 Estrutura final em public

```txt
public/
  build/              # gerado pelo Vite
  sdk/                # SDK versionado público
  static/             # estático público versionado/manual
  uploads/            # se realmente público
```

Evitar:

- Assets duplicados em `public/frontend`, `public/general`, `public/backend` sem ownership claro.
- SVGs de ícone espalhados sem registro.
- Uploads misturados com assets de produto.

### 14.3 Vite

Blueprint:

```txt
resources/js/public.ts
resources/js/app.ts
resources/js/admin.ts
resources/js/checkout.ts
resources/css/public.css
resources/css/app.css
resources/css/admin.css
resources/css/checkout.css
```

---

## 15. Organização de Routes

### 15.1 Estrutura final

```txt
routes/
  public.php
  app.php
  merchant.php
  admin.php
  api.php
  webhooks.php
  checkout.php
  console.php
```

Ou por módulo:

```txt
app/Modules/Payments/routes/api.php
app/Modules/Payments/routes/web.php
app/Modules/Webhooks/routes/admin.php
```

### 15.2 Route ownership

- `public.php`: landing, páginas públicas, docs, blog.
- `app.php`: painel usuário autenticado.
- `merchant.php`: painel lojista.
- `admin.php`: operação/admin.
- `checkout.php`: `/pay`, checkout público, payment links.
- `api.php`: API pública versionada.
- `webhooks.php`: inbound PSP, callbacks externos.

### 15.3 Regra

`routes/admin.php` não deve ter 500+ linhas no final da refatoração. O arquivo pode apenas carregar módulos:

```php
Route::prefix($adminPrefix)->as('admin.')->group(function () {
    require __DIR__.'/admin/finance.php';
    require __DIR__.'/admin/gateways.php';
    require __DIR__.'/admin/webhooks.php';
});
```

---

## 16. Organização de API

### 16.1 Estrutura final

```txt
app/Modules/Api/
  V1/
    Payments/
    Charges/
    Webhooks/
    Wallets/
    Customers/
```

Ou por módulo:

```txt
Payments/Interface/Http/Api/V1/
Webhooks/Interface/Http/Api/V1/
```

### 16.2 Regras

- API Controller retorna Resource/JSON DTO, não View.
- API usa FormRequest.
- API chama Action.
- API usa idempotency por padrão em criação de recursos financeiros.
- API versionada sempre.

---

## 17. Organização de Database

### 17.1 Migrations

Manter migrations existentes. Para novas migrations:

```txt
database/migrations/{year}_{module}_{change}.php
```

Exemplo:

```txt
2026_07_09_payments_add_idempotency_key_to_charges.php
```

### 17.2 Seeders/Factories

Organizar por módulo:

```txt
database/seeders/Payments/
database/factories/Payments/
```

### 17.3 Banco como contrato

- Tabelas financeiras devem ser tratadas como contrato crítico.
- Refatoração de Models não deve renomear tabelas no mesmo passo.
- Criar camada de compatibilidade antes de mover classes críticas.

---

## 18. Organização de Tests

### 18.1 Estrutura final

```txt
tests/
  Feature/
    Public/
    App/
    Admin/
    Api/
    Checkout/
  Modules/
    Payments/
    Withdrawals/
    Ledger/
    Gateways/
    Webhooks/
    Fees/
    Risk/
  Unit/
    Domain/
    Actions/
    Services/
```

### 18.2 Regras

- Cada Action crítica deve ter teste direto.
- Controllers devem ter teste de rota/permissão/resposta.
- Domain services devem ter testes unitários sem banco quando possível.
- Ledger/Wallet/Withdrawal precisam de testes de invariantes.
- Gateways usam fake adapters.

---

## 19. Dependências entre módulos

Mapa final recomendado:

```txt
PublicSite -> nenhum domínio financeiro direto
Checkout -> PaymentLinks, Payments, Customers, Risk
Developer -> Identity, Webhooks, Api, Payments
Accounts -> Identity
Merchants -> Accounts, Compliance, Fees
Wallets -> Ledger
Payments -> Gateways, Fees, Ledger, Risk, Webhooks
Withdrawals -> Wallets, Ledger, Gateways, Risk, Notifications
Ledger -> Core
Finance -> Ledger, Payments, Withdrawals, Fees, Gateways
Fees -> Merchants, Core
Gateways -> Core
Webhooks -> Payments, Withdrawals, Merchants, Developer
Risk -> Accounts, Merchants, Payments, Withdrawals, Ledger
Compliance -> Accounts, Merchants
Billing -> Merchants, Payments
Subscriptions -> Payments, Customers
Notifications -> Core, Identity
Support -> Accounts, Merchants, Notifications
Platform -> Core, Finance, Gateways, Queues
Cms -> Core
```

Regra de ouro:

```txt
Módulos operacionais podem consumir módulos financeiros por Queries/ReadModels.
Módulos de domínio financeiro não devem depender de UI/Admin/CMS.
```

---

## 20. Contratos principais

### 20.1 Gateway contracts

```php
interface PaymentGateway
{
    public function createCharge(CreateGatewayChargeData $data): GatewayChargeResult;
    public function cancelCharge(string $externalId): GatewayResult;
    public function refundCharge(string $externalId, Money $amount): GatewayResult;
}

interface WithdrawalGateway
{
    public function createWithdrawal(CreateGatewayWithdrawalData $data): GatewayWithdrawalResult;
    public function getWithdrawalStatus(string $externalId): GatewayWithdrawalStatus;
}
```

### 20.2 Ledger contracts

```php
interface LedgerWriter
{
    public function record(LedgerEntryData $entry): LedgerEntry;
}

interface BalanceReader
{
    public function availableFor(AccountId $accountId, Currency $currency): Money;
}
```

### 20.3 Webhook contracts

```php
interface WebhookSigner
{
    public function sign(string $payload, string $secret): string;
    public function verify(string $payload, string $signature, string $secret): bool;
}

interface WebhookDispatcher
{
    public function dispatch(WebhookEventData $event): DispatchResult;
}
```

---

## 21. Refatoração recomendada por fases

### Fase 0 — Congelar estado atual

- Manter `_archive_legacy_review` intacto.
- Manter testes passando.
- Não mover Models críticos.
- Criar blueprint e ADRs.

### Fase 1 — Introduzir Actions sem mover estrutura

- Criar `app/Actions` ou `app/Modules/*/Application/Actions`.
- Controllers passam a chamar Actions novas.
- Services antigos viram dependências internas das Actions.
- Testar cada Action.

Baixo risco.

### Fase 2 — Modularizar Services

- Classificar serviços atuais em Application/Domain/Infrastructure/Query.
- Mover apenas serviços com teste.
- Criar namespaces de compatibilidade se necessário.

Risco médio.

### Fase 3 — Separar Gateways

- Definir contracts.
- Isolar adapters PSP.
- Remover chamadas diretas de PSP dos módulos financeiros.
- Criar fake gateways para testes.

Risco alto, fazer com testes.

### Fase 4 — Consolidar Ledger/Wallet/Finance

- Definir Ledger como fonte de consistência.
- Remover escrita direta de saldo fora dos Actions autorizados.
- Criar invariantes.

Risco alto.

### Fase 5 — Reorganizar Views/Assets

- Separar public/app/merchant/admin/checkout.
- Migrar Vite entries.
- Reduzir `public/frontend`, `public/backend`, `public/general` gradualmente.

Risco visual, validar no browser.

### Fase 6 — Modularizar Routes

- Quebrar `admin.php` e `web.php` por domínio.
- Manter nomes de rotas.
- Testar `route:list` e navegação.

Risco médio.

### Fase 7 — Reorganizar Models

- Última etapa.
- Criar aliases se necessário.
- Evitar mudar tabelas junto com namespace.
- Rodar suíte completa.

Risco alto.

---

## 22. Decisões arquiteturais obrigatórias

1. OriginPay será monólito modular Laravel.
2. Actions serão a camada oficial de casos de uso.
3. Domain não depende de HTTP/UI/Infrastructure concreta.
4. Gateway adapters ficam isolados por contracts.
5. Ledger é fonte de consistência financeira.
6. Finance monta visão operacional, não substitui Ledger.
7. Views recebem ViewModels/DTOs, não Services complexos.
8. `x-icon` é componente local oficial.
9. Routes serão divididas por superfície/módulo.
10. Assets serão divididos por public/app/admin/checkout/sdk.
11. Refatoração deve ser incremental, sempre com testes.
12. Arquivos suspeitos/legados não devem ser deletados sem segunda auditoria.

---

## 23. Convenções de nomenclatura

### Actions

```txt
VerbObjectAction
CreateChargeAction
ApproveWithdrawalAction
ReplayWebhookEventAction
```

### Queries

```txt
ListObjectsQuery
GetObjectDetailsQuery
BuildDashboardQuery
```

### DTOs/Data

```txt
CreateChargeData
GatewayChargeResult
WebhookEventData
```

### Events

```txt
ObjectPastTense
ChargePaid
WithdrawalApproved
LedgerEntryRecorded
```

### Jobs

```txt
VerbObjectJob
DispatchWebhookJob
ReconcileGatewayTransactionsJob
```

### Controllers

```txt
ObjectController
ChargeController
WebhookEndpointController
Admin\Finance\LedgerController
```

---

## 24. Checklist para cada módulo refatorado

Antes de considerar um módulo migrado:

- [ ] Tem README/ADR curto explicando responsabilidade.
- [ ] Controllers chamam Actions/Queries.
- [ ] Requests validam entrada.
- [ ] Actions têm teste.
- [ ] Domain services não dependem de HTTP/UI.
- [ ] Jobs são finos.
- [ ] Events representam fatos.
- [ ] Policies testadas quando houver permissão crítica.
- [ ] Views recebem ViewModel/DTO.
- [ ] Assets têm ownership claro.
- [ ] `php artisan test` passa.
- [ ] `php artisan route:list` passa.
- [ ] `npm run build` passa se mexeu em assets/views.

---

## 25. Próximos documentos recomendados

1. `docs/adr/0001-modular-monolith.md`
2. `docs/adr/0002-actions-as-use-cases.md`
3. `docs/adr/0003-ledger-as-financial-source-of-truth.md`
4. `docs/adr/0004-gateway-contracts.md`
5. `docs/refactor/phases.md`
6. `docs/refactor/module-map.md`
7. `docs/refactor/risk-register.md`

---

## 26. Próximo passo técnico recomendado

Não mover arquivos ainda.

Próxima etapa segura:

1. Criar ADRs.
2. Criar mapa `current -> target` por arquivo/módulo.
3. Escolher um módulo pequeno para piloto, recomendado: `Webhooks` ou `Fees`.
4. Introduzir Actions mantendo paths atuais.
5. Rodar suíte completa.

Módulos que não devem ser o primeiro piloto:

- Ledger.
- Wallets.
- Payments.
- Withdrawals.
- Gateways.

Motivo: risco financeiro alto.

---

## 27. Resumo executivo

A arquitetura final do OriginPay deve ser um monólito modular Laravel orientado a domínio.

O centro transacional deve ser composto por:

```txt
Payments + Withdrawals + Wallets + Ledger + Fees + Gateways + Risk + Webhooks
```

A camada administrativa deve operar por cima desses módulos, sem conter regra financeira própria.

A camada pública/landing/docs deve ficar separada do produto financeiro.

A refatoração deve começar por Actions e contracts, não por movimentação massiva de arquivos.

Este blueprint é a referência para a próxima etapa de refatoração.
