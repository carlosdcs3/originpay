# R6.7 — Baseline histórico e coleta operacional local

## Escopo

A R6.7 define uma fundação mínima, local e desacoplada para coletar sinais operacionais agregados. Ela não integra Prometheus, Grafana, Loki, OpenTelemetry, PagerDuty, Slack, e-mail, SMS, tracing distribuído, backup/restore ou DR.

A coleta local é apenas baseline histórico inicial. Ela não substitui observabilidade final de produção.

## Arquitetura de coleta

- `App\Support\Observability\Metrics\LocalMetricsCollector`: fachada best-effort para registrar contadores, gauges e distribuições.
- `App\Support\Observability\Metrics\MetricsStore`: contrato de storage local.
- `App\Support\Observability\Metrics\InMemoryMetricsStore`: storage leve para testes e baseline efêmero.
- `config/observability.php`: allowlist de labels, limite de cardinalidade e política de retenção do baseline.

Falhas na coleta são absorvidas e não quebram o fluxo principal.

## Sinais cobertos

Sinais já seguros para coleta local agregada:

1. request count por `route_name`, `method` e `status_class`;
2. status codes normalizados para `status_class`;
3. duration como distribuição agregada;
4. charge creation result por `operation`, `gateway` e `result`;
5. webhook processing result por `operation`, `gateway` e `result`;
6. payment confirmation result por `operation`, `gateway` e `result`;
7. queue backlog por `queue`;
8. failed jobs sem payload/exception;
9. DLQ count/age sem payload, header, assinatura ou tenant;
10. scheduler freshness sem chave de cache/host;
11. settlement result por `operation`, `gateway` e `result`;
12. authorization failures por operação controlada;
13. API key failures por operação controlada;
14. rate limit events por rota/método controlados.

## Labels permitidas

Allowlist inicial:

- `route_name`;
- `method`;
- `status_class`;
- `gateway`;
- `result`;
- `queue`;
- `operation`;
- `reason` apenas para motivos internos controlados como `cardinality_limit`.

Labels fora da allowlist são removidas antes de armazenamento.

## Labels proibidas

Nunca usar como labels ou valores armazenados:

- `user_id`;
- `merchant_id`;
- `payment_id`;
- `correlation_id`;
- API key;
- webhook event ID;
- IP;
- URL arbitrária;
- exception message;
- headers, Authorization, cookies, client secrets, bearer tokens ou payloads brutos.

## Cardinalidade

- Agregações devem usar dimensões controladas e de baixo volume.
- `route_name` deve ser nome de rota, não path bruto.
- HTTP status deve ser agrupado por classe (`2xx`, `4xx`, `5xx`), não por código completo como dimensão primária.
- O limite inicial é `ORIGINPAY_METRICS_BASELINE_MAX_SERIES`, default `100` séries por métrica.
- Séries excedentes são descartadas e incrementam `metrics_dropped_total{reason=cardinality_limit}`.

## Retenção e agregação

Política inicial:

- janela mínima recomendada: 30 dias para formar baseline operacional;
- granularidade: agregados de 1 minuto para inspeção recente; rollups diários quando houver persistência em incremento futuro;
- expiração: 90 dias para dados locais de baseline;
- reset: permitido entre ambientes ou após mudança de schema/política; reset local não deve ser tratado como perda de observabilidade de produção;
- limitações: dados locais são auxiliares e não são evidência final de produção, SLO definitivo, SLA ou DR.

## Segurança

A camada armazena apenas agregados. IDs pessoais/financeiros, correlation IDs, API keys, IPs, URLs arbitrárias, mensagens de exceção e segredos são removidos por allowlist antes de chegar ao storage.

## Integração R6.9

- `MetricsStore` usa backend `redis` por padrão, configurável por `ORIGINPAY_METRICS_BASELINE_BACKEND`;
- `memory` é fallback explícito para testes/ambientes sem persistência;
- conexão e TTL usam `ORIGINPAY_METRICS_REDIS_CONNECTION` e `ORIGINPAY_METRICS_TTL_SECONDS`;
- requests registram total, classe de status e duração por nome de rota/método;
- webhook jobs registram resultado e duração por gateway controlado;
- criação de cobrança da API registra success/failure sem IDs, payload ou mensagem de erro;
- a coleta é integralmente fail-open: falhas de configuração, binding, construção, conexão, escrita, leitura ou serialização não afetam o fluxo principal;
- `NoOpMetricsStore` é o fallback seguro para Redis indisponível e driver inválido, evitando estado falso ou crescimento de memória;
- Redis é resolvido apenas sob demanda e somente quando a classe/extensão está disponível;
- a validação com Redis real permanece ignorada quando extensão ou servidor não estão disponíveis no PHP canônico.

## Encerramento R6

- namespace configurável por `ORIGINPAY_METRICS_REDIS_NAMESPACE`, validado e isolado para chaves e índice;
- counters usam a operação atômica Redis `HINCRBYFLOAT` e renovam TTL da série e do índice;
- teste condicional com Redis real verifica persistência, TTL, atomicidade, isolamento, concorrência básica e reconnect;
- após reinício do Redis, métricas voláteis podem ser perdidas conforme a natureza do backend; novas escritas devem retomar após reconexão;
- indisponibilidade continua fail-open com `NoOpMetricsStore`.
