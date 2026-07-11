# 09 — Arquitetura Oficial de Gateway da OriginPay

Status: **obrigatório para novas integrações**  
Escopo: PSPs, métodos de pagamento, webhooks, settlement, ledger, observabilidade e migração das camadas legadas.

## Decisão oficial

A camada **`app/Gateway`** é a autoridade arquitetural oficial da OriginPay para integrações com provedores de pagamento.

Nenhum novo gateway, método de pagamento ou integração PSP deve ser implementado em `app/Payment`, `app/Payment/Modern` ou em providers soltos fora de `app/Gateway`.

## Justificativa técnica

A decisão é baseada nas evidências do código atual:

- `app/Gateway` já contém contratos explícitos (`Contracts/GatewayProviderInterface.php`, `Contracts/GatewayWebhookValidatorInterface.php`).
- `app/Gateway` já contém DTOs tipados de fronteira (`Contracts/Data/*`).
- `app/Gateway` já contém pipeline HTTP (`Http/Middlewares/*`, `Http/Transports/*`, `Http/Response/*`).
- `app/Gateway` já contém autenticação extensível (`Security/Drivers/*`, `GatewayAuthenticationRegistry`, `GatewayAuthenticationService`).
- `app/Gateway` já contém retry, circuit breaker, métricas e event dispatching.
- `GatewayResolver` já concentra resolução/roteamento por operação e fallback.
- `GatewayManager` já faz ponte com adapters legados e com providers novos.

As camadas `app/Payment` e `app/Payment/Modern` permanecem como legado/migração, não como destino para novas integrações.

## Regra absoluta

A partir desta especificação:

1. Novo PSP nasce em `app/Gateway/Providers/{Provider}`.
2. Novo contrato/DTO nasce em `app/Gateway/Contracts` ou `app/Gateway/Contracts/Data`.
3. Novo fluxo HTTP usa `GatewayHttpClient` e o pipeline `app/Gateway/Http`.
4. Webhook inbound usa `GatewayWebhookController`, `GatewayWebhookValidationService`, `webhook_events` e `webhook_dead_letters`.
5. Resultado financeiro só pode alterar saldo via serviços financeiros/ledger transacionais existentes; provider não credita saldo diretamente.
6. Observabilidade deve emitir métricas, logs estruturados e correlation id.
7. Testes são obrigatórios antes de habilitar provider em produção.

## Arquitetura alvo

```text
Merchant/API/Admin
    |
    v
Application Service
    |
    v
GatewayResolver  ---> PaymentMethodRoute / PaymentGateway / Circuit Breaker / Health Score
    |
    v
GatewayManager
    |
    v
Provider em app/Gateway/Providers/{Provider}
    |
    v
GatewayHttpClient
    |
    v
Pipeline HTTP: correlation -> auth -> retry -> circuit breaker -> transport -> metrics
    |
    v
PSP externo
    |
    v
Response Mapper / DTO GatewayResponse
    |
    v
Application Service
    |
    v
Charge / Settlement / Ledger / Audit / Webhook outbound
```

## Módulos oficiais

### 1. Provider

Local oficial:

```text
app/Gateway/Providers/{Provider}/
```

Responsabilidade:

- Traduzir operação de domínio para payload PSP.
- Chamar `GatewayHttpClient`.
- Mapear resposta PSP para `GatewayResponse`.
- Não alterar saldo.
- Não gravar ledger diretamente.
- Não executar regra financeira fora da transação de domínio.

### 2. Contracts

Local oficial:

```text
app/Gateway/Contracts/
app/Gateway/Contracts/Data/
app/Gateway/Contracts/Enums/
```

Responsabilidade:

- Definir interface mínima de provider.
- Definir operações (`GatewayOperation`).
- Definir DTOs de entrada/saída e health.

### 3. DTOs

DTO oficial de fronteira deve ficar em `app/Gateway/Contracts/Data`.

DTOs de `app/Payment/Modern/DTO` são legado e não devem ser usados por novos PSPs.

### 4. Pipeline HTTP

Local oficial:

```text
app/Gateway/Http/
```

Pipeline obrigatório:

1. Correlation id.
2. Autenticação.
3. Retry policy.
4. Circuit breaker.
5. Transporte.
6. Métricas.
7. Response mapping.

### 5. Webhook inbound

Fluxo oficial:

```text
PSP
  -> route /api/webhooks/gateway/{provider}
  -> GatewayWebhookValidationService
  -> webhook_events (persistir antes da fila)
  -> ProcessGatewayWebhookJob
  -> adapter/provider normalize
  -> serviço financeiro transacional
  -> ledger/auditoria
  -> webhook_dead_letters em falha final
```

Regras obrigatórias:

- Webhook inválido nunca altera estado financeiro.
- Evento deve ser persistido antes de processamento assíncrono.
- Replay deve ser bloqueado por chave idempotente.
- Reprocessamento deve preservar payload, headers, provider, assinatura e timestamp originais.
- DLQ canônica inbound: `webhook_dead_letters`.

### 6. Settlement

Fluxo alvo:

```text
Provider settlement event/report
  -> Gateway provider/settlement parser
  -> settlement domain service
  -> reconciliation
  -> ledger entries
  -> audit/metrics
```

Provider apenas entrega dados normalizados. Liquidação, conciliação e ledger pertencem ao domínio financeiro.

### 7. Ledger

Regras:

- Provider nunca credita saldo diretamente.
- Toda mutação financeira passa por serviço transacional com idempotência.
- Eventos idempotentes devem possuir correlation id e external/provider reference.
- Reprocessamento não pode gerar lançamento duplicado.

### 8. Observabilidade

Obrigatório para todo provider:

- Correlation id por requisição.
- Métricas de latência, erro, timeout, retry e circuit breaker.
- Logs estruturados sem segredo.
- Health check via `checkHealth()` quando aplicável.
- Alertas para DLQ, falha de autenticação e degradação do PSP.

## Como implementar novo PSP

### Estrutura mínima

```text
app/Gateway/Providers/Acme/AcmeProvider.php
app/Gateway/Providers/Acme/AcmeWebhookValidator.php
app/Gateway/Exceptions/Mappers/AcmeExceptionMapper.php (se necessário)
tests/Feature/Gateway/AcmeGatewayTest.php
tests/Feature/Webhooks/AcmeWebhookTest.php
```

### Fluxo obrigatório

1. **Provider**
   - Implementa `App\Gateway\Contracts\GatewayProviderInterface`.
   - Define `getIdentifier()`.
   - Implementa `sendRequest(GatewayOperation $operation, array $payload): GatewayResponse`.
   - Implementa `checkHealth(): GatewayHealthData`.

2. **Contracts/DTOs**
   - Reusar `GatewayOperation`, `GatewayResponse`, `GatewayCredentials`, `GatewayHealthData`.
   - Criar DTO novo somente se não existir equivalente.

3. **Pipeline**
   - Usar `GatewayHttpClient`.
   - Usar drivers de autenticação em `Security/Drivers`.
   - Usar retry/circuit breaker/métricas do pipeline.

4. **Webhook**
   - Criar validator em `app/Gateway/Providers/{Provider}/{Provider}WebhookValidator.php`.
   - Validar assinatura, timestamp e shape.
   - Normalizar para DTO/evento interno.
   - Persistir antes da fila.

5. **Settlement**
   - Normalizar settlement externo para domínio interno.
   - Entregar a serviço de settlement/conciliação.
   - Nunca creditar direto no provider.

6. **Ledger**
   - Usar serviço financeiro/ledger transacional.
   - Chave idempotente obrigatória.
   - Lançamentos devem ser rastreáveis ao event/provider reference.

7. **Observabilidade**
   - Métrica de operação.
   - Métrica de latência.
   - Log com provider, operation, correlation id e resultado.
   - Sem segredos em log.

8. **Testes**
   - Sucesso por operação suportada.
   - Erro do PSP.
   - Timeout/retry.
   - Circuit breaker.
   - Webhook válido.
   - Webhook inválido.
   - Replay/idempotência.
   - Falha final para DLQ.
   - Não duplicidade financeira.

## Política para camadas legadas

### `app/Payment`

Classificação: legado.

Uso permitido:

- Compatibilidade enquanto houver chamadas existentes.
- Nenhum novo PSP.
- Nenhuma nova regra financeira.

Destino:

- Migrar providers ativos para `app/Gateway`.
- Remover após teste de paridade e ausência de referências.

### `app/Payment/Modern`

Classificação: ponte parcialmente migrada/legado moderno.

Uso permitido:

- Apenas manter fluxos existentes até migração.

Destino:

- Consolidar DTOs equivalentes em `app/Gateway/Contracts/Data`.
- Migrar `Providers/EfiGateway.php` para provider/adapters oficiais em `app/Gateway`.
- Remover `NewProviderGateway.php` se for scaffold/exemplo sem uso produtivo.

### `app/Gateway`

Classificação: autoridade oficial.

Uso permitido:

- Único destino para novos PSPs.
- Evolução incremental para substituir adapters legados.

## Critérios objetivos de conclusão da migração

A migração será considerada concluída quando:

1. Não houver referência runtime a `app/Payment/*PaymentGateway.php`.
2. Não houver referência runtime a `app/Payment/Modern/*`.
3. Todos os PSPs ativos estiverem em `app/Gateway/Providers/{Provider}`.
4. Todos os webhooks inbound financeiros passarem por `webhook_events` e `webhook_dead_letters`.
5. Todos os PSPs tiverem testes de contrato, webhook, idempotência, DLQ e não duplicidade financeira.
6. `GatewayResolver` e `GatewayManager` forem o único caminho de resolução/execução PSP.
7. Admin/seeders/rotas não apontarem para adapters/classes legadas.
8. Observabilidade estiver padronizada para todos os PSPs ativos.

## Proibição explícita

É proibido implementar novo gateway em:

- `app/Payment/{Provider}`
- `app/Payment/Modern/Providers`
- `app/Services/Gateways/Adapters` como destino final
- Controller direto com lógica PSP
- Job direto com lógica PSP que bypassa `app/Gateway`

Exceção: código temporário de migração deve ter issue/plano, testes de paridade e data de remoção.
