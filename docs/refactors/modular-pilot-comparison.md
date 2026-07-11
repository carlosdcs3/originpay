# Modular Refactor Pilot Comparison

Data: 2026-07-09

## Objetivo

Comparar os dois pilotos modulares já executados:

- `docs/refactors/webhooks-pilot.md`
- `docs/refactors/fees-pilot.md`

E transformar o padrão validado em regra oficial para próximas refatorações do OriginPay.

## Resultado executivo

Os dois pilotos passaram usando o mesmo padrão:

```txt
Controller atual -> Contract -> Application Action -> implementação/serviço existente
```

Sem mover arquivos grandes, sem alterar regra de negócio e mantendo compatibilidade com rotas/controllers/views atuais.

## Comparativo

| Critério | Webhooks pilot | Fees pilot |
|---|---|---|
| Módulo | Webhooks | Fees |
| Risco | Baixo/médio | Baixo |
| Fatia escolhida | Admin Webhooks/DLQ | Simulação de platform fee |
| Controller alterado | `Backend/WebhookAdminController.php` | `Backend/PlatformFeeRuleController.php` |
| Contract criado | `WebhookAdminAuditRecorder` | `PlatformFeeSimulator` |
| Actions criadas | 4 Actions admin/DLQ | 1 Action de simulação |
| Infrastructure criada | `EloquentWebhookAdminAuditRecorder` | não necessário |
| Teste criado | reaproveitou `WebhookAdminPanelTest` | criou `FeesPilotActionTest` |
| Rotas mantidas | sim | sim |
| Views mantidas | sim | sim |
| Models movidos | não | não |
| Jobs movidos | não | não |
| Regra de negócio alterada | não | não |
| Validação canônica | passou | passou |

## Webhooks pilot

### Escopo

Fluxos admin/DLQ:

- registrar auditoria de visualização;
- reprocessar item DLQ;
- reprocessar batch DLQ;
- resolver evento/DLQ manualmente.

### Padrão aplicado

```txt
WebhookAdminController
  -> RecordWebhookAdminAuditAction
  -> ReprocessWebhookDlqAction
  -> ReprocessWebhookDlqBatchAction
  -> ResolveWebhookItemManuallyAction
  -> WebhookAdminAuditRecorder contract
  -> EloquentWebhookAdminAuditRecorder
```

### Resultado

```txt
php artisan test: passou
php artisan route:list: passou
npm run build: passou
```

## Fees pilot

### Escopo

Fluxo seguro:

```txt
admin.platform-fees.simulate
```

### Padrão aplicado

```txt
PlatformFeeRuleController
  -> PlatformFeeSimulator contract
  -> SimulatePlatformFeeAction
  -> PlatformFeeResolver / PlatformFeeCalculator existentes
```

### Resultado

```txt
php artisan test: 396 passed (1353 assertions)
php artisan route:list: passou, 674 rotas
npm run build: passou
```

## Regra oficial de refatoração modular

A partir dos dois pilotos, a regra oficial para próximos módulos é:

### 1. Começar por mapa current -> target

Antes de alterar código, documentar:

- controllers atuais;
- routes atuais;
- models atuais;
- services atuais;
- jobs/events/listeners;
- views/assets;
- target futuro.

### 2. Escolher fatia pequena

A primeira extração do módulo deve ser:

- pequena;
- testável;
- coberta por rota/teste existente ou fácil de testar;
- sem mudança de regra de negócio;
- sem mover models/jobs/views.

### 3. Criar Contract quando houver dependência de boundary

Usar contract quando a Action precisa esconder:

- persistência;
- integração;
- adapter;
- cálculo ou serviço que será realocado depois.

### 4. Criar Application Action

A Action deve conter o caso de uso extraído.

Controller não deve conter orquestração nova.

Padrão:

```php
public function store(Request $request, SomeActionContract $action)
{
    $result = $action->execute($validated);

    return ...;
}
```

### 5. Manter serviços existentes inicialmente

Na primeira fase, a Action pode chamar services antigos.

Não mover tudo de uma vez.

Objetivo da fase piloto:

```txt
isolar orchestration primeiro, mover implementação depois
```

### 6. Não mover Models no piloto

Models ficam onde estão até:

- Actions estabilizadas;
- tests cobrindo fluxo;
- route-list verde;
- build verde;
- plano de alias/compatibilidade definido.

### 7. Não mover Views no piloto

Views só mudam em fase visual/presentation posterior.

### 8. Validar em três níveis

Para cada piloto:

1. Teste focado.
2. Verificação ad-hoc com script temporário `hermes-verify-*` quando exigido.
3. Validação canônica:

```bash
php artisan test
php artisan route:list
npm run build
```

### 9. Documentar sempre

Cada piloto deve gerar:

```txt
docs/refactors/{module}-pilot.md
docs/refactors/{module}-pilot-validation.log
```

### 10. Só avançar para módulos críticos depois de 2+ pilotos verdes

Critérios agora atingidos:

- Webhooks pilot passou.
- Fees pilot passou.

Mesmo assim, ainda não é recomendável ir direto para Ledger/Wallets/Payments/Withdrawals/Gateways.

## Módulos proibidos para próxima etapa imediata

Ainda não refatorar:

```txt
Ledger
Wallets
Payments
Withdrawals
Gateways
```

Motivo:

- alto risco financeiro;
- forte acoplamento transacional;
- dependem de invariantes, idempotência e consistência contábil.

## Próximo módulo recomendado

Antes de módulos críticos, fazer um terceiro piloto não crítico.

Candidatos:

1. `Notifications`
2. `Support`
3. `Developer`
4. `Cms/PublicSite`
5. `Platform`

Recomendação principal:

```txt
Notifications
```

Motivo:

- tem boundary claro;
- pode usar contract para sender/template;
- baixo risco financeiro;
- testa Events/Listeners/Actions sem mexer em Ledger/Payments.

## Template oficial para próximos pilotos

```txt
1. Ler blueprint.
2. Mapear current -> target.
3. Escolher uma fatia pequena.
4. Criar Contract se houver boundary.
5. Criar Application Action.
6. Alterar controller/job/listener mínimo para usar Action.
7. Criar/atualizar teste focado.
8. Rodar teste focado.
9. Rodar verificação ad-hoc se exigida.
10. Rodar:
    - php artisan test
    - php artisan route:list
    - npm run build
11. Salvar log.
12. Documentar resultado, riscos e próximos passos.
```

## Padrão de diretório validado

```txt
app/Modules/{Module}/
  Application/
    Actions/
  Domain/
    Contracts/
  Infrastructure/
    Persistence/        # quando necessário
```

Esse padrão é suficiente para pilotos iniciais.

Outras pastas (`Queries`, `DTOs`, `Jobs`, `Listeners`, `Presentation`) só devem ser criadas quando houver necessidade real.

## Conclusão

O padrão modular foi validado em dois contextos diferentes:

- Webhooks: fluxo operacional/admin com persistência de auditoria e jobs.
- Fees: fluxo de simulação/cálculo com services existentes.

A regra oficial é avançar incrementalmente por Actions e Contracts, preservando compatibilidade e só movendo arquivos grandes em fases posteriores.
