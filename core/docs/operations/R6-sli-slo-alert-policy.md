# R6.6 — SLIs, SLOs, error budgets e política operacional de alertas

## Escopo

Este documento define a política operacional canônica inicial de observabilidade da OriginPay para R6.6.

Não há integração externa neste incremento. O catálogo é declarativo e local:

- sem Prometheus;
- sem Grafana;
- sem Loki;
- sem OpenTelemetry;
- sem PagerDuty;
- sem Slack;
- sem e-mail;
- sem SMS;
- sem dashboards externos;
- sem tracing distribuído;
- sem backup/restore operacional;
- sem DR.

Os thresholds em `config/observability.php` são sinais operacionais provisórios do deep health. Eles não são SLO final, SLA, nem compromisso externo.

## Convenções

- **SLI**: indicador de nível de serviço medido ou planejado.
- **SLO definitivo**: compromisso interno aceito somente quando há fonte canônica e histórico suficiente.
- **SLO provisório**: faixa sugerida para operação enquanto se coleta baseline. Não é compromisso final.
- **Error budget**: margem de erro permitida na janela do SLO.
- **Burn rate**: velocidade de consumo do error budget.
- **Exclusões**: eventos removidos do cálculo para evitar distorção.
- **Cardinalidade**: risco de gerar série/agrupamento excessivo quando houver futura integração de métricas.

## Catálogo de SLIs

### 1. Disponibilidade da API

- **Definição**: proporção de requisições API que não retornam erro 5xx.
- **Numerador**: respostas HTTP API com status menor que 500.
- **Denominador**: total de respostas HTTP API.
- **Janela**: provisória 5m, 30m e 30d para tendência.
- **Fonte do sinal**: logs HTTP estruturados, readiness/deep health e status code da aplicação.
- **Exclusões**: 4xx causados por cliente, throttling esperado, rotas administrativas fora do escopo público, tráfego sintético malformado.
- **Risco de cardinalidade**: alto se usar path bruto, tenant, usuário, API key ou correlation id como dimensão.
- **Limitações atuais**: não há agregador externo; cálculo ainda depende de logs/apuração local.
- **SLO**: provisório: faixa candidata 99.5%–99.9% mensal; decisão pendente.

### 2. Criação de cobranças

- **Definição**: proporção de tentativas válidas de criação de cobrança concluídas sem erro interno e sem órfão financeiro.
- **Numerador**: cobranças válidas criadas com persistência consistente ou falha controlada sem órfão.
- **Denominador**: tentativas válidas de criação de cobrança recebidas pela API.
- **Janela**: provisória 15m, 1h e 30d.
- **Fonte do sinal**: logs de payments, eventos de cobrança, testes de invariantes e erros controlados.
- **Exclusões**: payload inválido, autenticação/scope inválido, idempotência repetida legítima, rejeição esperada por regra de negócio.
- **Risco de cardinalidade**: alto se agrupar por charge id, customer id, tenant ou idempotency key.
- **Limitações atuais**: não há métrica agregada canônica por minuto; depende de logs e banco.
- **SLO**: provisório: faixa candidata 99.0%–99.5% para tentativas válidas; decisão pendente.

### 3. Processamento de webhooks

- **Definição**: proporção de webhooks aceitos que são processados idempotentemente sem DLQ pendente.
- **Numerador**: webhooks aceitos processados, duplicados tratados como idempotentes ou rejeitados de forma controlada por assinatura inválida.
- **Denominador**: webhooks recebidos e aceitos para processamento.
- **Janela**: provisória 15m, 1h e 30d.
- **Fonte do sinal**: `webhook_events`, `webhook_dead_letters`, logs de webhooks e jobs críticos.
- **Exclusões**: assinatura inválida, replay fora da janela, provider inválido, payload malformado rejeitado antes de aceitação.
- **Risco de cardinalidade**: alto para provider event id, tenant, assinatura, payload hash e correlation id.
- **Limitações atuais**: deep health cobre DLQ pendente, mas não calcula taxa histórica completa.
- **SLO**: provisório: faixa candidata 99.0%–99.9%; decisão pendente.

### 4. Confirmação de pagamentos

- **Definição**: proporção de confirmações de pagamento válidas refletidas corretamente no estado financeiro interno.
- **Numerador**: confirmações válidas aplicadas idempotentemente com ledger/balance consistente.
- **Denominador**: confirmações válidas recebidas por webhook ou consulta interna.
- **Janela**: provisória 1h e 30d.
- **Fonte do sinal**: eventos de pagamento, ledger, wallet balance, logs de gateway/webhook.
- **Exclusões**: eventos inválidos, charge inexistente rejeitada, confirmação duplicada idempotente, indisponibilidade externa já classificada como gateway.
- **Risco de cardinalidade**: alto se usar payment id, transaction id, tenant ou gateway reference.
- **Limitações atuais**: não há SLI agregado dedicado.
- **SLO**: provisório: faixa candidata 99.5%–99.9%; decisão pendente.

### 5. Queue backlog

- **Definição**: volume de jobs pendentes no backend configurado.
- **Numerador**: backlog atual lido em modo read-only.
- **Denominador**: não aplicável para gauge; usar tendência e limite operacional.
- **Janela**: current, 10m e 15m tendência.
- **Fonte do sinal**: `GET /api/health/deep`, backend de fila configurado.
- **Exclusões**: filas sem backlog persistente (`sync`/`null`) e ambientes de teste sem workers.
- **Risco de cardinalidade**: médio se separar por fila; alto se separar por job class/tenant.
- **Limitações atuais**: thresholds são provisórios em `config/observability.php`.
- **SLO**: sem SLO definitivo; usar apenas sinal operacional provisório.

### 6. Failed jobs

- **Definição**: contagem de jobs em `failed_jobs`.
- **Numerador**: total atual de jobs falhos.
- **Denominador**: não aplicável para gauge; pode evoluir para taxa por total de jobs processados.
- **Janela**: current e 15m tendência.
- **Fonte do sinal**: `GET /api/health/deep`, tabela configurada de failed jobs.
- **Exclusões**: falhas antigas já triadas quando houver workflow formal de resolução.
- **Risco de cardinalidade**: alto se expor payload, exception, tenant ou job id.
- **Limitações atuais**: deep health não retorna payload/exception e não diferencia classes de falha.
- **SLO**: sem SLO definitivo; usar threshold provisório.

### 7. DLQ

- **Definição**: pendências em `webhook_dead_letters` e idade do item pendente mais antigo.
- **Numerador**: count pendente e idade do oldest pending item.
- **Denominador**: não aplicável para gauge; pode evoluir para taxa por webhooks aceitos.
- **Janela**: current e 15m tendência.
- **Fonte do sinal**: `GET /api/health/deep`, `webhook_dead_letters`.
- **Exclusões**: itens resolvidos/reprocessados; payload e tenant nunca entram no sinal.
- **Risco de cardinalidade**: alto se usar gateway_code, tenant, headers, assinatura ou payload.
- **Limitações atuais**: thresholds são provisórios; sem workflow formal de aging por severidade.
- **SLO**: sem SLO definitivo; usar threshold provisório.

### 8. Scheduler freshness

- **Definição**: idade do último heartbeat canônico do scheduler Laravel.
- **Numerador**: segundos desde o último heartbeat.
- **Denominador**: não aplicável para gauge.
- **Janela**: current e 10m tendência.
- **Fonte do sinal**: cache configurável via `SchedulerHeartbeat`, exposto sanitizado no deep health.
- **Exclusões**: ambientes sem scheduler ativo explicitamente documentados fora de produção.
- **Risco de cardinalidade**: baixo; não expor chave de cache ou host.
- **Limitações atuais**: depende do cache configurado e do scheduler estar rodando.
- **SLO**: provisório operacional apenas; decisão pendente.

### 9. Settlement

- **Definição**: consistência entre settlement, ledger, wallet balance e reservas.
- **Numerador**: reconciliações sem divergência crítica.
- **Denominador**: reconciliações executadas.
- **Janela**: por execução, diário e mensal.
- **Fonte do sinal**: rotinas de reconciliação, anomalias financeiras e testes de integridade.
- **Exclusões**: divergências já aprovadas formalmente e documentadas por operação financeira autorizada.
- **Risco de cardinalidade**: alto se usar merchant, bank account, transaction id ou settlement id.
- **Limitações atuais**: política definida; agregação operacional ainda pendente.
- **SLO**: SLO interno candidato: 100% de reconciliações sem divergência crítica não aprovada; decisão pendente antes de virar compromisso formal.

### 10. Integridade de dados

- **Definição**: proporção de verificações de invariantes de dados concluídas sem violação crítica.
- **Numerador**: verificações sem violação de ledger, saldo, idempotência, tenant isolation ou imutabilidade.
- **Denominador**: total de verificações executadas.
- **Janela**: por execução e diário.
- **Fonte do sinal**: testes, comandos de verificação, anomalias e logs de auditoria.
- **Exclusões**: dados seed/teste fora de produção e divergências aprovadas por procedimento formal.
- **Risco de cardinalidade**: alto se usar IDs financeiros, tenant, usuário ou detalhes de payload.
- **Limitações atuais**: há testes e algumas rotinas, mas não há catálogo unificado de verificações em produção.
- **SLO**: SLO interno candidato: 100% para invariantes financeiras críticas; decisão pendente.

## Error budgets

Para SLOs definitivos futuros:

```text
error_budget = 1 - SLO
allowed_bad_events = total_eligible_events * error_budget
consumed_budget = bad_events / allowed_bad_events
burn_rate = observed_error_rate / error_budget
```

Para gauges sem denominador natural, usar orçamento operacional por tempo fora da faixa:

```text
allowed_bad_minutes = window_minutes * (1 - target_availability)
consumed_budget = minutes_in_WARN_or_ERROR / allowed_bad_minutes
burn_rate = observed_bad_minutes_rate / allowed_bad_minutes_rate
```

### Aplicação atual

- API availability: orçamento aplicável quando houver agregação canônica de status HTTP.
- Charge creation: orçamento aplicável após definição de tentativa válida e agregador canônico.
- Webhooks: orçamento aplicável após taxa histórica por webhooks aceitos.
- Payment confirmation: orçamento aplicável após sinal agregado dedicado.
- Settlement/data integrity: orçamento tende a ser zero para violação crítica; ações são imediatas, não consumo gradual.
- Queue/failed jobs/DLQ/scheduler: usar como sinais operacionais provisórios, não orçamento final.

### Ações por consumo

- **< 50%**: acompanhar tendência.
- **50%–80%**: priorizar correção na próxima janela de manutenção.
- **80%–100%**: congelar mudanças não essenciais relacionadas ao serviço afetado.
- **> 100%**: abrir incidente interno, executar runbook e exigir decisão operacional antes de novas mudanças de risco.

## Política de alertas

O catálogo executável está em `config/alerts.php`. Cada alerta contém:

- nome;
- SLI;
- sinal;
- condição;
- severidade;
- janela;
- responsável;
- primeira ação;
- runbook;
- condição de encerramento;
- integração externa desabilitada.

### Severidades

- **SEV1**: risco financeiro, indisponibilidade crítica ou integridade comprometida.
- **SEV2**: degradação relevante de pagamentos, webhooks, gateway ou dependência essencial.
- **SEV3**: degradação operacional sem evidência imediata de perda financeira.
- **SEV4**: aviso informativo ou política futura.

### Alertas catalogados

1. `api_5xx_increase`
2. `api_latency_degraded`
3. `charge_creation_failure`
4. `webhook_processing_failure`
5. `queue_backlog_high`
6. `failed_jobs_high`
7. `dlq_growth`
8. `scheduler_delayed`
9. `gateway_degraded`
10. `settlement_inconsistent`
11. `database_unavailable`
12. `redis_unavailable`
13. `api_key_abuse`
14. `backup_restore_failure_future`

## Decisões pendentes

- Definir SLOs finais com base em histórico real.
- Definir janelas finais para disponibilidade e latência.
- Definir quais sinais entram em budget por evento e quais por minuto degradado.
- Definir owners nominais ou escala formal.
- Implementar agregação externa somente em fase futura.
- Formalizar política de backup/restore e DR fora de R6.6.
