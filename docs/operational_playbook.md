# Playbook Operacional (OriginPay Enterprise)

Este manual tem como objetivo orientar o time de operações, infraestrutura e SRE no dia-a-dia do monitoramento e resposta a incidentes na plataforma, estabelecendo as bases de telemetria introduzidas nas Fases 5.2 e 5.3.

## 1. Arquitetura de Observabilidade

A fundação de observabilidade da OriginPay baseia-se num sistema 100% passivo (Zero Overhead Funcional) que não introduz bloqueios na thread principal.

### Fluxo de Logs (Logs Estruturados)
1. **Middlewares**: `CorrelationIdMiddleware` captura ou gera UUIDs da requisição.
2. **Contexto (Laravel 11 `Context`)**: Injetado nativamente para que alcance sub-rotinas e Jobs assíncronos (Queues).
3. **Canais Monolog (`config/logging.php`)**: Direcionamento segmentado de arquivos:
   - `laravel.log` (Sistema Geral / Default)
   - `payments.log` (Transações Financeiras)
   - `webhooks.log` (Callbacks de Gateways)
   - `security.log` (Autenticação/Acessos)
   - `performance.log` (Violação de latência)

### Fluxo de Métricas (NullMetricsDriver)
1. **Abstração (Fase 5.3)**: O `OperationalMetricsServiceInterface` é a única porta de entrada para emissão de contadores no código-fonte.
2. **Driver Atual (Null Driver)**: Atualmente silencioso, prevendo integração futura (Datadog/Prometheus) sem necessitar refatoração de controllers.

## 2. Health Checks e Monitoramento Ativo

Existem dois endpoints desenhados para balanceadores de carga e agentes externos:

*   **`GET /api/health/live` (Público):** Usado pelo Kubernetes/Load Balancer para atestar se o container/processo do PHP está vivo e respondendo. Retorna HTTP 200 `{"status": "UP"}`.
*   **`GET /api/health/ready` (Protegido por Token):** Usado por monitoramento profundo. Exige o Header `X-Monitor-Token`. Efetua ping no Banco de Dados (MySQL/Postgres), Cache (Redis) e disco local (Storage). Retorna HTTP 200 (se saudável) ou HTTP 503 (se indisponível).

## 3. Troubleshooting e Incidentes

### A. Lentidão Sistêmica (Degradação)
*Sintoma:* Aumento na latência geral no load balancer.
*Ação:*
1. Verificar arquivo `storage/logs/performance.log`.
2. O middleware `PerformanceLoggingMiddleware` apontará a exata Rota e o tempo em milissegundos excedido.
3. Buscar pelo `correlation_id` no Elasticsearch (se disponível) ou via grep no bash local:
   `grep "uuid-do-correlation" storage/logs/laravel.log`
4. Avaliar se o gargalo é no Banco (Query lenta) ou na integração externa (Gateway).

### B. Falha de Webhooks (Gateways)
*Sintoma:* Transações aprovadas no gateway mas pendentes no sistema.
*Ação:*
1. Analisar `storage/logs/webhooks.log`. Procurar por `webhook_recebido_enfileirado` vs `Webhook ingestion failed`.
2. Se estiver chegando e falhando, verificar os detalhes da *Exception* registrados na mesma linha do log (Classe, Linha, Arquivo).
3. Se estiver em *Dead Letter Queue*, o painel de reprocessamento do Admin emitirá logs de auditoria se falhar na retentativa (`Webhook reprocess failed`).

## 4. Checklist de Produção (Deploy / Manutenção)

Antes de promover novas builds, garanta:
- [ ] `$ artisan config:cache` e `route:cache` (Logs categorizados precisam do cache em dia).
- [ ] O Header `X-Monitor-Token` está devidamente configurado nas variáveis de ambiente do Load Balancer ou uptime monitor externo (Datadog Synthetics / Pingdom).
- [ ] Permissões de pasta em `storage/logs/` garantem escrita pelo usuário web (`www-data`).
- [ ] Rotacionamento de logs (logrotate) em nível do S.O configurado, além do controle nativo do Monolog (14 days daily logs limit configurado no `logging.php`).
