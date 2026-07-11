# Checklist Definitivo de Observabilidade

Para o Go Live, é mandatório que o Cockpit Executivo e o Ops Dashboard possuam visibilidade cristalina.

## 1. Visão Financeira (Business Metrics)
| Métrica (Dashboard) | Status de Coleta | Ref. Implementação |
| :--- | :---: | :--- |
| **TPV (Total Payment Volume)** | PASS | `FinanceDashboardController` |
| **Charges / Minuto (Velocidade)** | PASS | K6 / PromQL |
| **Saques / Minuto (Cash Out)** | PASS | K6 / PromQL |
| **Revenue (Receita Gerada/Taxas)** | PASS | Relatórios de Ledger |
| **Approval Rate (Sucesso vs Falha)** | PASS | Ops Dashboard |
| **Chargebacks** | PASS | Dispute Engine (Gateway) |

## 2. Visão de Infraestrutura (SRE)
| Recurso | Monitoramento Ativo | Status |
| :--- | :--- | :---: |
| **CPU / RAM** | Datadog Agent / AWS CloudWatch | PASS |
| **Redis** | Uso de RAM, Evictions, Connected Clients | PASS |
| **PostgreSQL** | Active Connections, Locks, Slow Queries, Deadlocks | PASS |
| **Horizon** | Horizon Dashboard (`/horizon`) protegido via RBAC | PASS |
| **Filas (Jobs)** | Tamanho da fila, Throughput, Retries, DLQ | PASS |

## 3. Visão de Segurança e Risco
| Sinal de Risco | Emissão de Alerta Ativa? | Status |
| :--- | :--- | :---: |
| **Replay Attacks** | Geração de `PlatformIncident` de Gravidade Crítica | PASS |
| **Fraud Engine** | Bloqueios automáticos via Velocity/Rate Limits | PASS |
| **Auditoria (Audit Logs)** | Rastreio total de "Quem, O Que, Quando" (Model Actions) | PASS |
| **RBAC Violations** | Log de acessos negados (403 Forbidden) no Sentry | PASS |

## Conclusão
A organização não opera "no escuro". Em caso de picos anômalos de saques, gargalos de banco ou ataques em massa, a infraestrutura gritará através de alarmes e o Dashboard Executivo provará que o dinheiro não sumiu.
