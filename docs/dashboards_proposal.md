# Proposta de Dashboards de Observabilidade (Fase 5.3)

Este documento especifica as propostas lógicas para a criação de Dashboards na ferramenta de visualização (ex: Grafana/Datadog) para monitoramento em tempo real da OriginPay Enterprise.

## 1. Dashboard Operacional (NOC / SRE)
Focado na saúde bruta da infraestrutura e performance sistêmica.

**Painéis Sugeridos:**
*   **RPS (Requests per Second):**
    *   Métrica: Contagem total de requests HTTP agregada por minuto/segundo.
    *   Tipo: *Time Series Line Chart*.
*   **Taxa de Erros HTTP (5xx / 4xx):**
    *   Métrica: Requests onde HTTP Status >= 400.
    *   Tipo: *Time Series Stacked Area*.
*   **Latência P95 e Média:**
    *   Métrica: Histograma das requests da aplicação (SLA base: 500ms).
    *   Tipo: *Time Series Line Chart*.
*   **Uptime e Health:**
    *   Métrica: Status de `/health/ready` e `/health/live`.
    *   Tipo: *Stat / Gauge (Verde/Vermelho)*.

## 2. Dashboard Financeiro (Negócios)
Focado no volume de transacionamento e conversão de checkouts.

**Painéis Sugeridos:**
*   **Funil de Checkout:**
    *   Métrica: Total de `checkout_started` -> `checkout_completed`.
    *   Tipo: *Funnel Chart*.
*   **Pagamentos por Minuto:**
    *   Métrica: Taxa de `payment_created` e `payment_approved`.
    *   Tipo: *Bar Chart*.
*   **Taxa de Aprovação (Approval Rate):**
    *   Métrica: (`payment_approved` / `checkout_completed`) * 100.
    *   Tipo: *Stat (Percentage)*.
*   **Webhooks Recebidos (Ingestão):**
    *   Métrica: Contagem de `webhook_received`.
    *   Tipo: *Time Series*.

## 3. Dashboard Gateways (Integração)
Focado em identificar gargalos externos, provedor por provedor.

**Painéis Sugeridos:**
*   **Share de Utilização por Provider:**
    *   Métrica: Transações agrupadas por tag `provider` (ex: *efi*, *stripe*).
    *   Tipo: *Pie / Donut Chart*.
*   **Erros do Gateway:**
    *   Métrica: Contagem de `provider_not_found`, `invalid_credential`, etc.
    *   Tipo: *Table / Bar Chart*.
*   **Disponibilidade do Provider (Webhooks):**
    *   Métrica: Taxa de Webhooks com falhas vs Sucesso por Provider.
    *   Tipo: *Gauge / Status History*.
