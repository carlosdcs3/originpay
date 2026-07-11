# Structural Cleanup & Dead Code Elimination

Data: 2026-06-27

Escopo: auditoria e limpeza conservadora do projeto DigiKash/OriginPay, com foco em rotas perigosas, duplicações evidentes, arquivos temporarios, backups acidentais e codigo morto de alta confianca.

## Resumo executivo

Foram removidos somente itens com alta confianca de nao pertencerem ao runtime de producao: rota publica de reset de senha administrativa, rotas administrativas duplicadas/quebradas de gateway, scripts soltos de manutencao/teste, arquivos `.backup`/`.tmp` e logs de debug em notificacoes Pusher.

Nao foram removidos fluxos financeiros, controllers de dominio, views de features comentadas, dependencias Composer/NPM suspeitas ou rotas administrativas com impacto operacional ainda ambiguo. Esses itens ficam listados como risco/pendencia para decisao humana.

## Codigo removido

### Rota publica perigosa

- `core/routes/web.php`
  - Removida a rota `GET /reset-admin-pwd`.
  - Motivo: endpoint publico alterava a senha de um usuario administrador para valor fixo (`12345678`).
  - Risco mitigado: takeover administrativo via URL conhecida.

### Rotas duplicadas/quebradas de gateway

- `core/routes/admin.php`
  - Removida a rota duplicada `GET /admin/gateway/monitor` apontando para `GatewayManagerController@monitor`.
  - Removida a rota duplicada `GET /admin/gateway/logs` apontando para `GatewayManagerController@logs`.
  - Mantidas as rotas funcionais equivalentes:
    - `GET /admin/gateway/monitor` -> `Backend\Gateway\GatewayMonitorController@index`
    - `GET /admin/gateway/logs` -> `Backend\Gateway\GatewayLogController@index`
  - Motivo: a primeira declaracao de `/gateway/logs` apontava para metodo inexistente e podia sombrear a rota valida posterior.

### Debug server-side

- `core/app/Http/Controllers/Frontend/VirtualCardRequestController.php`
  - Removido `dd($e->getMessage())` em bloco de excecao.
  - Substituido por `Log::error(...)` estruturado.
  - Mantido o comportamento de rollback, notificacao ao usuario e `return back()`.

### Debug client-side

- `core/resources/views/general/notification_config/_pusher_config.blade.php`
  - Removido `pusher.logToConsole = true`.
  - Removidos `console.log(...)` de canal/notificacao.
  - Mantido `console.error(...)` para ausencia de CSRF token.

## Arquivos orfaos removidos

### Scripts soltos na raiz do repositorio

- `fix.php`
- `fix2.php`
- `fix3.php`
- `fix_admin_routes.php`

### Scripts soltos em `core/`

- `auto_translate.php`
- `change_name.php`
- `change_name2.php`
- `change_name3.php`
- `check_referral.php`
- `create_efi.php`
- `find_orphans.php`
- `fix_flag.php`
- `fix_wallet.php`
- `get_currency.php`
- `get_gateways.php`
- `get_langs.php`
- `setup_pix.php`
- `setup_pix2.php`
- `setup_pix3.php`
- `setup_pix4.php`
- `setup_pix5.php`
- `sync_permissions.php`
- `test.php`
- `test2.php`
- `test3.php`
- `test_chat.php`
- `test_router.php`
- `test_urls.php`
- `tinker.php`
- `tinker_script.php`
- `translate.php`
- `translate_validation.php`
- `prepareBindings($bindings)`

Motivo: arquivos PHP avulsos, fora de rotas/autoload, com nomes de teste, setup, tinker, traducao ou manutencao pontual. Nenhum deles faz parte dos entrypoints normais do Laravel.

### Backups e temporarios removidos

- `core/routes/admin.php.before-restore.backup`
- `core/routes/admin.php.corrupted.backup`
- `core/routes/admin.recovered.php`
- `core/app/Http/Controllers/Backend/SupportChatAdminController.php.before-user-filter-fix.backup`
- `core/app/Http/Controllers/Frontend/SupportChatController.php.before-fix.backup`
- `core/app/Models/CustomCode.php.before-cache-fix.backup`
- `core/app/Models/Plugin.php.before-credentials-fix.backup`
- `core/resources/views/backend/gateway/charges/index.blade.php.backup`
- `core/bootstrap/cache/pac742B.tmp`
- `core/bootstrap/cache/pacAA46.tmp`
- `core/bootstrap/cache/ser1E1D.tmp`
- `core/bootstrap/cache/ser374E.tmp`

## Duplicacoes eliminadas

- URL duplicada `/admin/gateway/monitor`.
- URL duplicada `/admin/gateway/logs`.
- Nome de rota duplicado `admin.gateway.monitor.index`.
- Nome de rota duplicado `admin.gateway.logs`.
- Metodo inexistente `GatewayManagerController@logs` deixou de ser referenciado por rota.

## Duplicacoes ainda existentes

Estas duplicacoes foram detectadas, mas nao removidas por exigirem decisao de dominio ou risco de compatibilidade com menus, permissoes e links existentes:

- `core/routes/admin.php`
  - `admin.finance.refunds` aparece mais de uma vez.
  - `admin.finance.chargebacks` aparece mais de uma vez.
  - `admin.finance.reconciliation` aparece mais de uma vez.
  - `/admin/finance/ledger` aparece em mais de um contexto:
    - `Backend\Finance\LedgerController@index`
    - `Backend\FinanceController@ledger`

Recomendacao: consolidar o dominio Finance em uma unica familia de controllers enterprise e criar redirecionamentos/aliases somente quando houver necessidade explicita de compatibilidade.

## Rotas e comportamentos de risco

### GET com efeito de escrita ou operacao operacional

- `core/routes/admin.php`
  - Notificacoes:
    - `GET /admin/notifications/{notification}/read`
    - `GET /admin/notifications/read-all`
  - Referral:
    - `GET /admin/referral/status-update/{type}/{status}`

Observacao: outras rotas `status-update` ja usam `POST`/`PUT`, mas esta rota de referral ainda usa `GET`.

Recomendacao: migrar para `POST`, `PUT` ou `PATCH`, com CSRF, mantendo redirects/links antigos apenas durante janela de compatibilidade.

### Operacoes administrativas sensiveis

- Rotas administrativas de cache/optimizacao existem e devem ser revisadas quanto a permissao, metodo HTTP e auditoria:
  - `admin.app.optimize`
  - `admin.app.clear-cache`

Nao foram alteradas para evitar quebrar a navegacao administrativa existente.

## Views e rotas quebradas ou incompletas

Detectadas durante a auditoria estrutural:

- `SupportInboxController@resolved` referencia `backend.support.resolved`, view nao encontrada.
- `SupportInboxController@knowledgeBase` referencia `backend.support.knowledge_base`, view nao encontrada.
- `SupportInboxController@macros` referencia `backend.support.macros`, view nao encontrada.
- `SupportInboxController@metrics` referencia `backend.support.metrics`, view nao encontrada.
- `Admin\Billing\PlanController@create`/`edit` referencia `admin.billing.plans.form`, view nao encontrada.

Essas rotas nao foram removidas porque podem fazer parte de telas planejadas, permissao/menu futuro ou backlog enterprise.

## Debug restante nao removido

Foram encontrados `console.log(...)` restantes em arquivos que parecem documentacao, sandbox ou feature legada sem rota ativa:

- `core/resources/views/general/merchant/payment_wallet.blade.php`
  - Logs de fluxo sandbox de wallet/voucher.
- `core/resources/views/general/merchant/payment_checkout.blade.php`
  - Logs de fluxo sandbox de checkout.
- `core/resources/views/general/api-docs/**`
  - Logs dentro de exemplos de API/documentacao e inicializacao de highlight/documentacao.
- `core/resources/views/frontend/user/vouchers/create.blade.php`
  - `console.log(total, convertedAmount)`.
  - A tela de vouchers parece estar associada a rotas atualmente comentadas em `routes/web.php`.

Recomendacao: remover logs reais de UI de producao e preservar logs apenas quando forem exemplos de documentacao. O fluxo sandbox deve ser decidido por ambiente/configuracao antes de limpeza automatica.

## Dependencias suspeitas

Nao removidas automaticamente.

### Composer

- `mollie/laravel-mollie`
  - Suspeita: possivel gateway legado ou nao referenciado diretamente.
- `laravel/tinker`
  - Suspeita: os scripts `tinker*.php` foram removidos, mas o comando Artisan pode ainda ser util em desenvolvimento.
- `laravel-notification-channels/twilio`
  - Suspeita: depende de uso real de notificacoes SMS/WhatsApp.
- `bombenprodukt/cryptomus-php-sdk`
  - Suspeita: validar uso real no gateway antes de remover.

### NPM

- `alpinejs`
- `laravel-echo`
- `pusher-js`
- `@tailwindcss/forms`

Observacao: ha configuracao e views relacionadas a Pusher/Echo, portanto a remocao dessas dependencias exige verificacao de build e runtime.

## Arquivos nao removidos

- Controllers e views de vouchers, virtual card, request money e exchange money associados a blocos de rota comentados.
  - Motivo: podem representar feature pausada ou planejada.
- Rotas duplicadas do dominio Finance.
  - Motivo: exigem migracao cuidadosa de menus, permissoes e possiveis links externos/internos.
- Rotas administrativas de cache/optimizacao.
  - Motivo: podem estar acopladas ao painel atual; precisam de revisao de autorizacao e metodo HTTP.
- `console.log` em documentacao/API docs.
  - Motivo: alguns logs sao exemplos de uso para desenvolvedores.
- Dependencias Composer/NPM suspeitas.
  - Motivo: remover dependencias sem executar testes/build completos pode quebrar gateway, notificacao, assets ou ferramentas de desenvolvimento.

## Validacoes executadas

- Busca por `reset-admin-pwd`: sem ocorrencias restantes em `core/routes`, `core/app`, `core/resources`.
- Busca por `pusher.logToConsole`, `dgdg` e logs Pusher removidos: sem ocorrencias restantes.
- Busca por `dd(...)`, `dump(...)`, `var_dump(...)`, `ray(...)`: sem ocorrencias restantes em `core/routes`, `core/app`, `core/resources`.
- Busca por backups/temporarios `*.backup`, `*.bak`, `*.tmp`, `*.old`, `*.orig` em `core` e `docs`: sem ocorrencias restantes.
- `php artisan route:list` executado pelo usuario em 2026-06-27:
  - Laravel carregou a tabela de rotas sem erro fatal.
  - Total informado: 577 rotas.
  - `GET /reset-admin-pwd` nao aparece mais.
  - `admin/gateway/logs` aponta somente para `Backend\Gateway\GatewayLogController@index`.
  - `admin/gateway/monitor` aponta somente para `Backend\Gateway\GatewayMonitorController@index`.

## Validacoes nao executadas

- `php artisan test`
- `php -l`
- build Vite/NPM

Motivo: o binario `php` nao esta disponivel no `PATH` deste ambiente Codex. A validacao de rotas foi feita com base no output anexado pelo usuario; testes, lint e build ainda dependem de execucao no ambiente local com PHP/NPM disponiveis.

## Roadmap recomendado

### Prioridade alta

1. Consolidar rotas duplicadas do dominio Finance.
2. Migrar rotas `GET` com efeito de escrita para metodos seguros (`POST`/`PUT`/`PATCH`).
3. Corrigir ou remover rotas que apontam para views inexistentes.
4. Rodar `php artisan route:list`, testes e lint em ambiente com PHP disponivel.

### Prioridade media

1. Separar claramente sandbox de producao nos checkouts.
2. Remover logs reais de UI que nao sejam exemplos de documentacao.
3. Revisar dependencias Composer/NPM suspeitas com base em autoload, uso real e build.
4. Consolidar controllers legados e enterprise que atendem ao mesmo dominio.

### Prioridade baixa

1. Remover comentarios extensos de rotas antigas apos decisao de produto.
2. Padronizar nomes de rotas e controllers por dominio.
3. Criar checklist recorrente para impedir entrada de `.backup`, `.tmp`, scripts `test*.php` e rotas temporarias.

## Conclusao

A limpeza removeu itens claramente inseguros ou mortos sem alterar o comportamento financeiro central. O projeto ainda possui duplicacoes e riscos arquiteturais relevantes, mas eles exigem uma rodada de migracao controlada, com PHP/artisan/testes disponiveis e validacao de permissoes, menus e dependencias de runtime.

## Fase 2.1 - Finance & Admin Safety

Data: 2026-06-27

### Finance consolidado

Arquitetura canonica definida:

- `admin.finance.ledger` -> `Backend\Finance\LedgerController@index`
- `admin.finance.chargebacks` -> `Backend\Finance\ChargebackController@index`
- `admin.finance.reconciliation` -> `Backend\Finance\ReconciliationController@index`
- `admin.finance.refunds` -> `Backend\FinanceController@refunds`

Alteracoes:

- Removida a rota legada duplicada `/admin/finance/ledger` em `Backend\FinanceController@ledger`.
- Removida a rota legada duplicada `/admin/finance/chargebacks` em `Backend\FinanceController@chargebacks`.
- Removida a rota legada duplicada `/admin/finance/reconciliation` em `Backend\FinanceController@reconciliation`.
- Removido o bloco final duplicado de `refunds`, `chargebacks` e `reconciliation`.
- Mantidos os nomes de rota usados em `config/admin_menus.php`.
- Atualizadas as views enterprise para usar os nomes canonicos:
  - `backend.finance.ledger.index`
  - `backend.finance.chargebacks.index`
  - `backend.finance.reconciliations.index`

Resultado: nao existem mais rotas nomeadas ativas duplicadas para `ledger`, `chargebacks`, `reconciliation` e `refunds`.

### GET mutavel migrado

Rotas administrativas mutaveis migradas:

- `admin.notifications.markAsRead`: `GET` -> `PATCH`
- `admin.notifications.markAllAsRead`: `GET` -> `PATCH`
- `admin.referral.status-update`: `GET` -> `PATCH`
- `admin.app.optimize`: `GET` -> `POST`
- `admin.app.clear-cache`: `GET` -> `POST`

Consumidores atualizados:

- `backend.notifications.index`: link de "Mark all as read" virou formulario `POST` com `@method('PATCH')` e CSRF.
- `backend.notifications.partials._scripts`: leitura individual de notificacao agora usa AJAX `PATCH` com CSRF.
- `backend.referral.index`: toggle de status agora usa AJAX `PATCH` com CSRF.
- `config/admin_menus.php`: menu "Maintenance" deixou de apontar para `admin.app.optimize` e passou a abrir `admin.app.control-panel`, evitando mutacao por clique GET.

### Views inexistentes corrigidas

Criados empty states minimos:

- `core/resources/views/backend/support/resolved.blade.php`
- `core/resources/views/backend/support/knowledge_base.blade.php`
- `core/resources/views/backend/support/macros.blade.php`
- `core/resources/views/backend/support/metrics.blade.php`
- `core/resources/views/admin/billing/plans/form.blade.php`

Billing tambem foi alinhado ao schema real do projeto:

- `PlanController` deixou de usar `App\Models\Plan` e `FeatureCatalog`, que nao existem.
- `PlanController` passou a usar `CommercialPlan`, `CommercialFeature` e `Product`.
- `BillingDashboardController` passou a contar `CommercialPlan` e calcular MRR por assinaturas ativas com `prices`.
- `BillingSetting::fallbackPlan()` passou a referenciar `CommercialPlan`.
- `admin.billing.plans.index` foi ajustada para os campos reais de `commercial_plans`.

### Validacoes executadas na Fase 2.1

Comando base usado:

```powershell
E:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe artisan route:list
```

Resultados:

- `php artisan route:list`: sucesso, 576 rotas carregadas.
- `php artisan route:list | findstr finance`: sucesso; `ledger`, `chargebacks` e `reconciliation` apontam para controllers enterprise especificos.
- `php artisan route:list | findstr gateway`: sucesso; Gateway Monitor/Logs continuam sem duplicidade.
- `php artisan route:list | findstr notification`: sucesso; rotas de leitura mutavel admin aparecem como `PATCH`.
- `php artisan route:list | findstr referral`: sucesso; `admin/referral/status-update/{type}/{status}` aparece como `PATCH`.
- `php artisan route:list | findstr billing`: sucesso; rotas de plans carregam com `PlanController`.
- `php artisan route:list | findstr support`: sucesso para rotas support-chat/support-ticket.
- `php artisan route:list | findstr inbox`: sucesso extra para as rotas de `SupportInboxController`.

Lint PHP executado com sucesso:

- `routes/admin.php`
- `app/Http/Controllers/Admin/Billing/PlanController.php`
- `app/Http/Controllers/Admin/Billing/BillingDashboardController.php`
- `app/Http/Controllers/Backend/NotificationController.php`
- `app/Http/Controllers/Backend/ReferralController.php`
- `app/Http/Controllers/Backend/AppController.php`
- `app/Http/Controllers/Backend/SupportInboxController.php`
- `app/Models/BillingSetting.php`
- `app/Models/PlanVersion.php`
