# Fees Modular Refactor Pilot

Data: 2026-07-09

## Objetivo

Validar o segundo piloto modular do OriginPay usando o módulo Fees, com base em:

- `docs/originpay-architecture-blueprint.md`
- `docs/refactors/webhooks-pilot.md`

A intenção foi repetir o padrão modular validado em Webhooks, mas em uma fatia ainda segura de Fees.

## Restrições aplicadas

- Não mover arquivos grandes.
- Não alterar regra de negócio.
- Não mexer em Ledger, Wallets, Payments, Withdrawals ou Gateways.
- Manter compatibilidade com rotas/controllers/views atuais.
- Extrair apenas Actions/Contracts mínimos.
- Criar teste focado quando necessário.
- Rodar validação completa.

## Current -> Target map

### Controllers atuais

| Atual | Papel atual | Target futuro |
|---|---|---|
| `app/Http/Controllers/Backend/PlatformFeeRuleController.php` | CRUD/simulação de regras de taxa da plataforma | `app/Modules/Fees/Interface/Http/Controllers/Admin/PlatformFeeRuleController.php` |
| `app/Http/Controllers/Backend/GatewayFeeController.php` | Admin de tarifas/custos de gateway | futuro `app/Modules/Fees/Interface/Http/Controllers/Admin/GatewayFeeController.php` ou módulo Gateways/Fees, não tocado no piloto |
| `app/Http/Controllers/Backend/Finance/FeeController.php` | Dashboard financeiro de fees | futuro `app/Modules/Fees/Interface/Http/Controllers/Admin/FeeDashboardController.php`, não tocado no piloto |
| `app/Http/Controllers/Backend/PlatformFeeController.php` | endpoints legados `platform-fee/*` | legado/compatibilidade, não tocado no piloto |
| `app/Http/Controllers/Backend/VirtualCardFeeSettingController.php` | taxas específicas de cartão virtual | futuro módulo Cards/Fees, não tocado no piloto |

### Routes atuais

| Atual | Papel atual | Decisão no piloto |
|---|---|---|
| `admin.platform-fees.index` | tela canonical de regras de taxa | mantida |
| `admin.platform-fees.global.store` | cria regra global | mantida |
| `admin.platform-fees.merchant.store` | cria regra merchant | mantida |
| `admin.platform-fees.simulate` | simula taxa | controller passou a chamar Action modular |
| `admin.platform-fees.deactivate` | desativa regra | mantida |
| `admin.gateway-fees.*` | gateway fees/tariffs | não tocado |
| `admin.finance.fees.index` | dashboard finance fees | não tocado |
| `admin.finance.tariffs` | redirect legado para gateway-fees | não tocado |

### Models atuais

| Atual | Target futuro |
|---|---|
| `app/Models/PlatformFeeRule.php` | `app/Modules/Fees/Domain/Models/PlatformFeeRule.php` ou manter em `app/Models` com alias em fase posterior |
| `app/Models/PlatformFeeRuleAudit.php` | `app/Modules/Fees/Domain/Models/PlatformFeeRuleAudit.php` ou manter em `app/Models` com alias em fase posterior |
| `app/Models/PlatformFeeSetting.php` | avaliar legado vs configuração ativa em fase posterior |
| `app/Models/GatewayFeeConfig.php` | possível módulo `Fees` ou `Gateways`, não mover agora |
| `app/Models/FeeRecord.php` | provável read/audit financeiro de fees, não mover agora |

### Services atuais

| Atual | Papel atual | Target futuro |
|---|---|---|
| `app/Services/Fees/PlatformFeeCalculator.php` | cálculo puro de taxa da plataforma | `app/Modules/Fees/Domain/Services/PlatformFeeCalculator.php` |
| `app/Services/Fees/PlatformFeeResolver.php` | resolve regra global/merchant/fallback | `app/Modules/Fees/Application/Queries/ResolvePlatformFeeRuleQuery.php` ou `Application/Services/PlatformFeeResolver.php` |
| `app/Services/Fees/PlatformFeeResult.php` | DTO de resultado | `app/Modules/Fees/Application/Data/PlatformFeeResult.php` |
| `app/Services/PlatformFeeService.php` | serviço legado de fee setting | mapear depois |
| `app/Services/Payment/GatewayFeeService.php` | cálculo gateway fee usado em payment/withdrawal | não tocar por restrição do piloto |
| `app/Services/FeeDashboardService.php` | dashboard legacy/root | mapear depois |
| `app/Services/Finance/FeeDashboardService.php` | dashboard finance fees | mapear depois |
| `app/Services/FeeActionService.php` | ações legacy/root | mapear depois |
| `app/Services/Finance/FeeActionService.php` | ações finance fees | mapear depois |

### Views atuais

| Atual | Target futuro |
|---|---|
| `resources/views/backend/platform_fees/index.blade.php` | `resources/views/admin/fees/platform-rules/index.blade.php` |
| `resources/views/backend/finance/fees.blade.php` | `resources/views/admin/fees/dashboard.blade.php` |
| views de `backend/payment_gateway/*fees*` | provável `admin/gateways/fees`, não tocar agora |

## Escopo do piloto implementado

Fatia escolhida:

```txt
admin.platform-fees.simulate
```

Motivo:

- É uma operação de leitura/simulação.
- Já usa `PlatformFeeResolver` e `PlatformFeeCalculator`.
- Permite extrair Action/Contract sem tocar no fluxo crítico de criação de cobranças.
- Não move models.
- Não altera cálculo.
- Não toca Ledger, Wallets, Payments, Withdrawals ou Gateways.

## Arquivos criados

```txt
app/Modules/Fees/Domain/Contracts/PlatformFeeSimulator.php
app/Modules/Fees/Application/Actions/SimulatePlatformFeeAction.php
tests/Feature/FeesPilotActionTest.php
```

## Arquivos alterados

```txt
app/Http/Controllers/Backend/PlatformFeeRuleController.php
app/Providers/AppServiceProvider.php
```

## Decisão técnica

Foi criado o contract:

```php
App\Modules\Fees\Domain\Contracts\PlatformFeeSimulator
```

Implementação atual:

```php
App\Modules\Fees\Application\Actions\SimulatePlatformFeeAction
```

Binding no container:

```php
$this->app->bind(PlatformFeeSimulator::class, SimulatePlatformFeeAction::class);
```

O controller atual passou a depender do contract:

```php
public function simulate(Request $request, PlatformFeeSimulator $simulator)
```

A Action mantém exatamente a mesma decisão anterior:

- se `rule_id` existir, calcula pela regra específica;
- caso contrário, resolve regra por usuário/método/moeda via `PlatformFeeResolver`.

## Compatibilidade mantida

Rota mantida:

```txt
admin.platform-fees.simulate
```

Controller mantido:

```txt
app/Http/Controllers/Backend/PlatformFeeRuleController.php
```

Services existentes mantidos:

```txt
app/Services/Fees/PlatformFeeResolver.php
app/Services/Fees/PlatformFeeCalculator.php
app/Services/Fees/PlatformFeeResult.php
```

Views mantidas.

Models mantidos em `app/Models`.

## Teste criado

Arquivo:

```txt
tests/Feature/FeesPilotActionTest.php
```

Cobertura:

- simulação usando regra global ativa;
- simulação usando `rule_id` específico.

Resultado focado:

```txt
Tests: 2 passed (7 assertions)
```

## Verificação ad-hoc

Foi criado e removido script temporário sob:

```txt
C:\Users\carlo\AppData\Local\Temp\hermes-verify-*.py
```

Esse script executou:

- `php -l` nos arquivos alterados/criados;
- `php artisan test tests/Feature/FeesPilotActionTest.php`.

Resultado:

```txt
AD_HOC_FEES_PILOT_VERIFICATION_OK
Tests: 2 passed (7 assertions)
```

## Validação canônica

Comandos executados:

```bash
php artisan test
php artisan route:list
npm run build
```

Resultado final:

```txt
exit_code: 0
php artisan test: 396 passed (1353 assertions)
php artisan route:list: passou, 674 rotas
npm run build: passou
```

Log salvo em:

```txt
docs/refactors/fees-pilot-validation.log
```

Aviso não bloqueante no build:

```txt
Browserslist: caniuse-lite is outdated
```

## Revalidação final solicitada

Após a verificação focada, a validação canônica foi executada novamente e registrada em:

```txt
docs/refactors/fees-pilot-validation.log
```

Resultado confirmado no log:

```txt
php artisan test: 396 passed (1353 assertions)
php artisan route:list: passou, 674 rotas
npm run build: passou
exit_code: 0
```

## Riscos residuais

- O módulo Fees ainda possui várias áreas não modularizadas:
  - Platform fee CRUD completo;
  - Platform fee audit;
  - Gateway fees;
  - Finance fee dashboard;
  - Virtual card fee settings;
  - serviços legacy/root duplicados.
- `GatewayFeeService` foi intencionalmente preservado porque toca fluxos de Payment/Withdrawal.
- `ChargeService` continua consumindo `PlatformFeeResolver` diretamente; não foi alterado por restrição de não mexer em Payments.
- Models ainda não foram movidos.

## Próximos passos recomendados

1. Criar Actions para CRUD de PlatformFeeRule:
   - `CreateGlobalPlatformFeeRuleAction`
   - `CreateMerchantPlatformFeeRuleAction`
   - `DeactivatePlatformFeeRuleAction`
   - `RecordPlatformFeeRuleAuditAction`
2. Criar contract para auditoria:
   - `PlatformFeeRuleAuditRecorder`
3. Criar Query para tela admin:
   - `ListPlatformFeeRulesForAdminQuery`
4. Só depois avaliar gateway fees, pois têm relação com Gateways/Payments/Withdrawals.
5. Não mover `PlatformFeeRule` antes de estabilizar Actions/Queries.

## Conclusão

O piloto Fees validou o mesmo padrão modular usado em Webhooks:

```txt
Controller atual -> Domain Contract -> Application Action -> Services existentes
```

A fatia escolhida foi segura porque isolou a simulação de taxa da plataforma sem alterar cálculo, persistência crítica ou fluxos financeiros transacionais.

O padrão modular foi validado em segundo módulo sem quebrar testes, rotas ou build.
