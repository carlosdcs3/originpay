# R6 — Observability, SRE e DR

## Estado até R6.3

- R6.1 concluída: readiness protegido por `X-Monitor-Token`.
- R6.2 concluída: logging estruturado, correlation ID e redaction.
- R6.3 concluída: contexto operacional em `ProcessGatewayWebhookJob`.

## R6.4 — Deep health operacional mínimo e protegido

Endpoint implementado: `GET /api/health/deep`.

Contrato implementado:

```json
{
  "status": "UP|DEGRADED|DOWN",
  "service": "originpay",
  "checked_at": "ISO-8601",
  "checks": {
    "failed_jobs": "OK|WARN|ERROR",
    "queue_backlog": "OK|WARN|ERROR",
    "dlq": "OK|WARN|ERROR",
    "scheduler_freshness": "OK|WARN|ERROR|NOT_IMPLEMENTED"
  },
  "details": {
    "failed_jobs": { "count": "integer|null" },
    "queue_backlog": { "count": "integer|null" },
    "dlq": {
      "pending_count": "integer|null",
      "oldest_pending_age_seconds": "integer|null"
    }
  }
}
```

Política de segurança aplicada:

- exige `X-Monitor-Token` com a mesma política segura do readiness;
- falha fechado quando o token de monitoramento não está configurado;
- é somente leitura;
- não chama PSP;
- não cria pagamentos;
- não despacha, publica, consome, remove ou reserva jobs;
- não altera banco, fila ou DLQ;
- não retorna payloads, exception text, stack trace, SQL, caminhos, credenciais, nomes de segredos ou dados de tenant.

Checks cobertos:

1. `failed_jobs`
   - lê somente o count da tabela configurada em `queue.failed.table`;
   - `OK` quando count é zero;
   - `ERROR` quando count é maior que zero ou a dependência está indisponível;
   - não retorna payload nem exception.

2. `queue_backlog`
   - lê count aproximado/read-only para backend `database`;
   - retorna zero para backends sem backlog persistente (`sync`/`null`);
   - lê `LLEN` para backend `redis` sem publicar/consumir;
   - backend ausente, mal configurado ou não suportado retorna `ERROR`.

3. `dlq`
   - lê count de `webhook_dead_letters` com `status = pending`;
   - lê idade do item pendente mais antigo via `received_at`, quando disponível;
   - `OK` quando não há pendências;
   - `ERROR` quando há pendências ou a dependência está indisponível;
   - não retorna payload, headers, assinatura, gateway, erro bruto ou tenant.

4. `scheduler_freshness`
   - nenhum marcador confiável/canônico de última execução foi encontrado neste incremento;
   - reportado como `NOT_IMPLEMENTED`;
   - não é tratado como heartbeat saudável artificial.

Decisão pendente:

- Não foram inventados thresholds definitivos de WARN para failed jobs, backlog, DLQ ou scheduler. O contrato conservador atual usa `ERROR` para pendências operacionais conhecidas e para dependência indisponível. Thresholds futuros devem vir de configuração explícita/canônica.

## Verificação R6.4

Testes criados em `tests/Feature/R64DeepHealthTest.php`:

- token inválido retorna 401;
- resposta saudável não expõe detalhes internos;
- failed jobs são lidos sem retornar payload/exception;
- backlog é lido sem modificar fila e sem despachar jobs;
- DLQ retorna apenas count/idade sanitizados;
- dependência indisponível retorna `DOWN` com check `ERROR`;
- scheduler sem heartbeat não é falsamente reportado como `OK`.

Comandos executados com PHP canônico:

- `artisan test tests/Feature/R64DeepHealthTest.php`
- `artisan test`
- `vendor/bin/pint app/Http/Controllers/HealthCheckController.php routes/api.php tests/Feature/R64DeepHealthTest.php docs/audit/R6-observability-sre-dr-plan.md`

PHPStan:

- configuração PHPStan não encontrada neste estado local; não executado.

## R6.5 — Thresholds configuráveis e heartbeat canônico do scheduler

Arquitetura implementada:

- thresholds centralizados em `config/observability.php`, com override por env/config;
- defaults provisórios documentados, não SLO definitivo;
- deep health sem números mágicos espalhados no controller;
- heartbeat canônico em `App\Services\Observability\SchedulerHeartbeat`;
- tarefa agendada Laravel `originpay-scheduler-heartbeat`, a cada minuto, com `onOneServer()`;
- heartbeat gravado em cache configurável, preferindo o cache padrão da aplicação e compatível com Redis/database cache em ambientes distribuídos;
- não usa arquivo local como fonte de verdade;
- não executa operação financeira, PSP, saldo, pagamento, settlement ou gateway.

Thresholds provisórios criados:

| Check | Env/config | WARN | ERROR |
| --- | --- | ---: | ---: |
| `failed_jobs` | `ORIGINPAY_DEEP_HEALTH_FAILED_JOBS_WARN` / `ERROR` | 1 | 10 |
| `queue_backlog` | `ORIGINPAY_DEEP_HEALTH_QUEUE_BACKLOG_WARN` / `ERROR` | 100 | 1000 |
| `dlq_count` | `ORIGINPAY_DEEP_HEALTH_DLQ_COUNT_WARN` / `ERROR` | 1 | 10 |
| `dlq_oldest_age_seconds` | `ORIGINPAY_DEEP_HEALTH_DLQ_OLDEST_AGE_WARN_SECONDS` / `ERROR_SECONDS` | 900 | 3600 |
| `scheduler_freshness_seconds` | `ORIGINPAY_DEEP_HEALTH_SCHEDULER_WARN_SECONDS` / `ERROR_SECONDS` | 120 | 300 |

Classificação R6.5:

- `OK`: valor abaixo de WARN;
- `WARN`: valor maior ou igual a WARN e menor que ERROR;
- `ERROR`: valor maior ou igual a ERROR, heartbeat ausente/muito atrasado ou leitura do heartbeat indisponível;
- status global `DOWN`: apenas quando dependência crítica de leitura operacional está indisponível;
- status global `DEGRADED`: quando há `WARN` ou `ERROR` operacional sem indisponibilidade crítica;
- status global `UP`: todos os checks `OK`.

Segurança mantida:

- endpoint segue protegido por `X-Monitor-Token`;
- não retorna valores de configuração, nomes internos desnecessários, chave de cache, stack trace, SQL, payloads ou exception text;
- falha de cache/Redis é sanitizada como `scheduler_freshness = ERROR` sem vazar detalhe interno.

Testes criados em `tests/Feature/R65DeepHealthThresholdsAndSchedulerTest.php`:

- defaults provisórios são aplicados;
- thresholds podem ser sobrescritos por config;
- `failed_jobs` muda entre `OK`, `WARN` e `ERROR`;
- `queue_backlog` muda entre `OK`, `WARN` e `ERROR`;
- DLQ count e idade do item mais antigo respeitam thresholds;
- heartbeat atual retorna `scheduler_freshness = OK`;
- heartbeat atrasado retorna `WARN`;
- heartbeat muito atrasado ou ausente retorna `ERROR`;
- execução do heartbeat não cria operação financeira;
- deep health não expõe configuração interna;
- falha de cache é tratada sem stack trace.

Comandos executados com PHP canônico:

- `artisan test tests/Feature/R64DeepHealthTest.php tests/Feature/R65DeepHealthThresholdsAndSchedulerTest.php`
- `artisan test`
- `vendor/bin/pint app/Http/Controllers/HealthCheckController.php app/Services/Observability/SchedulerHeartbeat.php bootstrap/app.php config/observability.php tests/Feature/R64DeepHealthTest.php tests/Feature/R65DeepHealthThresholdsAndSchedulerTest.php`

PHPStan:

- configuração PHPStan não encontrada neste estado local; não executado.

Risco restante:

- thresholds seguem provisórios até definição formal de SLO/SLA operacional canônico.

## R6.6 — SLIs, SLOs, error budgets e política operacional de alertas

Artefatos criados:

- documento canônico `docs/operations/R6-sli-slo-alert-policy.md`;
- catálogo declarativo local `config/alerts.php`;
- sem integração externa de métricas, logs, tracing, paging, mensagens, backup/restore ou DR.

SLIs definidos:

1. disponibilidade da API;
2. criação de cobranças;
3. processamento de webhooks;
4. confirmação de pagamentos;
5. queue backlog;
6. failed jobs;
7. DLQ;
8. scheduler freshness;
9. settlement;
10. integridade de dados.

Para cada SLI foram documentados:

- definição;
- numerador;
- denominador;
- janela;
- fonte do sinal;
- exclusões;
- risco de cardinalidade;
- limitações atuais.

SLOs:

- não foram declarados SLOs finais/SLA externos;
- foram propostas faixas provisórias onde útil;
- settlement e integridade de dados foram marcados como candidatos internos críticos, com decisão pendente antes de compromisso formal;
- thresholds de `config/observability.php` continuam classificados apenas como sinais operacionais provisórios, não SLO definitivo.

Error budgets:

- fórmula por eventos elegíveis documentada;
- fórmula por minutos degradados para gauges documentada;
- ações por consumo documentadas: acompanhamento, priorização, congelamento de mudanças não essenciais e incidente interno quando excedido.

Alertas catalogados em `config/alerts.php`:

- aumento de 5xx;
- latência degradada;
- falha de criação de cobrança;
- falha de webhook;
- backlog de fila;
- failed jobs;
- DLQ crescente;
- scheduler atrasado;
- gateway degradado;
- settlement inconsistente;
- banco indisponível;
- Redis indisponível;
- abuso de API Key;
- falha futura de backup/restore apenas como política futura.

Testes criados em `tests/Feature/R66AlertPolicyCatalogTest.php`:

- catálogo possui campos obrigatórios;
- severidades são válidas;
- todo alerta referencia runbook em `docs/operations/`;
- nenhum alerta habilita integração externa em R6.6;
- cobertura mínima dos alertas obrigatórios;
- thresholds operacionais não são confundidos com SLO definitivo;
- configuração não contém marcadores de segredo bruto.

Comandos executados com PHP canônico:

- `artisan test tests/Feature/R66AlertPolicyCatalogTest.php`
- `artisan test`
- `vendor/bin/pint config/alerts.php tests/Feature/R66AlertPolicyCatalogTest.php`

PHPStan:

- configuração PHPStan não encontrada neste estado local; não executado.

Decisões pendentes:

- SLOs finais e SLAs externos dependem de histórico real e decisão operacional;
- owners nominais/escalas formais ainda devem ser definidos;
- integração com stack externa de observabilidade permanece fora do escopo desta fase.

## R6.7 — Baseline histórico e fundação de coleta operacional

Arquitetura implementada:

- camada local e desacoplada em `App\Support\Observability\Metrics`;
- contrato `MetricsStore` para storage local/backend-neutral;
- `InMemoryMetricsStore` leve para testes e baseline efêmero;
- `LocalMetricsCollector` best-effort para contadores, gauges e distribuições;
- configuração em `config/observability.php` para allowlist de labels, limite de cardinalidade e política de retenção;
- documentação operacional em `docs/operations/R6-metrics-baseline.md`.

Sinais cobertos para coleta segura local:

1. request count;
2. status codes por `status_class`;
3. duration agregada;
4. charge creation result;
5. webhook processing result;
6. payment confirmation result;
7. queue backlog;
8. failed jobs;
9. DLQ count/age;
10. scheduler freshness;
11. settlement result;
12. authorization failures;
13. API key failures;
14. rate limit events.

Labels permitidas:

- `route_name`;
- `method`;
- `status_class`;
- `gateway`;
- `result`;
- `queue`;
- `operation`;
- `reason` para motivos internos controlados.

Política aplicada:

- labels fora da allowlist são removidas antes de armazenamento;
- IDs pessoais/financeiros, `correlation_id`, API keys, webhook event IDs, IPs, URLs arbitrárias, exception messages e segredos não são armazenados;
- cardinalidade limitada por `ORIGINPAY_METRICS_BASELINE_MAX_SERIES`, default `100` séries por métrica;
- séries excedentes são descartadas e contabilizadas em `metrics_dropped_total` com `reason=cardinality_limit`;
- falha no storage de métricas não quebra o fluxo principal.

Retenção definida:

- janela mínima recomendada: 30 dias;
- granularidade: agregados de 1 minuto para inspeção recente e rollups diários quando houver persistência futura;
- expiração: 90 dias para baseline local;
- reset: permitido entre ambientes ou após mudança de schema/política;
- limitação: baseline local não é observabilidade final de produção, SLO definitivo, SLA ou DR.

Testes criados em `tests/Feature/R67MetricsBaselineTest.php`:

- coleta de requests por status class;
- duração agregada sem armazenar `correlation_id`;
- eventos financeiros agregados sem IDs pessoais/financeiros;
- labels fora da allowlist removidas;
- nenhum segredo armazenado;
- cardinalidade limitada;
- falha na coleta não quebra o fluxo principal.

Comandos executados com PHP canônico:

- `artisan test tests/Feature/R67MetricsBaselineTest.php`
- `artisan test`
- `vendor/bin/pint app/Support/Observability/Metrics config/observability.php tests/Feature/R67MetricsBaselineTest.php`

PHPStan:

- configuração PHPStan não encontrada neste estado local; não executado.

Decisões pendentes:

- persistência durável e rollups reais ficam para incremento futuro;
- integração com stack externa de observabilidade permanece fora do escopo;
- baseline histórico ainda não define SLO/SLA final.

## R6.8 — Persistência de counters em Redis

- criado `RedisMetricsStore`, mantendo `InMemoryMetricsStore` disponível;
- counters persistem entre instâncias e recebem TTL renovado a cada escrita;
- coleta continua best-effort, com allowlist de labels e limite de cardinalidade existentes;
- distribuições/gauges permanecem compatíveis, sem novos fluxos ou rollups;
- verificação direcionada: `tests/Feature/R68RedisMetricsStoreTest.php` (4 testes, 7 assertions).

## R6.9 — Integração real e instrumentação crítica inicial

- binding configurável de `MetricsStore`: `redis` por padrão e `memory` apenas explícito;
- `LocalMetricsCollector` registrado como singleton best-effort;
- instrumentação inicial de requests HTTP, `ProcessGatewayWebhookJob` e criação de cobrança da API;
- testes direcionados: 6 aprovados, 1 ignorado (15 assertions); Redis real foi ignorado porque a extensão/cliente Redis não está disponível no PHP canônico local;
- regressão única: 370 aprovados, 1 ignorado e 112 falhas pela indisponibilidade da classe `Redis` ao resolver o backend padrão;
- Pint aplicado; PHPStan não executado por ausência de configuração.

Correção conclusiva da R6.9:

- resolução lazy e protegida do backend; construção, configuração, binding e conexão falham aberto;
- `NoOpMetricsStore` é usado quando Redis está indisponível ou o driver é inválido; `memory` permanece explícito;
- instrumentações confirmadas em request HTTP, `ProcessGatewayWebhookJob` e `App\Services\ChargeService`;
- testes direcionados/fluxos afetados: 14 aprovados, 1 ignorado (71 assertions);
- regressão: 484 aprovados, 1 ignorado (2215 assertions);
- Pint aprovado; PHPStan não executado por ausência de configuração;
- teste Redis real permanece ignorado porque extensão/servidor não estão disponíveis no PHP canônico.

R6.9 concluída com coleta integralmente fail-open; persistência Redis real continua dependência operacional não validada neste ambiente.

## R6.10 — Encerramento da infraestrutura de métricas

- namespace Redis configurável e validado, com índices, reset e snapshots isolados;
- counters mantêm incremento atômico via `HINCRBYFLOAT` e TTL renovado;
- teste real cobre persistência, TTL, atomicidade, isolamento, concorrência básica e reconnect;
- ausência de extensão ou servidor mantém skip automático e fallback fail-open, sem alterar o ambiente;
- direcionados: 9 aprovados, 2 ignorados (22 assertions);
- regressão: 485 aprovados, 2 ignorados (2217 assertions);
- Pint aprovado; PHPStan não executado por ausência de configuração.

R6 concluída; validação real permanece condicional à disponibilidade operacional de Redis.
