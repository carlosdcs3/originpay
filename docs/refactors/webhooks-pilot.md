# Webhooks Modular Refactor Pilot

Data: 2026-07-09

## Objetivo

Validar o padrão modular proposto em `docs/originpay-architecture-blueprint.md` usando o módulo Webhooks como piloto de baixo risco.

## Restrições aplicadas

- Não mover arquivos grandes.
- Não alterar regra de negócio.
- Não mexer em Ledger, Wallets, Payments, Withdrawals ou Gateways.
- Manter rotas/controllers atuais compatíveis.
- Extrair somente Actions/Contracts mínimos.
- Rodar testes, route-list e build.

## Current -> Target map

### Controllers atuais

| Atual | Papel atual | Target futuro |
|---|---|---|
| `app/Http/Controllers/Backend/WebhookAdminController.php` | Admin panel de eventos, DLQ, replay e resolução manual | `app/Modules/Webhooks/Interface/Http/Controllers/Admin/WebhookAdminController.php` |
| `app/Http/Controllers/Admin/WebhookEndpointController.php` | CRUD JSON de endpoints admin | `app/Modules/Webhooks/Interface/Http/Controllers/Admin/WebhookEndpointController.php` |
| `app/Http/Controllers/User/Developer/WebhookController.php` | Webhooks do developer hub do usuário/merchant | `app/Modules/Webhooks/Interface/Http/Controllers/App/DeveloperWebhookController.php` |
| `app/Http/Controllers/Api/V1/Webhooks/EfiWebhookController.php` | Inbound webhook EFI | futuro módulo `Gateways`/`Webhooks/Inbound`, não tocado no piloto |
| `app/Http/Controllers/Webhook/GatewayWebhookController.php` | Inbound gateway webhook genérico | futuro módulo `Gateways`/`Webhooks/Inbound`, não tocado no piloto |

### Models atuais

| Atual | Target futuro |
|---|---|
| `app/Models/WebhookEvent.php` | `app/Modules/Webhooks/Domain/Models/WebhookEvent.php` ou manter em `app/Models` com alias em fase posterior |
| `app/Models/WebhookDlq.php` | `app/Modules/Webhooks/Domain/Models/WebhookDlq.php` ou manter em `app/Models` com alias em fase posterior |
| `app/Models/WebhookEndpoint.php` | `app/Modules/Webhooks/Domain/Models/WebhookEndpoint.php` ou manter em `app/Models` com alias em fase posterior |
| `app/Models/WebhookDelivery.php` | `app/Modules/Webhooks/Domain/Models/WebhookDelivery.php` ou manter em `app/Models` com alias em fase posterior |
| `app/Models/WebhookAdminAudit.php` | `app/Modules/Webhooks/Domain/Models/WebhookAdminAudit.php` ou manter em `app/Models` com alias em fase posterior |

### Services atuais

| Atual | Papel atual | Target futuro |
|---|---|---|
| `app/Services/Webhooks/WebhookSignatureService.php` | assinatura outbound | `app/Modules/Webhooks/Domain/Services/WebhookSignatureService.php` ou contract `WebhookSigner` |
| `app/Services/Webhooks/WebhookEventService.php` | cria eventos outbound e deliveries | `app/Modules/Webhooks/Application/Actions/DispatchWebhookEventAction.php` |
| `app/Services/Webhooks/WebhookDeliveryService.php` | entrega HTTP, retry/dead-letter | `app/Modules/Webhooks/Application/Actions/AttemptWebhookDeliveryAction.php` + infrastructure HTTP client |
| `app/Services/WebhookDispatcher.php` | dispatcher legado/operacional | mapear em fase posterior |
| `app/Services/WebhookProcessingService.php` | processamento inbound/legacy | mapear em fase posterior |

### Jobs atuais

| Atual | Target futuro |
|---|---|
| `app/Jobs/ReplayWebhookJob.php` | manter por enquanto; depois `app/Modules/Webhooks/Interface/Jobs/ReplayWebhookJob.php` chamando Action |
| `app/Jobs/ProcessWebhookJob.php` | manter por enquanto; depois `app/Modules/Webhooks/Interface/Jobs/ProcessWebhookJob.php` |
| `app/Jobs/RetryWebhookDeliveryJob.php` | manter por enquanto; depois `app/Modules/Webhooks/Interface/Jobs/RetryWebhookDeliveryJob.php` |

### Views atuais

| Atual | Target futuro |
|---|---|
| `resources/views/backend/webhooks/index.blade.php` | `resources/views/admin/webhooks/index.blade.php` |
| `resources/views/backend/webhooks/show.blade.php` | `resources/views/admin/webhooks/show.blade.php` |
| `resources/views/backend/webhooks/show_dlq.blade.php` | `resources/views/admin/webhooks/show-dlq.blade.php` |
| `resources/views/frontend/docs/pages/webhooks.blade.php` | `resources/views/public/docs/webhooks.blade.php` |
| `resources/views/frontend/docs/webhook_simulator.blade.php` | `resources/views/developer/webhooks/simulator.blade.php` |

## Escopo do piloto implementado

O piloto focou apenas no fluxo admin de Webhooks/DLQ já coberto por `tests/Feature/WebhookAdminPanelTest.php`.

Fluxos extraídos:

- registrar auditoria admin;
- replay de um item DLQ;
- replay em lote de itens DLQ;
- resolução manual de evento/DLQ.

Não foram alterados:

- inbound gateway webhooks;
- EFI webhook;
- entrega outbound HTTP;
- assinatura de webhook;
- endpoints developer/user;
- regras de retry existentes fora do admin panel.

## Arquivos criados

```txt
app/Modules/Webhooks/Domain/Contracts/WebhookAdminAuditRecorder.php
app/Modules/Webhooks/Infrastructure/Persistence/EloquentWebhookAdminAuditRecorder.php
app/Modules/Webhooks/Application/Actions/RecordWebhookAdminAuditAction.php
app/Modules/Webhooks/Application/Actions/ReprocessWebhookDlqAction.php
app/Modules/Webhooks/Application/Actions/ReprocessWebhookDlqBatchAction.php
app/Modules/Webhooks/Application/Actions/ResolveWebhookItemManuallyAction.php
```

## Arquivos alterados

```txt
app/Http/Controllers/Backend/WebhookAdminController.php
app/Providers/AppServiceProvider.php
```

## Decisão técnica

Foi criado o contract:

```php
App\Modules\Webhooks\Domain\Contracts\WebhookAdminAuditRecorder
```

Implementação atual:

```php
App\Modules\Webhooks\Infrastructure\Persistence\EloquentWebhookAdminAuditRecorder
```

Binding no container:

```php
$this->app->bind(WebhookAdminAuditRecorder::class, EloquentWebhookAdminAuditRecorder::class);
```

Motivo:

- manter controller compatível;
- isolar persistência de auditoria admin;
- validar padrão Domain Contract + Infrastructure Adapter;
- não mover Model nem alterar tabela.

## Compatibilidade mantida

Rotas atuais mantidas:

```txt
admin.webhooks.index
admin.webhooks.showEvent
admin.webhooks.showDlq
admin.webhooks.reprocessSingle
admin.webhooks.reprocessBatch
admin.webhooks.resolveManual
```

Views atuais mantidas:

```txt
backend.webhooks.index
backend.webhooks.show
backend.webhooks.show_dlq
```

Jobs atuais mantidos:

```txt
App\Jobs\ReplayWebhookJob
App\Jobs\ProcessWebhookJob
```

## Resultado funcional

O comportamento do controller foi preservado:

- listagem por abas continua no controller;
- `showEvent` continua mascarando payload/headers e auditando visualização;
- `showDlq` continua mascarando payload/headers e auditando visualização;
- `reprocessSingle` continua validando reason quando DLQ já resolvida;
- `reprocessBatch` continua limitado a 50 itens;
- `resolveManual` continua exigindo reason e atualizando `resolved_at`/status.

## Verificação ad-hoc

Foi criado e removido script temporário sob:

```txt
C:\Users\carlo\AppData\Local\Temp\hermes-verify-*.py
```

Esse script executou:

- `php -l` nos arquivos alterados;
- `php artisan test tests/Feature/WebhookAdminPanelTest.php`.

Resultado:

```txt
AD_HOC_WEBHOOKS_PILOT_VERIFICATION_OK
Tests: 7 passed (24 assertions)
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
php artisan test: passou
php artisan route:list: passou, 674 rotas
npm run build: passou
```

Log:

```txt
docs/refactors/webhooks-pilot-validation.log
```

Observação do build:

```txt
Browserslist: caniuse-lite is outdated
```

Aviso não bloqueante; build finalizou com sucesso.

## Riscos residuais

- Ainda existem múltiplos fluxos de Webhooks fora do piloto: inbound gateway, EFI, developer endpoints e delivery outbound.
- Models continuam em `app/Models`.
- Jobs continuam em `app/Jobs`.
- Services antigos continuam ativos.
- O piloto valida o padrão, mas ainda não modulariza todo o domínio Webhooks.

## Próximos passos recomendados

1. Criar Actions para endpoint admin:
   - `CreateWebhookEndpointAction`
   - `UpdateWebhookEndpointAction`
   - `RotateWebhookSecretAction`
   - `DeleteWebhookEndpointAction`
   - `DispatchTestWebhookAction`
2. Criar contract `WebhookSigner` para `WebhookSignatureService`.
3. Criar Action `DispatchWebhookEventAction` envolvendo `WebhookEventService`.
4. Fazer `ReplayWebhookJob` chamar uma Action em vez de conter regra.
5. Só depois avaliar movimentação de Models/Jobs.

## Conclusão

O piloto Webhooks validou o padrão modular com baixo risco.

A refatoração introduziu uma fatia vertical pequena:

```txt
Controller atual -> Application Actions -> Domain Contract -> Infrastructure Persistence
```

Sem alterar regra de negócio, sem mover arquivos grandes e sem tocar nos módulos financeiros críticos.
