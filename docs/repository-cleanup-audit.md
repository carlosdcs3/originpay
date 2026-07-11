# OriginPay Repository Cleanup Audit

Data: 2026-07-09
Repo auditado: `E:/projetos/DigiKash v1.0.5/DigiKash v1.0.5/core`

## Escopo

Auditoria para separar código ativo OriginPay de legado, duplicado, temporário e não usado. Nenhuma regra de negócio, landing ou feature foi alterada nesta etapa.

## Método

- Mapeamento de diretórios principais.
- Listagem de rotas com `php artisan route:list`.
- Listagem de comandos Artisan com `php artisan list --raw`.
- Busca textual por referências dos candidatos antes de classificar como remoção segura.
- Exclusão de `vendor`, `node_modules`, `.git`, `_archive_legacy_review` das buscas.
- Análise manual de nomes, localização e função aparente.

## Estrutura principal

### Diretórios principais

- `app/` — aplicação Laravel, controllers, models, services, console commands, gateway, domain.
- `routes/` — rotas `web.php`, `admin.php`, `api.php`, `auth.php`, `connect.php`, etc.
- `resources/views/` — views Blade divididas entre `frontend`, `backend`, `admin`, `general`, `components`.
- `public/` — assets públicos: `frontend`, `backend`, `general`, `sdk`, uploads, custom landings.
- `database/` — migrations, seeders, factories.
- `config/` — configs Laravel e integrações.
- `tests/` — testes Pest/PHPUnit.
- `docs/` — documentação técnica OriginPay.
- `storage/` — runtime Laravel, logs, cache, uploads via storage link.
- `vendor/` — dependências Composer. Não remover.
- `node_modules/` — dependências NPM. Não remover manualmente.

## Módulos ativos identificados

### Core Laravel

- `routes/web.php`, `routes/admin.php`, `routes/api.php`, `routes/auth.php`, `routes/connect.php`.
- `app/Http/Controllers/Frontend/HomeController.php` serve home atual via `frontend.pages.digisynk-home`.
- `resources/views/frontend/pages/digisynk-home.blade.php` é landing atual.
- `resources/views/frontend/layouts/landing.blade.php` é layout da landing atual.
- `public/frontend/css/originpay.css` é CSS ativo servido como `OriginPay.css`.
- `public/frontend/images/hero-logo-object.png` é fallback atual do hero.
- `public/frontend/models/originpay-hero-logo.glb` é caminho futuro para modelo 3D; arquivo ainda ausente.

### Admin / Backend

Rotas `admin/*` ativas via `routes/admin.php`, incluindo:

- Dashboard e activity logs.
- Compliance, command center, alerts.
- Billing admin.
- Custom landing admin.
- User/merchant management.
- Gateway/payment management.
- Finance/ledger/reconciliation/disputes.

Views em `resources/views/backend` e `resources/views/admin` são usadas por rotas e controllers backend/admin. Não remover sem auditoria fina por rota.

### API / Payments / Gateway

Módulos ativos ou referenciados:

- `app/Http/Controllers/Api` e `app/Http/Controllers/Api/V1`.
- `app/Gateway`.
- `app/Services/Gateway*`, `app/Services/Payments`, `app/Services/Finance`.
- `app/Domain/Payments`, `app/Domain/Auth`, `app/Domain/PaymentMethod`.
- `public/sdk` e `resources/js/originpay-sdk` indicam SDK público e fonte.

### Console / Operação

`php artisan list --raw` mostra comandos custom ativos, incluindo exemplos:

- `anomalies:scan`
- `beta:readiness`
- `chaos:trigger`
- comandos de finance/reconciliation/gateway/observability

`app/Console/Commands` é ativo. Não remover.

## Duplicado / Legado provável

### Duplicados arquiteturais — NÃO REMOVER agora

Há duplicação real entre namespaces antigos e novos. Alto risco remover sem teste dirigido.

Exemplos:

- `app/Gateway/*` vs `app/Services/Gateway/*` e `app/Services/Gateways/*`.
- `app/Services/ChargeService.php` vs `app/Services/Payments/ChargeService.php`.
- `app/Services/FeeDashboardService.php` vs `app/Services/Finance/FeeDashboardService.php`.
- `app/Data/Finance/*DashboardData.php` vs `app/DTOs/Finance/*DashboardData.php`.
- `app/Contracts/GatewayProviderInterface.php` vs `app/Gateway/Contracts/GatewayProviderInterface.php`.
- `app/Models/ApiCredential.php` vs `app/Domain/Auth/ApiCredential.php`.
- `app/Models/Charge.php` vs `app/Domain/Payments/Charge.php`.
- `app/Constants/FixPctType.php` vs `app/Enums/FixPctType.php`.

Classificação: `DUPLICADO`, risco alto. Não mover nesta rodada.

### Assets duplicados por storage link — NÃO REMOVER

Muitos arquivos aparecem em três caminhos:

- `storage/app/public/...`
- `public/storage/...`
- `public/files` ou `public/images`

Parte disso é efeito de storage link/cópias públicas. Risco alto de quebrar uploads existentes. Não mover sem mapear uso no banco.

### OriginPay assets duplicados — SUSPEITO

Há assets em:

- `public/frontend/images/originpay/...`
- `public/frontend/images/...`

Alguns são duplicados de logo/favicon. Como layout usa caminhos específicos em `originpay/`, não mover nesta rodada.

### Custom landings — SUSPEITO

Arquivos:

- `public/custom-landings/digital-wallet-landing-1752500682/index.html`
- `public/custom-landings/virtual-card-landing-1752500798/index.html`

Home atual não usa CustomLanding nesse checkout, mas admin ainda tem `custom-landing`. Pode haver dependência no banco/admin. Não mover.

## Legado / temporário identificado

### Scripts soltos na raiz

Arquivos com nomes `fix_*`, `update_*`, `translate_*`, `test_*`, dumps de rotas/views e scripts PowerShell aparentam ser ferramentas temporárias de manutenção/manual patch.

Busca textual não encontrou referências reais para quase todos. `test.txt` teve hit textual em `HealthCheckController.php`, mas é falso positivo por string genérica `test.txt`; não há uso como include/require/asset.

Classificação: `REMOÇÃO SEGURA` para backup em `_archive_legacy_review`.

### `tmp/`

Contém HTML/cookies/responses de testes manuais/e2e e scripts de probe:

- cookies admin/user/merchant
- HTML capturado
- respostas de withdraw
- probes locais

Não é código runtime Laravel. Alguns hits por string `tmp` são falsos positivos em libs/assets. Classificação: `REMOÇÃO SEGURA` para backup.

### `scratch/`

Contém scripts manuais de refactor e listas:

- `refactor_finance.php`
- `files_to_refactor.txt`
- `find_views.php`

Não referenciado por Composer, Artisan ou app. Classificação: `REMOÇÃO SEGURA` para backup.

### `appDataDir/`

Contém artefato de tarefa/brain local:

- `appDataDir/brain/.../task.md`

Não é Laravel runtime. Classificação: `REMOÇÃO SEGURA` para backup.

### `DB/digikash.sql`

Dump SQL de banco. Não referenciado. Pode ser backup/manual. Classificação: `SUSPEITO`, não mover nesta rodada para evitar perda de snapshot útil.

## ATIVO

Não remover:

- `app/`
- `routes/`
- `resources/views/`
- `public/frontend/`
- `public/backend/`
- `public/general/`
- `public/sdk/`
- `database/`
- `config/`
- `tests/`
- `composer.json`, `composer.lock`
- `package.json`, `package-lock.json`
- `vite.config.js`, `tailwind.config.js`, `postcss.config.js`
- `artisan`
- `.env`, `.env.example`
- `storage/` runtime, exceto logs antigos analisáveis depois
- `vendor/`, `node_modules/`

## LEGADO

Provável legado DigiKash/origem antiga, mas ainda misturado ao produto e não seguro para mover sem testes específicos:

- módulos Gateway duplicados;
- módulos Finance duplicados;
- views `frontend/pages/sections/*` do antigo CMS de páginas;
- `public/custom-landings/*`;
- assets antigos em `public/frontend/images` fora de `originpay/`.

Classificação: legado provável, risco médio/alto. Não mover nesta rodada.

## DUPLICADO

Duplicados identificados:

- `app/Gateway` vs `app/Services/Gateway`/`app/Services/Gateways`.
- `app/Data/Finance` vs `app/DTOs/Finance`.
- `public/frontend/images/originpay-*` vs `public/frontend/images/originpay/originpay-*`.
- arquivos de upload repetidos entre `storage/app/public`, `public/storage`, `public/files`, `public/images`.

Ação: documentar; não remover sem teste por domínio e checagem de banco.

## SUSPEITO

Não mover agora:

- `DB/digikash.sql` — dump SQL pode ser backup útil.
- `public/custom-landings/*` — admin custom landing ainda existe.
- `resources/views/frontend/pages/sections/*` — CMS antigo, mas pode ser usado por páginas dinâmicas.
- `backend_views_list.txt` e route dumps foram classificados seguros se recriáveis, mas conteúdo pode ser útil apenas como snapshot; movidos para backup, não deletados.

## REMOÇÃO SEGURA

Itens aprovados para mover para `_archive_legacy_review`, preservando estrutura:

- `backend_views_list.txt`
- `routes_list.txt`
- `routes_names.json`
- `test-batch-summary.txt`
- `test.js`
- `test.php`
- `test.txt`
- `test_decode.php`
- `cache_bust.php`
- `crop.ps1`
- `fix_admin.php`
- `fix_auth.php`
- `fix_auth_css.php`
- `fix_autofill.php`
- `fix_branding.php`
- `fix_button.php`
- `fix_charges.php`
- `fix_daterangepicker.php`
- `fix_dropdown.php`
- `fix_encoding.php`
- `fix_extra.php`
- `fix_extra2.php`
- `fix_phone2.php`
- `remove_emojis.php`
- `revert_cache.php`
- `translate.php`
- `translate_controllers.php`
- `update_blade.php`
- `update_charge.php`
- `update_css.php`
- `update_css2.php`
- `update_css_mq.php`
- `update_extends.ps1`
- `update_phone.php`
- `update_register.php`
- `update_sidebar.php`
- `update_title.php`
- `validate.ps1`
- `tmp/`
- `scratch/`
- `appDataDir/`
- `_route_list_current.txt` e `_artisan_commands_current.txt` se gerados durante auditoria

Motivo: artefatos manuais, dumps, probes, correções one-off, capturas temporárias. Sem referência real em Composer, routes, controllers, views, configs ou package scripts.

Risco: baixo. Mitigação: mover para `_archive_legacy_review`, não deletar.

## NÃO REMOVER

- Código em `app/`.
- Rotas em `routes/`.
- Views em `resources/views/`.
- Migrations/seeders/factories.
- Assets públicos usados por layout/Blade/CSS.
- SDK público.
- Uploads/storage/public.
- Dumps SQL e backups de banco até decisão humana.
- `vendor`, `node_modules`, `storage/framework`, `bootstrap/cache`.

## Próximos passos recomendados

1. Rodar validação pós-arquivo.
2. Se tudo passar, manter `_archive_legacy_review` por alguns dias.
3. Próxima auditoria: mapear duplicações de Gateway/Finance por namespace e testes.
4. Próxima auditoria: mapear assets no banco antes de limpar `public/files`, `public/images` e `storage/app/public`.
5. Próxima auditoria: verificar se CMS antigo (`frontend.pages.index` + `sections/*`) ainda é necessário.
6. Só deletar `_archive_legacy_review` após homologação manual.

## Resultado pós-movimentação

Arquivo de log:

- `_archive_legacy_review/validation-after-move.log`

Itens movidos:

- 43 itens de baixa criticidade para `_archive_legacy_review`.
- Manifest: `_archive_legacy_review/MANIFEST.json`.

### Comandos executados

- `composer dump-autoload` — passou.
- `php artisan config:clear` — passou.
- `php artisan view:clear` — passou.
- `php artisan route:list` — passou.
- `php artisan test` — executou, mas 3 testes falharam.
- `npm run build` — passou.

### Falha em testes

Falha reportada:

```txt
View [components.regular.dashboard] not found.
```

Teste afetado no log:

```txt
tests\Feature\WebhookAdminPanelTest.php:61
```

Análise da causa:

- A view `components.regular.dashboard` não existe no repositório ativo.
- A view também não existe em `_archive_legacy_review`.
- Busca textual por `components.regular.dashboard` encontrou apenas logs (`storage/logs/laravel.log` e `validation-after-move.log`).
- Nenhum item movido continha essa view ou referência decisiva.

Conclusão:

- Falha de teste parece preexistente/independente da limpeza.
- Não foi feita restauração dos itens movidos, pois não há item movido que corrija a view ausente.
- `route:list` e `npm run build` passaram após movimentação, indicando que bootstrap, rotas e build continuam íntegros.

### Ação recomendada para testes

Criar auditoria separada para corrigir `components.regular.dashboard` ou ajustar `resources/views/backend/layouts/partials/_sidebar.blade.php`/componentes relacionados. Não corrigido nesta etapa para respeitar regra de não alterar regra de negócio/UI.

