# Definição de Alertas Operacionais (OriginPay Enterprise)

Este documento define as regras de acionamento de alertas lógicos baseados nas métricas operacionais capturadas pela abstração `OperationalMetricsServiceInterface`.

## CRÍTICO (Atuação Imediata - P1)

*   **Webhook Failure Rate Alto (> 5%)**
    *   *Métrica:* Razão entre `webhook_failed` e `webhook_received`.
    *   *Gatilho:* Se a razão for maior que 5% em uma janela de 5 minutos.
    *   *Causa comum:* Gateway fora do ar, mudança de contrato de API, fila Redis travada.
*   **Pagamentos Falhando (> 3%)**
    *   *Métrica:* Razão entre `payment_failed` e `checkout_started`.
    *   *Gatilho:* Se a razão for maior que 3% em uma janela de 10 minutos.
    *   *Causa comum:* Saldo do adquirente esgotado, credenciais inválidas em massa.
*   **Infraestrutura Indisponível**
    *   *Métrica:* Endpoint `/health/ready` (Monitor externo pingando).
    *   *Gatilho:* Status != 200 OK.
    *   *Causa comum:* Conexão PDO (MySQL) recusada, Redis Offline, Disco local cheio.

## ALTO (Atuação Prioritária - P2)

*   **Latência Média Elevada (> 1s)**
    *   *Métrica:* Histograma de latência via `PerformanceLoggingMiddleware`.
    *   *Gatilho:* Se o p95 (percentil 95) das requisições web ou API ultrapassar 1000ms.
*   **Acúmulo de Exceptions**
    *   *Métrica:* Contagem de ocorrências em `Log::channel('security')` e `payments`.
    *   *Gatilho:* > 50 exceptions em 5 minutos.
*   **Filas Acumulando (Queue Backlog)**
    *   *Métrica:* `queue_size` da fila `high` e `default`.
    *   *Gatilho:* > 1000 jobs enfileirados sem diminuição em 5 minutos.

## MÉDIO (Análise Pós-Incidente - P3)

*   **Aumento de Bloqueios de Rate Limit (HTTP 429)**
    *   *Métrica:* Contagem de `throttle` limit hits.
    *   *Gatilho:* Aumento de 30% em relação ao baseline do dia anterior.
*   **Aumento de Requisições Lentas (Slow Requests)**
    *   *Métrica:* Volume de logs de *Slow operation detected*.
    *   *Gatilho:* > 100 alertas gerados em 1 hora.

---
**Nota Operacional:** As integrações (Slack/PagerDuty) devem ler e processar os eventos consolidados gerados por ferramentas analíticas futuras (Datadog/Prometheus) alimentadas por esses gatilhos.
