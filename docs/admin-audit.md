# Auditoria Completa do Painel Administrativo - OriginPay Enterprise

Data da auditoria: 2026-06-27  
Escopo analisado: `core/routes/admin.php`, `core/routes/web.php`, `core/routes/auth.php`, controllers `App\Http\Controllers\Admin` e `App\Http\Controllers\Backend`, views `resources/views/admin`, `resources/views/backend`, `config/admin_menus.php`, middlewares, policies, permissões e componentes Blade.

## Sumario Executivo

O painel administrativo possui uma base extensa, mas heterogenea. Existem telas Enterprise recentes com `x-admin.*`, DTOs e services, convivendo com CRUDs legados baseados em Bootstrap/CoreUI, Eloquent direto no controller, CSS/JS inline e modais parciais. O arquivo ativo `core/routes/admin.php` concentra a maior parte das paginas e tambem contem duplicidades de rotas/namespaces que tornam a ordem de registro critica.

Principais achados:

- Admin ativo e protegido em `bootstrap/app.php` por `web`, `auth:admin`, `verified`, `XSS`, `lock_screen`, `2fa`, `demo` e `AdminAuditMiddleware`.
- Auth admin separado em `routes/auth.php`; paginas admin em `routes/admin.php`; `routes/web.php` nao possui paginas admin do painel, mas contem rota publica critica `/reset-admin-pwd`.
- `config/admin_menus.php` referencia 39 entradas visiveis no menu, mas ha dezenas de paginas acessiveis apenas por rota.
- Ha duplicidade real de nomes de rota: `admin.gateway.monitor.index`, `admin.gateway.logs`, `admin.finance.refunds`, `admin.finance.chargebacks`, `admin.finance.reconciliation`; a ultima definicao tende a prevalecer no roteamento nomeado.
- Ha duas familias de Design System: `x-admin.*` e `x-ds.*`; o uso ainda e parcial. Apenas o dominio Finance usa `x-admin.*` de forma consistente.
- Foram encontrados pelo menos 11 placeholders explicitos: `admin.stub`, API Keys, API Docs, Reports, System Queues, Compliance audit/blacklist/whitelist/fraud_engine/risk_score, Gateway Fallback, Platform pages e pontos "Em breve" em dashboard/billing/gateway routing.
- Cerca de 100 arquivos Blade em `resources/views/admin` e `resources/views/backend` contem `<script>`, `<style>` ou `style=`, indicando forte acoplamento visual/comportamental nas views.
- Policies admin sao escassas: apenas `LanguagePolicy` usa `Admin`; `MerchantPolicy` e orientada ao usuario final.

## Fontes de Rota e Seguranca

| Arquivo | Papel | Observacoes |
|---|---|---|
| `core/bootstrap/app.php` | Registro das rotas | Carrega `routes/auth.php` e `routes/admin.php`; aplica middlewares ao Admin. |
| `core/routes/auth.php` | Login, senha, 2FA e lock screen admin | Prefixo `setting('admin_prefix')`, nome `admin.*`, middleware `XSS`; subgrupo autenticado para lock/logout/2FA. |
| `core/routes/admin.php` | Paginas e acoes do painel | Fonte principal do inventario; mistura closures, controllers antigos e controllers Enterprise. |
| `core/routes/web.php` | Frontend/public/user | Nao contem paginas internas do Admin, mas contem `/reset-admin-pwd`, risco critico fora do escopo funcional do painel. |

Middlewares relevantes:

- `auth:admin`, `verified`, `lock_screen`, `2fa`: acesso administrativo.
- `XSS`: sanitizacao por `strip_tags` em POST/PUT/PATCH.
- `demo`: bloqueia alteracoes quando `config('app.demo')` esta ativo.
- `AdminAuditMiddleware`: registra POST/PUT/PATCH/DELETE no canal `audit`, mas nao grava em tabela de auditoria.
- `AdminTenantBypassMiddleware`: existe, mas nao aparece aplicado ao grupo admin ativo.

Permissoes:

- Varios controllers legados estendem `Backend\BaseController`, que transforma `permissions()` em middleware `permission:*`.
- Muitos controllers novos (`Admin\*`, `Backend\Finance\*`, `GatewayManagerController`, `WebhookAdminController`, `SystemHealthController`) nao estendem `BaseController` e dependem de autorizacao manual ou apenas do grupo admin.
- `config/admin_menus.php` suporta permissao por item, mas a maioria dos itens nao declara `permission`.

## Componentes do Design System

Componentes encontrados:

- `resources/views/components/admin`: `page-hero`, `kpi-card`, `kpi-grid`, `data-table`, `drawer`, `empty-state`, `smart-filter`, `alerts-area`, `timeline`, `json-viewer`.
- `resources/views/components/ds`: `page`, `card`, `table`, `stat-card`, `filter-bar`, `pagination`, `empty-state`, `badge`, `action-dropdown`, `skeleton`.

Uso observado:

- `x-admin.*`: concentrado em Finance Enterprise (`backend/finance/*`) e alguns indices novos.
- `x-ds.*`: dashboard, KYC, merchants, usuarios, gateway charges/withdrawals, gateway logs/monitor.
- Legado: a maioria dos CRUDs usa HTML/Bootstrap/CoreUI direto, partials, modais e scripts inline.

## Inventario por Dominio

Legenda de maturidade: 0% quebrada/nao encontrada; 25% placeholder; 50% parcial; 75% funcional; 100% production ready.

### Dashboard e Operacoes

| Nome | URL | Controller | View Blade | Estado | Tipo | Dependencias | Origem dos dados | Problemas | Maturidade |
|---|---|---|---|---|---|---|---|---|---|
| Dashboard > Visao Geral | `/admin` | `Backend\DashboardController@index` | `backend.dashboard.index` | Parcial | Dashboard | `AdminDashboardMetricsService`, `PaymentGateway`, `WebhookDeadLetter`, `FinancialReconciliation`, `Ticket`, `Transaction` | Service + Model + Query Builder | Controller ainda faz queries diretas; KPI de tarifas "Em breve"; mistura DS e queries inline | 75% |
| Operacoes > Ops Dashboard | `/admin/ops-dashboard` | `Admin\OpsDashboardController@index` | `admin.ops.dashboard` | Parcial | Dashboard | Redis, Cache, `Gateway`, `FinancialReconciliationService`, `PlatformIncident` | Model + Redis + Cache + Mock | Usa `App\Models\Gateway` enquanto outras telas usam `PaymentGateway`; "Horizon Placeholder"; autorizacao manual | 50% |
| Operacoes > Command Center | `/admin/command-center` | `Backend\CommandCenterController@index` | `backend.operations.command_center` | Funcional | Monitoramento | `PlatformAlert`, `WebhookDeadLetter`, DB jobs, `PaymentGateway`, `Transaction` | Model + Query Builder + Mock | Controller faz queries diretas; `recentAlerts = []` mock; sem service dedicado | 75% |
| Operacoes > Alertas | `/admin/alerts` | `Backend\AlertController@index` | `backend.operations.alerts` | Funcional | Monitoramento | `PlatformAlert` | Model | Fallback para collection vazia se model nao existir; sem ActionService | 75% |
| Operacoes > Incidentes | `/admin/ops/incidents` | `Backend\OpsIncidentController@index` | `backend.ops.incidents` | Funcional | Monitoramento | `PlatformIncident` | Model | Query no controller; sem DTO; rota fica dentro de prefix controller diferente | 75% |
| Operacoes > API Metrics | `/admin/ops/metrics/api` | `Admin\OpsController@getApiMetrics` | JSON | Funcional | Sistema | `ApiMetricsService` | Service | Endpoint JSON acessivel por rota; nao e pagina visual | 75% |
| Operacoes > Gateway Metrics | `/admin/ops/metrics/gateways` | `Admin\OpsController@getGatewayMetrics` | JSON | Funcional | Sistema | `GatewayMetricsService`, `PaymentGateway` | Service + Model | Query no controller para gateways ativos | 75% |
| Operacoes > Queue Metrics | `/admin/ops/metrics/queues` | `Admin\OpsController@getQueueMetrics` | JSON | Funcional | Sistema | `QueueMonitorService`, `DlqMonitorService` | Service | Sem view | 75% |
| Operacoes > Scheduler Metrics | `/admin/ops/metrics/scheduler` | `Admin\OpsController@getSchedulerMetrics` | JSON | Funcional | Sistema | `SchedulerMonitorService` | Service | Sem view | 75% |
| Operacoes > Maintenance | `/admin/ops/maintenance` | `Admin\OpsController@getMaintenanceWindows` | JSON | Parcial | Sistema | `MaintenanceWindow` | Model | Sem pagina visual; query direta | 50% |
| Operacoes > SLA Metrics | `/admin/ops/metrics/sla` | `Admin\OpsController@getSlaMetrics` | JSON | Funcional | Sistema | `SlaMonitorService` | Service | Sem view | 75% |
| Operacoes > Cost Metrics | `/admin/ops/metrics/costs` | `Admin\OpsController@getPlatformCosts` | JSON | Funcional | Sistema | `PlatformCostService` | Service | Sem view | 75% |
| Operacoes > Feature Usage | `/admin/ops/metrics/features` | `Admin\OpsController@getFeatureUsage` | JSON | Funcional | Sistema | `FeatureUsageService` | Service | Sem view | 75% |
| Operacoes > Circuit Breaker | `/admin/ops/metrics/circuit-breaker` | `Admin\OpsController@getCircuitBreakerStates` | JSON | Funcional | Sistema | `CircuitBreakerService`, `GatewayHealthScoreService` | Service | Sem view | 75% |

### Finance

| Nome | URL | Controller | View Blade | Estado | Tipo | Dependencias | Origem dos dados | Problemas | Maturidade |
|---|---|---|---|---|---|---|---|---|---|
| Finance > Ledger Enterprise | `/admin/finance/ledger` | `Backend\Finance\LedgerController@index` ou `Backend\FinanceController@ledger` | `backend.finance.ledger.index` ou `backend.finance.ledger` | Parcial | Relatorio | `LedgerDashboardService`, `FinanceAlertService`, `WalletTransaction`, Cache | Service + DTO ou Model | Duas rotas com mesmo path/names diferentes; nome de menu aponta para rota legada; drawer com placeholder/JS inline | 75% |
| Finance > Ledger Engine | `/admin/ledger` | `Admin\LedgerController@index` | `backend.finance.ledger.index` | Funcional | Relatorio | `WalletTransaction` | Model | Controller faz query direta; sem DashboardService; conflita conceitualmente com Finance Ledger | 50% |
| Finance > Ledger Timeline | `/admin/ledger/timeline/{charge_id}` | `Admin\LedgerController@timeline` | `backend.finance.ledger.timeline` | Funcional | Monitoramento | `Charge`, `WalletTransaction`, `ProcessedEvent` | Model | Query direta; sem DTO; rota escondida | 75% |
| Finance > Reconciliations Enterprise | `/admin/finance/reconciliations` | `Backend\Finance\ReconciliationController@index` | `backend.finance.reconciliations.index` | Funcional | Relatorio | `Finance\ReconciliationDashboardService` | Service + DTO | Rota fora do menu; duplicada conceitualmente com `/finance/reconciliation` | 75% |
| Finance > Reconciliation Legado | `/admin/finance/reconciliation` | `Backend\FinanceController@reconciliation` | `backend.finance.reconciliation` | Parcial | Relatorio | `ReconciliationDashboardService` | Service | Nome duplicado declarado duas vezes; view tem drawer placeholder | 50% |
| Finance > Transactions Enterprise | `/admin/finance/transactions` | `Backend\Finance\TransactionController@index` | `backend.finance.transactions.index` | Funcional | Relatorio | `Finance\TransactionDashboardService` | Service + DTO | Rota fora do menu; duplicada com transacoes globais | 75% |
| Finance > Transacoes Globais | `/admin/transaction` | `Backend\TransactionController@index` | `backend.transaction.index` | Funcional | Relatorio | `Transaction` facade/service antigo | Model/Service legado | Sem DTO; HTML legado; filtros basicos | 75% |
| Finance > Chargebacks Enterprise | `/admin/finance/chargebacks` | `Backend\Finance\ChargebackController@index` ou `Backend\FinanceController@chargebacks` | `backend.finance.chargebacks.index` ou `backend.finance.chargebacks` | Parcial | Monitoramento | `ChargebackDashboardService`, `ChargebackActionService` | Service + DTO | Nome duplicado declarado duas vezes; menu aponta para versao legada; tabs placeholder | 75% |
| Finance > Settlements | `/admin/finance/settlements` | `Backend\Finance\SettlementController@index` | `backend.finance.settlements.index` | Funcional | Relatorio | `SettlementDashboardService`, `SettlementActionService` | Service + DTO | Rota fora do menu; acao pay separada | 75% |
| Finance > Fees Enterprise | `/admin/finance/fees` | `Backend\Finance\FeeController@index` | `backend.finance.fees.index` | Funcional | Relatorio | `FeeDashboardService`, `FeeActionService` | Service + DTO | Rota fora do menu; sobrepoe gateway fees conceitualmente | 75% |
| Finance > Fees Legado | sem rota GET ativa direta | `Backend\FinanceController@fees` | `backend.finance.fees` | Nao encontrada | Relatorio | `FeeDashboardService` | Service + DTO | Metodo existe sem rota GET ativa em `admin.php` | 0% |
| Finance > Wallets/Balances | `/admin/finance/balances` | `Backend\FinanceController@balances` | `backend.finance.balances` | Parcial | Monitoramento | `WalletDashboardService` | Service + DTO | Drawer/acoes simuladas; CSS/JS inline; sem ActionService para ajuste visualizado | 50% |
| Finance > Repasses | `/admin/finance/repasses` | `Backend\FinanceController@repasses` | `backend.finance.repasses` | Parcial | Relatorio | `SettlementDashboardService` | Service + DTO | Tabs placeholder; nomenclatura duplicada com settlements | 50% |
| Finance > Liquidacao | `/admin/finance/liquidacao` | `Backend\FinanceController@liquidacao` | `backend.finance.liquidacao` | Placeholder | Relatorio | Nenhuma | Sem dados | View de 15 linhas; sem dados; nao usa DS completo | 25% |
| Finance > Refunds | `/admin/finance/refunds` | `Backend\FinanceController@refunds` | `backend.finance.refunds` | Placeholder | Relatorio | Nenhuma | Sem dados | Nome de rota duplicado; view sem controller real/service | 25% |
| Finance > Charges | sem rota finance ativa | `Backend\FinanceController@charges` | `backend.finance.charges` | Nao encontrada | Relatorio | `ChargeDashboardService` | Service + DTO | Metodo e view existem, mas rota ativa usa Gateway Charges | 0% |
| Finance > Withdrawals | sem rota finance ativa | `Backend\FinanceController@withdrawals` | `backend.finance.withdrawals` | Nao encontrada | Relatorio | `WithdrawalDashboardService` | Service + DTO | Metodo e view existem, mas menu usa Gateway Withdrawals/Withdraw History | 0% |
| Finance > Webhooks Finance | sem rota ativa | `Backend\FinanceController@webhooks` | `backend.finance.webhooks` | Nao encontrada | Monitoramento | `WebhookEvent` | Model | Metodo existe sem rota no `admin.php` | 0% |
| Finance > Gateway Fees | `/admin/gateway-fees` | `Backend\GatewayFeeController@index` | `backend.gateway_fees.index` | Funcional | Configuracao | `GatewayFeeConfig`/configs, `GatewayFeeService` no ecossistema | Model | Simulador com JS inline; fora do padrao Enterprise | 75% |
| Finance > Gateway Fee Edit | `/admin/gateway-fees/{id}/edit` | `Backend\GatewayFeeController@edit` | `backend.gateway_fees.edit` | Funcional | Configuracao | Config de taxas | Model | CSS/JS inline; simulador acoplado a view | 75% |
| Finance > Depositos Manuais | `/admin/deposit/manual-request` | `Backend\DepositController@manualRequest` | `backend.deposit.manual_request` | Funcional | CRUD | `Transaction`, `DepositMethod` | Model | Controller legado; sem DTO; partials/modais | 75% |
| Finance > Historico de Depositos | `/admin/deposit/history` | `Backend\DepositController@history` | `backend.deposit.history` | Funcional | Relatorio | `Transaction` | Model | Sem service; filtros parciais | 75% |
| Finance > Metodos de Deposito | `/admin/deposit/method` | `Backend\DepositMethodController@index` | `backend.deposit.method.index` | Funcional | Configuracao | `DepositMethod`, `PaymentGateway` | Model | View pequena com modais; sem DS Enterprise | 75% |
| Finance > Saques Manuais | `/admin/withdraw/manual-request` | `Backend\WithdrawController@manualRequest` | `backend.withdraw.manual_request` | Funcional | CRUD | `Transaction`, `WithdrawMethod` | Model | Controller legado; sem DTO/ActionService | 75% |
| Finance > Historico de Saques | `/admin/withdraw/history` | `Backend\WithdrawController@history` | `backend.withdraw.history` | Funcional | Relatorio | `Transaction` | Model | Sem service; filtros parciais | 75% |
| Finance > Metodos de Saque | `/admin/withdraw/method` | `Backend\WithdrawMethodController@index` | `backend.withdraw.method.index` | Funcional | Configuracao | `WithdrawMethod`, `PaymentGateway` | Model | Sem DS Enterprise; CRUD legado | 75% |
| Finance > Agenda de Saque | `/admin/withdraw/schedule` | `Backend\WithdrawScheduleController@index` | `backend.withdraw.schedule.index` | Funcional | Configuracao | `WithdrawSchedule` | Model | Usa `all()`; sem paginacao; sem service | 50% |

### Gateway

| Nome | URL | Controller | View Blade | Estado | Tipo | Dependencias | Origem dos dados | Problemas | Maturidade |
|---|---|---|---|---|---|---|---|---|---|
| Gateway > Providers | `/admin/payment/gateway` | `Backend\PaymentGatewayController@index` | `backend.payment_gateway.index` | Funcional | CRUD | `PaymentGateway` | Model | CRUD legado; settings Enterprise parcial | 75% |
| Gateway > Overview | `/admin/payment/gateway/{id}/overview` | `Backend\PaymentGatewayController@settings` | `backend.payment_gateway.settings` + `tabs.overview` | Parcial | Configuracao | `PaymentGateway`, Gateway definitions, deposit/withdraw methods | Model + Definition | Todas as tabs usam mesmo controller; algumas tabs sao rasas/placeholders | 50% |
| Gateway > Credentials | `/admin/payment/gateway/{id}/credentials` | `PaymentGatewayController@settings` | `backend.payment_gateway.tabs.credentials` | Funcional | Configuracao | Gateway credentials/schema | Model + Definition | Muito HTML na view; risco de segredo em formularios; JS inline | 75% |
| Gateway > Charge Methods | `/admin/payment/gateway/{id}/charge-methods` | `PaymentGatewayController@settings` | `backend.payment_gateway.tabs.charge-methods` | Parcial | Configuracao | Payment methods | Model | Campos fixos; sem DTO | 50% |
| Gateway > Withdraw Methods | `/admin/payment/gateway/{id}/withdraw-methods` | `PaymentGatewayController@settings` | `backend.payment_gateway.tabs.withdraw-methods` | Parcial | Configuracao | Withdraw methods | Model | Campos dinamicos na view; sem componente dedicado | 50% |
| Gateway > Fees Limits | `/admin/payment/gateway/{id}/fees-limits` | `PaymentGatewayController@settings` | `backend.payment_gateway.tabs.fees-limits` | Parcial | Configuracao | Taxes/limits | Model | Regras financeiras no controller; sem ActionService | 50% |
| Gateway > Webhooks Tab | `/admin/payment/gateway/{id}/webhooks` | `PaymentGatewayController@settings` | `backend.payment_gateway.tabs.webhooks` | Placeholder | Monitoramento | Nenhuma clara | Sem dados | View de 12 linhas; sem eventos reais | 25% |
| Gateway > Health Tab | `/admin/payment/gateway/{id}/health` | `PaymentGatewayController@settings` | `backend.payment_gateway.tabs.health` | Placeholder | Monitoramento | Nenhuma clara | Sem dados | View de 15 linhas; sem health service | 25% |
| Gateway > Routing Tab | `/admin/payment/gateway/{id}/routing` | `PaymentGatewayController@settings` | `backend.payment_gateway.tabs.routing` | Parcial | Configuracao | Routing data | Model | Sobrepoe `/admin/gateway/routing`; sem ownership claro | 50% |
| Gateway > Logs Tab | `/admin/payment/gateway/{id}/logs` | `PaymentGatewayController@settings` | `backend.payment_gateway.tabs.logs` | Placeholder | Monitoramento | Nenhuma clara | Sem dados | View de 12 linhas | 25% |
| Gateway > Routing/Prioridades | `/admin/gateway/routing` | `Backend\GatewayManagerController@routing` | `backend.gateway.routing` | Parcial | Configuracao | `PaymentOperation`, `RoutingStrategy`, `PaymentMethodRoute`, `PaymentGateway`, `GatewayHealthScoreService`, `CircuitBreakerService`, `GatewayLog` | Model + Service | Controller faz varias queries; estrategias marcadas "Em breve"; sem DTO | 75% |
| Gateway > Fallback | `/admin/gateway/fallback` | `Backend\GatewayManagerController@fallback` | `backend.gateway.fallback` | Placeholder | Configuracao | `PaymentGateway` | Model | Alert informativo e pouca funcionalidade | 25% |
| Gateway > Capabilities | `/admin/gateway/capabilities` | `GatewayManagerController@capabilities` | `backend.gateway.capabilities` | Funcional | Relatorio | `PaymentGateway` | Model | Query direta; sem filtro/paginacao | 50% |
| Gateway > Connectivity | `/admin/gateway/connectivity` | `GatewayManagerController@connectivity` | `backend.gateway.connectivity` | Funcional | Monitoramento | `PaymentGateway` | Model | Query direta; sem health real por provider | 50% |
| Gateway > Monitor | `/admin/gateway/monitor` | `GatewayManagerController@monitor` ou `Gateway\GatewayMonitorController@index` | `backend.gateway.monitor` | Parcial | Monitoramento | `PaymentGateway`, `GatewayLog`, health stats | Model + Query Builder | Nome de rota duplicado; controller efetivo por nome pode ser o ultimo; menu duplica Health e Monitor para mesma rota | 50% |
| Gateway > Monitor Detalhe | `/admin/gateway/monitor/{id}` | `GatewayManagerController@show` | `backend.gateway.show` | Parcial | Monitoramento | `PaymentGateway`, `Transaction`, mock latency/uptime | Model + Mock | Usa `rand()`, `recentErrors=[]`; sem service | 50% |
| Gateway > Logs | `/admin/gateway/logs` | `GatewayManagerController@logs` ou `Gateway\GatewayLogController@index` | `backend.gateway.logs` | Parcial | Monitoramento | `GatewayLog` | Model | Nome duplicado; `GatewayManagerController::logs` nao aparece implementado no trecho lido; risco de rota quebrada se primeira prevalecer | 50% |
| Gateway > Charges | `/admin/gateway/charges` | `Gateway\AdminChargeController@index` | `backend.gateway.charges.index` | Funcional | Relatorio | Charges/transactions | Model | Query direta; sem ActionService; usa `x-ds` parcial | 75% |
| Gateway > Charge Detalhe | `/admin/gateway/charges/{id}` | `Gateway\AdminChargeController@show` | `backend.gateway.charges.show` | Funcional | Monitoramento | Charge | Model | Sem DTO; rota escondida | 75% |
| Gateway > Withdrawals | `/admin/gateway/withdrawals` | `Gateway\AdminWithdrawalController@index` | `backend.gateway.withdrawals.index` | Funcional | Relatorio | Withdrawals | Model | Query direta; sem ActionService | 75% |
| Gateway > Withdrawal Detalhe | `/admin/gateway/withdrawals/{id}` | `Gateway\AdminWithdrawalController@show` | `backend.gateway.withdrawals.show` | Funcional | Monitoramento | Withdrawal | Model | Rota escondida | 75% |
| Gateway > Digisynk | `/admin/gateway/digisynk` | `Backend\DigisynkGatewayController@index` | `backend.gateway.digisynk` | Parcial | Configuracao | Nenhuma clara | Mock/Sem dados | Muito CSS inline; parece pagina institucional/config incompleta | 50% |

### Compliance e Risco

| Nome | URL | Controller | View Blade | Estado | Tipo | Dependencias | Origem dos dados | Problemas | Maturidade |
|---|---|---|---|---|---|---|---|---|---|
| Compliance > Dashboard | `/admin/compliance` | `Admin\ComplianceController@index` | `backend.compliance.dashboard` | Parcial | Dashboard | `KycForm`, `FraudLog`, `PlatformIncident` | Model + fallback | Controller query direto; botoes "Analisar (Em breve)" | 50% |
| Compliance > Risco | `/admin/compliance/risk-score` | `Backend\ComplianceRiskController@riskScore` | `backend.compliance.risk_score` | Placeholder | Monitoramento | Nenhuma | Sem dados | View de 15 linhas; "Em breve" | 25% |
| Compliance > Fraud Engine | `/admin/compliance/fraud-engine` | `ComplianceRiskController@fraudEngine` | `backend.compliance.fraud_engine` | Placeholder | Monitoramento | Nenhuma | Sem dados | "Em breve"; sem engine wiring | 25% |
| Compliance > Anomalias | `/admin/compliance/anomalies` | `ComplianceRiskController@anomalies` | `backend.compliance.anomalies` | Parcial | Monitoramento | `FinancialAnomaly` se existir | Model + Stub | Comentario indica stub; sem service de risco | 50% |
| Compliance > Blacklist | `/admin/compliance/blacklist` | `ComplianceRiskController@blacklist` | `backend.compliance.blacklist` | Placeholder | Configuracao | Nenhuma | Sem dados | "Em breve" | 25% |
| Compliance > Whitelist | `/admin/compliance/whitelist` | `ComplianceRiskController@whitelist` | `backend.compliance.whitelist` | Placeholder | Configuracao | Nenhuma | Sem dados | "Em breve" | 25% |
| Compliance > Auditoria | `/admin/compliance/audit` | `ComplianceRiskController@audit` | `backend.compliance.audit` | Placeholder | Relatorio | Nenhuma | Sem dados | "Em breve"; sem dossies/RTS | 25% |
| Compliance > Activity Log | `/admin/activity-log` | `Backend\ActivityController@index` | `backend.activity.index` | Funcional | Relatorio | `LoginActivity` | Model | Apenas login/activity; nao substitui audit trail admin | 75% |
| Compliance > Legacy Compliance Index | sem rota ativa clara | `Backend\ComplianceController@index` | `backend.compliance.index` | Nao encontrada | Dashboard | `AccountRestriction`, `BlacklistedPixKey` etc. | Model | Controller/view existem, mas rota ativa `/admin/compliance` aponta para `Admin\ComplianceController` | 0% |

### Usuarios, Lojistas e KYC

| Nome | URL | Controller | View Blade | Estado | Tipo | Dependencias | Origem dos dados | Problemas | Maturidade |
|---|---|---|---|---|---|---|---|---|---|
| Usuarios > Clientes | `/admin/user` | `Backend\UserController@index` | `backend.user.index` | Funcional | CRUD | `User` | Model | Controller legado; DS parcial; sem service/DTO | 75% |
| Usuarios > Ativos | `/admin/user/active` | `UserController@activeUser` | `backend.user.index` | Funcional | Relatorio | `User` | Model | Reusa view; filtros por metodo | 75% |
| Usuarios > Suspensos | `/admin/user/suspended` | `UserController@suspendedUser` | `backend.user.index` | Funcional | Relatorio | `User` | Model | Reusa view | 75% |
| Usuarios > Email Nao Verificado | `/admin/user/unverified` | `UserController@unverifiedUser` | `backend.user.index` | Funcional | Relatorio | `User` | Model | Reusa view | 75% |
| Usuarios > KYC Nao Verificado | `/admin/user/kyc-unverified` | `UserController@kycUnverifiedUser` | `backend.user.index` | Funcional | Relatorio | `User` | Model | Reusa view | 75% |
| Usuarios > Gerenciar Usuario | `/admin/user/manage/{username}/{param?}` | `Backend\UserManageController@manageUser` | `backend.user.manage.*` | Funcional | CRUD | `User`, `TransactionData`, `Referral`, `Ticket`, `LoginActivity`, `KycSubmission`, `UserFeature` | Model + Data | Muitos handlers no controller; ActionService ausente para acoes sensiveis | 75% |
| Usuarios > Estatisticas JSON | `/admin/user/{id}/transaction-stats` | `UserController@transactionStats` | JSON | Funcional | Relatorio | `Transaction` | Model | Endpoint auxiliar; sem view | 75% |
| Lojistas > Todos | `/admin/merchant` | `Backend\MerchantController@index` | `backend.merchant.index` | Funcional | CRUD | `Merchant` | Model | Policy existente e para user, nao admin; controller legado | 75% |
| Lojistas > Pendentes | `/admin/merchant/pending` | `MerchantController@pendingMerchant` | `backend.merchant.index` | Funcional | Relatorio | `Merchant` | Model | Reusa view | 75% |
| Lojistas > Aprovados | `/admin/merchant/approved` | `MerchantController@approvedMerchant` | `backend.merchant.index` | Funcional | Relatorio | `Merchant` | Model | Reusa view | 75% |
| Lojistas > Rejeitados | `/admin/merchant/rejected` | `MerchantController@rejectedMerchant` | `backend.merchant.index` | Funcional | Relatorio | `Merchant` | Model | Reusa view | 75% |
| KYC > Todas Solicitações | `/admin/kyc/index` | `Backend\KycController@index` | `backend.kyc.index` | Funcional | CRUD | `KycSubmission`/KYC models | Model | DS parcial; request action no controller | 75% |
| KYC > Pendentes | `/admin/kyc/pending` | `KycController@pending` | `backend.kyc.pending` | Funcional | CRUD | KYC models | Model | View legada; sem service de decisao apesar de existir `KycDecisionService` | 75% |
| KYC > Templates | `/admin/kyc/template` | `Backend\KycTemplateController@index` | `backend.kyc.template.index` | Funcional | CRUD | KYC templates | Model | BaseController permissions; partials e JS inline | 75% |
| KYC > Edit Template | `/admin/kyc/template/{id}/edit` | `KycTemplateController@edit` | `backend.kyc.template.edit` | Funcional | CRUD | KYC templates | Model | View legada | 75% |
| Virtual Card > Lista | `/admin/virtual-card/list` | `Backend\VirtualCardController@virtualCardList` | `backend.virtual_card.list` | Funcional | CRUD | `VirtualCard`, `VirtualCardProvider` | Model | Sem service; controller faz filtros | 75% |
| Virtual Card > Requests Awaiting | `/admin/virtual-card/requests/awaiting` | `VirtualCardController@requestAwaiting` | `backend.virtual_card.awaiting` | Funcional | CRUD | `VirtualCardRequest`, providers | Model | Sem ActionService para review | 75% |
| Virtual Card > Requests All | `/admin/virtual-card/requests/all` | `VirtualCardController@requestAll` | `backend.virtual_card.all` | Funcional | Relatorio | `VirtualCardRequest` | Model | Sem DTO | 75% |
| Virtual Card > Cardholders | `/admin/virtual-card/cardholders` | `Backend\CardholdersController@index` | `backend.virtual_card.cardholder.index` | Funcional | CRUD | Cardholders | Model | Controller legado; sem service | 75% |
| Virtual Card > Provider | `/admin/virtual-card/provider` | `VirtualCardController@provider` | `backend.virtual_card.provider` | Funcional | Configuracao | `VirtualCardProvider` | Model | Sem service; partial manage | 75% |
| Virtual Card > Fee Settings | `/admin/virtual-card/fee-settings` | `VirtualCardFeeSettingController@index` | `backend.virtual_card.fee_settings.index` | Funcional | Configuracao | `VirtualCardFeeSetting`, `VirtualCardProvider`, `Currency` | Model | Usa `all()` para providers/currencies; sem DTO | 75% |
| Ranking | `/admin/ranking` | `Backend\UserRankController@index` | `backend.user_rank.index` | Funcional | Configuracao | `UserRank` | Model | Usa `all()`; CRUD legado | 75% |
| Indicacoes | `/admin/referral/index` | `Backend\ReferralController@index` | `backend.referral.index` | Funcional | Configuracao | `ReferralReward`, content | Model | Sem service apesar de `ReferralService` existir | 75% |

### API, Webhooks e Desenvolvedores

| Nome | URL | Controller | View Blade | Estado | Tipo | Dependencias | Origem dos dados | Problemas | Maturidade |
|---|---|---|---|---|---|---|---|---|---|
| API > Keys | `/admin/api-dev/keys` | `Backend\ApiController@keys` | `backend.api.keys` | Placeholder | Configuracao | Nenhuma | Sem dados | "Em breve"; sem CRUD de chaves | 25% |
| API > Docs | `/admin/api-dev/docs` | `Backend\ApiController@docs` | `backend.api.docs` | Placeholder | Sistema | Nenhuma | Sem dados | View de 17 linhas; sem gerador OpenAPI conectado | 25% |
| API > Logs | `/admin/api-dev/logs` | `Backend\ApiLogController@index` | `backend.api.logs` | Funcional | Relatorio | `ApiLog` | Model | Query direta; filtros basicos | 75% |
| Webhooks > Eventos/DLQ | `/admin/webhooks` | `Backend\WebhookAdminController@index` | `backend.webhooks.index` | Funcional | Monitoramento | `WebhookEvent`, `WebhookDlq` | Model | Controller concentra query, replay e auditoria; sem service em algumas acoes | 75% |
| Webhooks > Evento | `/admin/webhooks/events/{id}` | `WebhookAdminController@showEvent` | `backend.webhooks.show` | Funcional | Monitoramento | `WebhookEvent`, `MaskHelper` | Model | Rota escondida; sem policy granular | 75% |
| Webhooks > DLQ | `/admin/webhooks/dlqs/{id}` | `WebhookAdminController@showDlq` | `backend.webhooks.show_dlq` | Funcional | Monitoramento | `WebhookDlq`, `MaskHelper` | Model | Rota escondida | 75% |

### Sistema, Configuracoes e Acesso

| Nome | URL | Controller | View Blade | Estado | Tipo | Dependencias | Origem dos dados | Problemas | Maturidade |
|---|---|---|---|---|---|---|---|---|---|
| Sistema > Health | `/admin/system/health` | `Backend\SystemHealthController@index` | `backend.system.health` | Funcional | Monitoramento | DB, Cache, Queue, Storage, Scheduler, Gateway checks | Service/Query Builder | Controller grande; grava auditoria em model; sem DTO claro | 75% |
| Sistema > Queues | `/admin/system/queues` | `Backend\SystemAdminController@queues` | `backend.system.queues` | Placeholder | Monitoramento | Nenhuma | Sem dados | "Em breve" | 25% |
| Sistema > App Info | `/admin/app` | `Backend\AppController@appInfo` | `backend.app.info` | Funcional | Sistema | App/config | Config | View curta; ok para info | 75% |
| Sistema > Control Panel | `/admin/app/control-panel` | `AppController@controlPanel` | `backend.app.control-panel` | Funcional | Sistema | `config/admin_menus.php`, session permissions | Config + Session | Calcula menu no controller; sem cache; depende de route existence | 75% |
| Sistema > Style Manager | `/admin/app/style-manager` | `AppController@styleManager` | `backend.app.style_manager` | Funcional | Configuracao | `CustomCode` | Model | Edita CSS; risco operacional; sem preview robusto | 75% |
| Sistema > Optimize | `/admin/app/optimize` | `AppController@optimize` | redirect | Funcional | Sistema | Artisan/cache | Comando | Acao GET que altera estado; risco arquitetural | 50% |
| Sistema > Clear Cache | `/admin/app/clear-cache` | `AppController@clearCache` | redirect | Funcional | Sistema | Cache/Artisan | Comando | Acao GET que altera estado | 50% |
| Sistema > Empresa/Site Settings | `/admin/settings/site` | `Backend\SettingController@index` | `backend.settings.site.index` | Funcional | Configuracao | settings config/store | Config + Model | View grande com partials; JS inline | 75% |
| Sistema > Platform Fee | `/admin/settings/platform-fee` | `Backend\PlatformFeeController@index` | `backend.settings.platform_fee` | Funcional | Configuracao | `PlatformFeeService`, audits | Service + Model | Requer motivo; bom, mas ainda view/controller acoplados | 75% |
| Sistema > Plugins | `/admin/settings/plugin` | `Backend\PluginController@index` | `backend.settings.plugin.index` | Funcional | Configuracao | Plugins/config | Model/Config | CRUD legado; partials | 75% |
| Sistema > Plugin Type | `/admin/settings/{plugin_type}` | `PluginController@pluginType` | partial/config | Funcional | Configuracao | Plugins | Model/Config | Rota catch-all pode capturar paths futuros em settings | 50% |
| Sistema > Staff | `/admin/staff` | `Backend\StaffController@index` | `backend.staff.index` | Funcional | CRUD | `Admin`, `Role` | Model + Spatie | Sem create page separada; modal; BaseController permissions | 75% |
| Sistema > Roles | `/admin/role` | `Backend\RoleController@index` | `backend.role.index` | Funcional | CRUD | Spatie `Role`, `Permission` | Model | Permissions ok, mas sem agrupamento Enterprise | 75% |
| Sistema > Role Create | `/admin/role/create` | `RoleController@create` | `backend.role.create` | Funcional | CRUD | Spatie Permission | Model | View antiga | 75% |
| Sistema > Role Edit | `/admin/role/{id}/edit` | `RoleController@edit` | `backend.role.edit` | Funcional | CRUD | Spatie Permission | Model | View antiga | 75% |
| Sistema > Currency | `/admin/currency` | `Backend\CurrencyController@index` | `backend.currencies.index` | Funcional | Configuracao | `Currency`, roles | Model | Controller pesado; sem service | 75% |
| Sistema > Languages | `/admin/language` | `Backend\LanguageController@index` | `backend.languages.index` | Funcional | Configuracao | `Language` | Model | Policy so protege delete; views antigas | 75% |
| Sistema > Translate | `/admin/language/translate/{code}` | `LanguageController@translate` | `backend.languages.translate` | Funcional | Configuracao | Lang files/groups | File + Model | Manipulacao em controller; sem service dedicado | 75% |
| Sistema > Identity Sessions | `/admin/identity/sessions` | `IdentitySecurityController@sessions` | `backend.identity.sessions` | Funcional | Monitoramento | Sessions/logs | Model/DB | Sem service; rota fora do menu | 50% |
| Sistema > Login Logs | `/admin/identity/login-logs` | `IdentitySecurityController@loginLogs` | `backend.identity.login_logs` | Funcional | Relatorio | Login logs | Model | Sem service | 50% |
| Sistema > SSO/2FA | `/admin/identity/sso-2fa` | `IdentitySecurityController@sso2fa` | `backend.identity.sso_2fa` | Placeholder | Configuracao | Nenhuma | Sem dados | View de 15 linhas | 25% |
| Sistema > Devices | `/admin/identity/devices` | `IdentitySecurityController@devices` | `backend.identity.devices` | Placeholder | Monitoramento | Nenhuma | Sem dados | View de 15 linhas | 25% |
| Sistema > Profile | `/admin/profile/profile` | `Backend\AdminController@profile` | `backend.profile.index` | Funcional | Sistema | `TwoFactorService`, Admin auth | Model + Service | OK; partials antigas | 75% |

### CMS, Marketing e Conteudo

| Nome | URL | Controller | View Blade | Estado | Tipo | Dependencias | Origem dos dados | Problemas | Maturidade |
|---|---|---|---|---|---|---|---|---|---|
| Marketing > Campanhas | `/admin/stub?title=...` | Closure | `backend.stub.index` | Placeholder | Sistema | Nenhuma | Sem dados | Stub generico em rota publica admin | 25% |
| CMS > Pages | `/admin/page/site` | `Backend\PageController@index` | `backend.page.index` | Funcional | CRUD | `Page`, components, locales | Model | CRUD legado; muitos partials | 75% |
| CMS > Page Create/Edit | `/admin/page/site/create`, `/admin/page/site/{id}/edit` | `PageController@create/edit` | `backend.page.create/edit` | Funcional | CRUD | Page components/locales | Model | View curta inclui partials complexas; JS inline | 75% |
| CMS > Components | `/admin/page/component` | `PageComponentController@index` | `backend.page_component.index` | Funcional | CRUD | `PageComponentService`, models | Service + Model | XSS desabilitado em resource; risco controlado por Purifier? | 75% |
| CMS > Footer Sections | `/admin/page/footer/section` | `FooterSectionController@index` | `backend.page_footer.sections.index` | Funcional | CRUD | Footer sections/locales | Model | Modais/partials; sem DS Enterprise | 75% |
| CMS > Footer Items | `/admin/page/footer/item` | `FooterItemController@index` | `backend.page_footer.items.index` | Funcional | CRUD | Footer items/pages/socials | Model | Modais/partials | 75% |
| CMS > Navigation | `/admin/navigation/site` | `NavigationController@index` | `backend.navigation.index` | Funcional | CRUD | `Navigation`, `Page`, locales | Model | JS inline; position update por POST | 75% |
| CMS > Blog Posts | `/admin/blog/post` | `BlogController@index` | `backend.blog.index` | Funcional | CRUD | `Blog`, `BlogCategory`, `Language` | Model | XSS desabilitado; PurifyTrait no controller | 75% |
| CMS > Blog Categories | `/admin/blog/category` | `BlogCategoryController@index` | `backend.blog.categories.index` | Funcional | CRUD | `BlogCategory`, `Language` | Model | Modais/partials | 75% |
| CMS > Landing Pages | `/admin/custom-landing` | `CustomLandingController@index` | `backend.landings.index` | Funcional | CRUD | Landing files/pages | Model + File | Manipula HTML; XSS sem middleware em update | 75% |
| CMS > Landing HTML Manager | `/admin/custom-landing/{id}/manage-html` | `CustomLandingController@manageHtml` | `backend.landings.manage_html` | Funcional | Configuracao | Filesystem | File + Model | Edicao HTML direta; risco operacional | 75% |
| Marketing > Notifications | `/admin/notifications` | `NotificationController@index` | `backend.notifications.index` | Funcional | Sistema | Notifications | Model | GET para mark read; sem REST rigoroso | 75% |
| Marketing > Notify Users | `/admin/notifications/to-users` | `NotificationController@notifyUsers` | `backend.notifications.notify_users` | Funcional | Wizard | `NotifyUsers` job/notifications | Job + Model | Sem template DTO; permissao granular incerta | 75% |
| Marketing > Notification Templates | `/admin/notifications/template` | `NotificationTemplateController@index` | `backend.notifications.template.index` | Funcional | Configuracao | Notify templates | Model | CRUD parcial por channel | 75% |
| Marketing > SEO | `/admin/site-seo` | `SiteSeoController@index` | `backend.site_seo.index` | Funcional | Configuracao | SEO models/pages/locales | Model | CRUD legado | 75% |
| Marketing > Social | `/admin/social` | `SocialController@index` | `backend.social.index` | Funcional | Configuracao | Social links | Model | Modais/partials | 75% |
| Marketing > Subscribers | `/admin/subscriber` | `SubscriberController@index` | `backend.subscriber.index` | Funcional | Relatorio | Subscribers | Model | Send mail no controller/job?; view legada | 75% |

### Suporte e Atendimento

| Nome | URL | Controller | View Blade | Estado | Tipo | Dependencias | Origem dos dados | Problemas | Maturidade |
|---|---|---|---|---|---|---|---|---|---|
| Suporte > Chat | `/admin/support-chat` | `SupportChatAdminController@index` | `backend.support_chat.index` | Funcional | Monitoramento | Conversations/messages/users | Model | Controller grande; polling/JSON endpoints no mesmo controller | 75% |
| Suporte > Chat Detalhe | `/admin/support-chat/{conversationId}` | `SupportChatAdminController@show` | `backend.support_chat.show` | Funcional | Monitoramento | Conversation/messages | Model | JS inline; rota escondida | 75% |
| Suporte > Inbox | `/admin/inbox` | `SupportInboxController@index` | `backend.support.inbox` | Placeholder | Sistema | Nenhuma | Sem dados | View curta; Epic 6 incompleto | 25% |
| Suporte > Inbox Unread | `/admin/inbox/unread` | `SupportInboxController@unread` | `backend.support.unread` | Placeholder | Sistema | Nenhuma | Sem dados | View curta | 25% |
| Suporte > Inbox Active | `/admin/inbox/active` | `SupportInboxController@active` | `backend.support.active` | Placeholder | Sistema | Nenhuma | Sem dados | View curta | 25% |
| Suporte > Inbox Resolved | `/admin/inbox/resolved` | `SupportInboxController@resolved` | `backend.support.resolved` | Nao encontrada | Sistema | Nenhuma | Sem dados | Controller retorna `backend.support.resolved`, mas view nao foi encontrada na listagem | 0% |
| Suporte > Knowledge Base | `/admin/inbox/knowledge-base` | `SupportInboxController@knowledgeBase` | `backend.support.knowledge_base` | Nao encontrada | Sistema | Nenhuma | Sem dados | View nao encontrada | 0% |
| Suporte > Macros | `/admin/inbox/macros` | `SupportInboxController@macros` | `backend.support.macros` | Nao encontrada | Sistema | Nenhuma | Sem dados | View nao encontrada | 0% |
| Suporte > Metrics | `/admin/inbox/metrics` | `SupportInboxController@metrics` | `backend.support.metrics` | Nao encontrada | Relatorio | Nenhuma | Sem dados | View nao encontrada | 0% |
| Tickets > Pending | `/admin/support-ticket/pending` | `TicketController@pendingTicket` | `backend.support_ticket.list` | Funcional | CRUD | `Ticket` | Model | Sistema antigo mantido; menu preferiu chat | 75% |
| Tickets > In Progress | `/admin/support-ticket/inprogress` | `TicketController@inprogress` | `backend.support_ticket.list` | Funcional | CRUD | `Ticket` | Model | Reusa view | 75% |
| Tickets > Closed | `/admin/support-ticket/close` | `TicketController@closeTicket` | `backend.support_ticket.list` | Funcional | CRUD | `Ticket` | Model | Reusa view | 75% |
| Tickets > History | `/admin/support-ticket/history` | `TicketController@history` | `backend.support_ticket.list` | Funcional | Relatorio | `Ticket` | Model | Reusa view | 75% |
| Tickets > Show | `/admin/support-ticket/show/{ticket}` | `TicketController@ticketShow` | `backend.support_ticket.show` | Funcional | CRUD | `Ticket` | Model | Rota escondida | 75% |
| Tickets > Categories | `/admin/support-ticket/category` | `SupportCategoryController@index` | `backend.support_ticket.category.index` | Funcional | Configuracao | `SupportCategory` | Model | Modais/partials | 75% |

### Billing Enterprise

| Nome | URL | Controller | View Blade | Estado | Tipo | Dependencias | Origem dos dados | Problemas | Maturidade |
|---|---|---|---|---|---|---|---|---|---|
| Billing > Dashboard | `/admin/billing` | `Admin\Billing\BillingDashboardController@index` | `admin.billing.dashboard` | Parcial | Dashboard | `Subscription`, `Plan` | Model | Botao "Ver Faturas (Em breve)"; queries diretas | 50% |
| Billing > Plans | `/admin/billing/plans` | `Admin\Billing\PlanController@index` | `admin.billing.plans.index` | Funcional | CRUD | `Plan` | Model | Sem BaseController permissions; sem service | 75% |
| Billing > Plan Create | `/admin/billing/plans/create` | `PlanController@create` | `admin.billing.plans.form` | Parcial | CRUD | `FeatureCatalog` | Model | View `admin.billing.plans.form` nao apareceu na listagem de views; risco de quebrar | 0% |
| Billing > Plan Edit | `/admin/billing/plans/{id}/edit` | `PlanController@edit` | `admin.billing.plans.form` | Parcial | CRUD | `Plan`, `FeatureCatalog` | Model | View possivelmente ausente; sem service | 0% |

### Plataforma

| Nome | URL | Controller | View Blade | Estado | Tipo | Dependencias | Origem dos dados | Problemas | Maturidade |
|---|---|---|---|---|---|---|---|---|---|
| Plataforma > Feature Flags | `/admin/platform/feature-flags` | `Backend\PlatformController@featureFlags` | `backend.platform.feature_flags` | Placeholder | Configuracao | Nenhuma | Sem dados | View de 12 linhas | 25% |
| Plataforma > Versioning | `/admin/platform/versioning` | `PlatformController@versioning` | `backend.platform.versioning` | Placeholder | Sistema | Nenhuma | Sem dados | View de 12 linhas | 25% |
| Plataforma > Changelog | `/admin/platform/changelog` | `PlatformController@changelog` | `backend.platform.changelog` | Placeholder | Sistema | Nenhuma | Sem dados | View de 12 linhas | 25% |
| Relatorios > Todos | `/admin/reports` | `Backend\ReportController@index` | `backend.reports.index` | Placeholder | Relatorio | Nenhuma | Sem dados | View de 17 linhas; sem filtros/exportacoes | 25% |

## Paginas Escondidas por Rota

Paginas/rotas GET acessiveis mas ausentes do menu principal:

- Finance Enterprise: `/admin/finance/reconciliations`, `/admin/finance/transactions`, `/admin/finance/settlements`, `/admin/finance/fees`.
- Ledger Engine: `/admin/ledger`, `/admin/ledger/timeline/{charge_id}`.
- Gateway detalhe: `/admin/gateway/monitor/{id}`, `/admin/gateway/charges/{id}`, `/admin/gateway/withdrawals/{id}`, `/admin/gateway/digisynk`.
- Identity: `/admin/identity/sessions`, `/admin/identity/login-logs`, `/admin/identity/devices`.
- Virtual Card: todas as rotas de `virtual-card/*`.
- Deposit/Withdraw methods e requests.
- Support Ticket legado e categorias.
- CMS detalhado: navigation, footer, components, landing HTML manager, SEO, social, subscribers.
- Billing plans create/edit.
- API logs/docs/keys.
- JSON operacional em `/admin/ops/*`.

## Placeholders Detectados

| Arquivo/Rota | Evidencia | Impacto |
|---|---|---|
| `/admin/stub` / `backend.stub.index` | Stub generico "Modulo em Desenvolvimento" | Placeholder explicito para campanhas e possiveis modulos futuros |
| `backend.api.keys` | "Em breve, voce podera gerar..." | API Keys nao implementado |
| `backend.api.docs` | View curta sem dados | Docs admin placeholder |
| `backend.reports.index` | View curta | Relatorios nao implementados |
| `backend.system.queues` | "Em breve, monitoramento em tempo real..." | Scheduler/filas sem monitor real |
| `backend.compliance.audit` | "Em breve..." | Auditoria/RTS nao implementado |
| `backend.compliance.blacklist` | "Em breve..." | Listas restritivas nao implementadas |
| `backend.compliance.whitelist` | "Em breve..." | Bypass/whitelist nao implementado |
| `backend.compliance.fraud_engine` | "Em breve..." | Motor antifraude sem configuracao |
| `backend.compliance.risk_score` | View curta | Score de risco sem dados |
| `backend.gateway.fallback` | Alert informativo | Fallback sem implementacao operacional completa |
| `backend.payment_gateway.tabs.health/logs/webhooks` | Views de 12-15 linhas | Tabs de gateway sem dados reais |
| `backend.platform.*` | Views de 12 linhas | Epic Plataforma incompleto |
| `admin.ops.dashboard` | "Horizon Placeholder" | Fila/Horizon mockado |
| `admin.billing.dashboard` | "Ver Faturas (Em breve)" | Billing incompleto |
| `backend.dashboard.index` | KPI "Tarifas coletadas (Em breve)" | KPI executivo incompleto |
| `backend.finance.*` | Comentarios "Placeholder Tabs" | Drawers/tabs ainda simulados |

## Duplicacoes e Conflitos

Rotas/names duplicados:

- `admin.gateway.monitor.index` declarado em `GatewayManagerController@monitor` e `Gateway\GatewayMonitorController@index`.
- `admin.gateway.logs` declarado em `GatewayManagerController@logs` e `Gateway\GatewayLogController@index`.
- `admin.finance.refunds`, `admin.finance.chargebacks`, `admin.finance.reconciliation` declarados duas vezes.
- `/admin/finance/ledger` existe em dois grupos: `Backend\Finance\LedgerController@index` com nome `admin.finance.ledger.index` e `Backend\FinanceController@ledger` com nome `admin.finance.ledger`.
- `config/admin_menus.php` usa `admin.gateway.monitor.index` duas vezes: Health e Monitor.
- Finance tem duas arquiteturas paralelas: `Backend\FinanceController` e `Backend\Finance\*Controller`.
- Gateway tem duas arquiteturas paralelas: `GatewayManagerController` e `Backend\Gateway\*Controller`.
- Compliance tem `Admin\ComplianceController` e `Backend\ComplianceController`, mas a rota ativa `/admin/compliance` usa apenas o primeiro.
- Services/DTOs duplicados em namespaces raiz e `App\Services\Finance` / `App\DTOs\Finance` / `App\Data\Finance`.

Views duplicadas/conceituais:

- `backend.finance.chargebacks` e `backend.finance.chargebacks.index`.
- `backend.finance.transactions` e `backend.finance.transactions.index`.
- `backend.finance.fees` e `backend.finance.fees.index`.
- `backend.finance.reconciliation` e `backend.finance.reconciliations.index`.
- `backend.gateway.monitor` recebe dados de dois controllers diferentes.
- `backend.gateway.logs` pode ser alimentada por dois controllers/names iguais.

## Violacoes Arquiteturais

Padroes recorrentes:

- Query no controller: `DashboardController`, `FinanceController@ledger/webhooks`, `GatewayManagerController`, `CommandCenterController`, `UserController`, `MerchantController`, `WebhookAdminController`, `PaymentGatewayController`, `BillingDashboardController`, `PlanController`, varios CRUDs.
- Sem DTO: praticamente todos os CRUDs legados, Gateway Manager, Webhooks, Billing, Support, CMS.
- Sem DashboardService: Dashboard principal usa service parcial, mas ainda consulta DB diretamente; Command Center e Ops Dashboard nao usam service unico.
- Sem ActionService: acoes sensiveis em User Manage, Merchant Action, KYC Action, Webhook replay, Gateway credentials/routing, Virtual Card review, Withdraw/Deposit request action.
- Sem `x-admin.*`: maioria dos CRUDs legados; `x-admin.*` concentrado em Finance.
- HTML/CSS/JS inline: cerca de 100 Blade files contem `<script>`, `<style>` ou `style=`.
- Acoes destrutivas/alteradoras via GET: `admin.app.optimize`, `admin.app.clear-cache`, notificacoes read/read-all, referral status update.
- Mock/stub em producao: `rand()` em gateway show, arrays vazios em command center/gateway errors, "Horizon Placeholder", views "Em breve".
- Ausencia de paginacao em alguns pontos: `PaymentGateway::all`, `VirtualCardProvider::all`, `WithdrawSchedule::all`, `UserRank::all`, capabilities/connectivity/fallback.
- Risco N+1: varias listagens antigas dependem de relações sem evidencia de eager loading consistente.
- Permissoes inconsistentes: controllers que nao estendem `BaseController` nao recebem automaticamente middleware `permission:*`.

## Roadmap por Dominio

| Dominio | Paginas | Prontas/funcionais | Parciais | Placeholders/quebradas | Esforco | Prioridade |
|---|---:|---:|---:|---:|---|---|
| Finance | 28 | 15 | 8 | 5 | Alto | Alta |
| Gateway | 20 | 9 | 8 | 3 | Alto | Alta |
| Compliance | 9 | 2 | 2 | 5 | Alto | Alta |
| Usuarios/KYC/Merchant | 22 | 22 | 0 | 0 | Medio | Alta |
| Operacoes/Dashboard | 13 | 8 | 5 | 0 | Medio | Alta |
| Sistema/Configuracoes | 19 | 14 | 2 | 3 | Medio | Media |
| API/Webhooks | 6 | 4 | 0 | 2 | Medio | Media |
| CMS/Marketing | 18 | 17 | 0 | 1 | Baixo/Medio | Baixa |
| Suporte | 13 | 7 | 0 | 6 | Medio | Media |
| Billing | 4 | 1 | 1 | 2 | Medio | Media |
| Plataforma/Relatorios | 4 | 0 | 0 | 4 | Medio | Baixa |

## Ordem Ideal de Implementacao

1. Estabilizar roteamento: remover duplicidade de names, escolher controllers canonicos por dominio e alinhar `admin_menus.php`.
2. Definir padrao Enterprise: escolher entre `x-admin.*` e `x-ds.*` ou criar bridge clara; documentar componentes obrigatorios para pagina admin.
3. Finance: consolidar `Backend\FinanceController` versus `Backend\Finance\*Controller`; manter DTO/services; finalizar refunds, liquidacao, charges/withdrawals ou remover rotas mortas.
4. Gateway: consolidar monitor/logs/routing; implementar Health/Webhooks/Logs tabs com dados reais; remover mocks.
5. Compliance: implementar Audit, Risk Score, Fraud Engine, Blacklist/Whitelist com services existentes em `App\Services\Compliance` e `App\Services\Fraud`.
6. Operacoes: transformar Command Center/Ops Dashboard em dashboard service unico; trocar mocks por `PlatformAlertService`, `QueueMonitorService`, `SchedulerMonitorService`, `IncidentManagerService`.
7. Permissoes: aplicar `BaseController` ou middleware explicito nos controllers novos; revisar menus com `permission`.
8. Actions: mover alteracoes sensiveis para ActionServices e POST/PATCH; eliminar acoes GET alteradoras.
9. Suporte Inbox: criar views ausentes (`resolved`, `knowledge_base`, `macros`, `metrics`) ou remover rotas ate implementacao.
10. Billing: criar/validar `admin.billing.plans.form`, implementar invoices e services de billing.
11. API/Reports/Platform: substituir placeholders por paginas reais ou ocultar do menu.
12. Qualidade: extrair CSS/JS inline para assets, padronizar filtros/paginacao/drawers/cache, revisar N+1.

## Roadmap ate 100%

### Fase 1 - Fundacao e roteamento

- Resolver duplicidade de rotas nomeadas.
- Definir mapa canonico de menus e rotas.
- Garantir que toda rota visivel no menu existe e aponta para controller/view corretos.
- Remover ou isolar rotas escondidas quebradas.

### Fase 2 - Dominios criticos

- Finance 100%: Ledger, Transactions, Reconciliation, Settlements, Fees, Chargebacks, Refunds, Wallets.
- Gateway 100%: Providers, Routing, Fallback, Health, Logs, Webhooks, Connectivity.
- Compliance 100%: Audit, Risk, Fraud Engine, Blacklist, Whitelist, Anomalies.

### Fase 3 - Operacao Enterprise

- Command Center com dados reais.
- Ops Dashboard sem placeholders.
- Queues/Scheduler/Incidents/Health integrados aos services.
- Auditoria persistida em banco, nao apenas log file.

### Fase 4 - Consistencia de plataforma

- Migrar CRUDs legados gradualmente para Design System.
- Padronizar DTO + DashboardService + ActionService.
- Extrair JS/CSS inline.
- Aplicar permissoes granulares em controllers novos e menus.

### Fase 5 - Finalizacao

- Implementar Billing completo.
- Implementar API Keys/Docs/Reports/Platform.
- Revisar testes de smoke para todas as rotas admin GET.
- Adicionar checklist visual para todas as paginas: KPIs, filtros, tabela, empty state, paginacao, drawer/detalhe, acoes auditadas.

## Riscos Criticos Fora do Painel

Embora nao seja uma pagina do painel, `routes/web.php` contem `/reset-admin-pwd`, que cria/reseta administrador com senha fixa `12345678`. Isso deve ser tratado como risco critico de seguranca antes de qualquer go-live.

