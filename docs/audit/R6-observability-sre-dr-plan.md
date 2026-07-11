# R6 — Plano técnico de Observabilidade, SRE e DR

## 1. Resumo executivo

A fase anterior, **R5.1 — Hardening Administrativo Final**, está concluída e validada documentalmente em:

- `../docs/audit/R5.1-admin-hardening.md`
- `../docs/audit/R5.1-checkpoint-and-next-phase.md`

A próxima fase canônica é:

- **Fase R6 — Observabilidade, SRE e DR**

Fundamento principal:

- `../docs/audit/05-product-roadmap.md`

Definição canônica da R6:

```text
## Fase R6 — Observabilidade, SRE e DR
Prioridade: Alta.
Objetivo: produção operável.
Arquivos: health controllers, metrics services, Horizon config, logging config, commands.
Testes: Redis down, DB down, queue backlog, DLQ overflow, PSP timeout, incident drill.
Aceite: dashboards/alertas definidos, runbooks, RTO/RPO, backup/restore testados.
```

Esta execução **não implementou a R6**, não executou migrations, não fez commit, não criou tag e não iniciou go-live/Release Candidate/deploy. O objetivo foi localizar a raiz Git, preparar checkpoint e planejar tecnicamente a R6.

## 2. Raiz Git e estado do repositório

### 2.1 Diretório atual

```text
/e/projetos/DigiKash v1.0.5/DigiKash v1.0.5/core
```

### 2.2 Inspeção segura de Git

Comandos executados de forma read-only:

```bash
pwd
test -e .git
test -e ../.git
test -e ../../.git
git rev-parse --show-toplevel
git -C .. rev-parse --show-toplevel
git -C ../.. rev-parse --show-toplevel
find .. -maxdepth 4 -name .git -print
```

Resultado:

- `core/.git`: não existe.
- `../.git`: existe como diretório, mas contém apenas `info/exclude` e não possui estrutura válida de repositório Git.
- `../../.git`: não existe.
- `git rev-parse --show-toplevel`: falhou em `core`, no pai e no ancestral.
- `find .. -maxdepth 4 -name .git -print`: encontrou apenas `../.git`.

Saída relevante:

```text
fatal: not a git repository (or any of the parent directories): .git
```

Conclusão:

- **Nenhuma raiz Git válida foi encontrada dentro da árvore esperada do projeto.**
- O diretório provável do projeto é:

```text
/e/projetos/DigiKash v1.0.5/DigiKash v1.0.5
```

- Não foi criado repositório novo.
- Não foi simulado checkpoint.
- Não é seguro executar commit/tag até localizar ou restaurar a raiz Git correta.

### 2.3 Branch atual

Não disponível.

Motivo:

```text
fatal: not a git repository (or any of the parent directories): .git
```

### 2.4 Estado do working tree

Não disponível via Git.

Motivo:

- A árvore possui um diretório `.git` incompleto/inválido no provável diretório raiz do projeto.
- `git status`, `git diff`, `git diff --cached`, `git log`, `git branch --show-current` e `git ls-files` não podem ser usados de forma confiável.

## 3. Checkpoint R5.1 — auditoria documental

### 3.1 Relatórios lidos integralmente

- `../docs/audit/R5.1-admin-hardening.md`
- `../docs/audit/R5.1-checkpoint-and-next-phase.md`

### 3.2 Evidências de R5.1 confirmadas nos relatórios

O relatório R5.1 contém evidências suficientes sobre:

| Item exigido | Evidência documental | Status |
| --- | --- | --- |
| RBAC granular | Permissões específicas em rotas admin e seeder RBAC | Suficiente |
| Proteção da rota DLQ | `admin.permission:webhooks.dlq.reprocess` + testes merchant/user/admin | Suficiente |
| Scopes de API Keys | Middleware `api.scope` e scopes em rotas públicas v1 principais | Suficiente |
| Auditoria administrativa | `reason`, usuário, timestamp, recurso, alteração, IP, correlation id | Suficiente |
| Isolamento entre tenants | Regressão existente citada para Developer Portal, Merchant, Wallet, Settlement, API Keys e Webhooks | Suficiente para checkpoint |
| Pest | `413 passed (1426 assertions)` | Suficiente |
| Testes direcionados | `16 passed (43 assertions)` | Suficiente |
| Pint | `PASS 10 files` nos arquivos alterados | Suficiente |
| PHPStan | Não configurado | Documentado |
| Cobertura | Sem número, evidência funcional por testes | Documentado |
| Riscos restantes | Cinco riscos principais documentados | Suficiente |
| Bloqueadores | Não há bloqueadores conhecidos no escopo R5.1 | Suficiente |

### 3.3 Inconsistência objetiva encontrada

A única inconsistência operacional é o estado Git:

- O relatório anterior já indicava que `core` não era repositório Git.
- Nesta rodada foi confirmado que o pai contém `.git` incompleto/inválido.
- Portanto, checkpoint Git formal ainda depende de localizar/restaurar uma raiz Git válida.

Não foi encontrada inconsistência objetiva entre os relatórios R5.1 e a implementação aprovada, dentro dos limites de inspeção sem Git.

## 4. Classificação de arquivos pendentes para checkpoint R5.1

Como Git não está disponível, a classificação abaixo é baseada nos relatórios R5.1, na estrutura inspecionada e na análise de riscos de segredos/artefatos.

### 4.1 Pertencem ao R5.1

Implementação R5.1:

- `core/app/Http/Controllers/Webhook/GatewayWebhookController.php`
- `core/app/Http/Middleware/EnsureAdminPermission.php`
- `core/app/Http/Middleware/EnsureApiKeyScope.php`
- `core/bootstrap/app.php`
- `core/config/logging.php`
- `core/database/seeders/EnterpriseRbacSeeder.php`
- `core/routes/admin.php`
- `core/routes/api.php`
- `core/tests/Feature/GatewayWebhookValidationTest.php`
- `core/tests/Feature/R51AdminHardeningTest.php`
- `core/tests/Feature/WebhookAdminPanelTest.php`

Documentação R5.1:

- `docs/audit/R5.1-admin-hardening.md`
- `docs/audit/R5.1-checkpoint-and-next-phase.md`

Planejamento R6 gerado nesta execução:

- `docs/audit/R6-observability-sre-dr-plan.md`

### 4.2 Não pertencem ao R5.1 / não devem entrar no checkpoint R5.1 sem revisão separada

Arquivos observados no diretório provável do projeto que não pertencem diretamente ao checkpoint R5.1:

- `2026_07_03_112604_create_connect_domains_table.php` e demais migrations soltas no diretório raiz.
- `audit_matches.json`
- `digisynk_matches.json`
- `final_audit.json`
- `final_audit.txt`
- `fix_balances.js`
- `fix_balances.php`
- `fix_tabs.ps1`
- `fix_tabs2.ps1`
- `fix_webhooks.js`
- `rebrand.php`
- `rebrand.ps1`
- `rebrand_js.ps1`
- `refactor.py`
- `refactor_connect.php`
- `scratch_fix_routes.php`
- `temp_css.txt`
- `node_modules/`
- `package.json` e `package-lock.json` do diretório raiz, salvo se o projeto explicitamente os versiona.
- `backups/`
- `storage/`
- `.agents/`, `.claude/`, `.codex/`, `.codex-remote-attachments/`, `.impeccable/`, salvo política explícita de versionamento.

### 4.3 Sensíveis ou potencialmente sensíveis

Não incluir sem revisão manual e mascaramento:

- `.env`
- Qualquer arquivo com credenciais, chaves, tokens ou secrets.
- Dumps de banco.
- Backups.
- Logs de produção/teste com payloads financeiros ou dados pessoais.
- Arquivos em `core/storage/logs/*`.
- Arquivos em `core/storage/framework/*`.

### 4.4 Gerados automaticamente / temporários

Não incluir no checkpoint R5.1:

- caches de Laravel;
- logs;
- artefatos de testes;
- dumps;
- diretórios de vendor/dependências;
- arquivos temporários de scripts de correção;
- relatórios JSON intermediários fora de `docs/audit`.

## 5. Checkpoint R5.1 proposto — comandos não executados

### 5.1 Pré-condição obrigatória

Antes de qualquer comando de checkpoint, localizar/restaurar uma raiz Git válida.

Comandos seguros para confirmar:

```bash
cd "E:/projetos/DigiKash v1.0.5/DigiKash v1.0.5"
git rev-parse --show-toplevel
git status --short
git branch --show-current
git log --oneline -10
```

Se esses comandos ainda falharem:

- não executar commit;
- não criar tag;
- não executar `git init` sem decisão explícita do responsável;
- investigar se o repositório foi copiado sem metadados Git ou se `.git` foi corrompido.

### 5.2 Comandos de revisão antes do stage

Executar somente na raiz Git válida:

```bash
git status --short
git diff --stat
git diff --name-status
git diff --cached --stat
git diff --cached --name-status
git ls-files --others --exclude-standard
```

Revisão de arquivos R5.1:

```bash
git diff -- core/app/Http/Controllers/Webhook/GatewayWebhookController.php
git diff -- core/app/Http/Middleware/EnsureAdminPermission.php
git diff -- core/app/Http/Middleware/EnsureApiKeyScope.php
git diff -- core/bootstrap/app.php
git diff -- core/config/logging.php
git diff -- core/database/seeders/EnterpriseRbacSeeder.php
git diff -- core/routes/admin.php
git diff -- core/routes/api.php
git diff -- core/tests/Feature/GatewayWebhookValidationTest.php
git diff -- core/tests/Feature/R51AdminHardeningTest.php
git diff -- core/tests/Feature/WebhookAdminPanelTest.php
git diff -- docs/audit/R5.1-admin-hardening.md
git diff -- docs/audit/R5.1-checkpoint-and-next-phase.md
git diff -- docs/audit/R6-observability-sre-dr-plan.md
```

### 5.3 Stage explícito — sem `git add .` e sem `git add -A`

```bash
git add core/app/Http/Controllers/Webhook/GatewayWebhookController.php \
  core/app/Http/Middleware/EnsureAdminPermission.php \
  core/app/Http/Middleware/EnsureApiKeyScope.php \
  core/bootstrap/app.php \
  core/config/logging.php \
  core/database/seeders/EnterpriseRbacSeeder.php \
  core/routes/admin.php \
  core/routes/api.php \
  core/tests/Feature/GatewayWebhookValidationTest.php \
  core/tests/Feature/R51AdminHardeningTest.php \
  core/tests/Feature/WebhookAdminPanelTest.php \
  docs/audit/R5.1-admin-hardening.md \
  docs/audit/R5.1-checkpoint-and-next-phase.md \
  docs/audit/R6-observability-sre-dr-plan.md
```

### 5.4 Revisão após stage

```bash
git status --short
git diff --cached --stat
git diff --cached --name-status
git diff --cached
```

### 5.5 Validações antes do commit

Executar a partir de `core` ou ajustando path conforme a raiz Git:

```bash
cd core
"E:/laragon/bin/php/php-8.3.30-Win32-vs16-x64/php.exe" artisan test
"E:/laragon/bin/php/php-8.3.30-Win32-vs16-x64/php.exe" vendor/bin/pint --test app/Http/Middleware/EnsureAdminPermission.php app/Http/Middleware/EnsureApiKeyScope.php app/Http/Controllers/Webhook/GatewayWebhookController.php routes/api.php routes/admin.php database/seeders/EnterpriseRbacSeeder.php tests/Feature/R51AdminHardeningTest.php tests/Feature/WebhookAdminPanelTest.php tests/Feature/GatewayWebhookValidationTest.php config/logging.php
```

PHPStan:

```bash
# Executar somente se phpstan.neon/phpstan.neon.dist existir.
"E:/laragon/bin/php/php-8.3.30-Win32-vs16-x64/php.exe" vendor/bin/phpstan analyse
```

### 5.6 Commit proposto — não executado

```bash
git commit -m "security: close R5.1 admin hardening checkpoint" \
  -m "- protect DLQ reprocess with granular admin permission
- add API key scope middleware and route scopes
- add granular RBAC for sensitive admin financial actions
- enforce reasoned audit logging for DLQ reprocess
- document R5.1 checkpoint and next canonical phase

Validation:
- Pest: 413 passed (1426 assertions)
- Targeted tests: 16 passed (43 assertions)
- Pint changed files: PASS
- PHPStan: not configured"
```

### 5.7 Tag proposta — não executada

Criar somente após commit aprovado:

```bash
git tag -a r5.1-admin-hardening-final -m "R5.1 admin hardening final checkpoint"
```

### 5.8 Verificação do commit e tag

```bash
git status --short
git log --oneline -3
git tag --list "r5.1-admin-hardening-final"
git show --stat --oneline HEAD
```

## 6. Fundamento canônico da R6

Documentos lidos e usados como base:

- `../docs/audit/05-product-roadmap.md`
- `../docs/production/03-testing-strategy.md`
- `../docs/production/04-security-testing.md`
- `../docs/production/05-high-availability.md`
- `../docs/production/06-operational-runbook.md`
- `../docs/production/07-release-checklist.md`
- `../docs/production/08-coding-standards.md`
- `../docs/deploy/deploy_validation.md`
- `../docs/disaster_recovery/backup_validation.md`
- `../docs/observability/observability_validation.md`
- `../docs/go-live/disaster_recovery.md`
- `../docs/runbooks/restore_database.md`
- `../docs/runbooks/restore_full.md`
- `../docs/runbooks/restore_redis.md`
- `../docs/runbooks/01-gateway-offline.md`
- `../docs/runbooks/02-redis-indisponivel.md`
- `../docs/runbooks/03-horizon-parado.md`
- `../docs/runbooks/04-banco-lento.md`
- `../docs/runbooks/05-dlq-crescente.md`
- `../docs/runbooks/06-backup-restore.md`
- `../docs/runbooks/07-circuit-breaker.md`
- `../docs/runbooks/08-financial-maintenance.md`

Documentos com conflito/risco de interpretação:

- `../docs/go-live/go_live_certificate.md`
- `../docs/go-live/relatorio_final_v2.md`

Decisão:

- Eles não devem ser usados como autorização atual de go-live porque conflitam com o roadmap atual e com o checklist de produção pendente.

## 7. Inventário atual de observabilidade e SRE

### 7.1 Health endpoints

Encontrado:

- `api/health/live` → `HealthCheckController@live`
- `api/health/ready` → `HealthCheckController@ready`
- `admin/system/health` → `SystemHealthController@index`
- `admin/system/health-check` → `SystemHealthController@healthCheck`

Comando read-only executado:

```bash
"E:/laragon/bin/php/php-8.3.30-Win32-vs16-x64/php.exe" artisan route:list --path=health
```

Resultado resumido:

```text
GET|HEAD api/health/live
GET|HEAD api/health/ready
GET|HEAD admin/system/health
POST admin/system/health-check
```

Lacunas:

- Readiness usa fallback `default-secret-token`, que deve ser tratado como decisão de segurança/configuração na R6.
- Readiness escreve/deleta arquivo local para checar storage; deve ser avaliado para evitar carga/efeito colateral excessivo.
- Não há evidência de deep health para PSP/gateway, scheduler freshness, DLQ age e fila backlog em endpoint unificado.

### 7.2 Correlation ID

Encontrado:

- `app/Http/Middleware/CorrelationIdMiddleware.php`
- Usa header `X-Correlation-ID` ou gera UUID.
- Adiciona contexto global com `correlation_id`, `request_id`, `route`, `method`, `ip`, `authenticated_user_id`, `merchant_id`.
- Retorna `X-Correlation-ID` na resposta.

Lacunas:

- Verificar propagação para jobs, webhooks, gateway calls e logs de erro.
- Verificar se `merchant_id` está correto para todos os guards/contextos, pois o middleware usa propriedade `merchant_id` do usuário quando existe.
- Definir obrigatoriedade de `request_id`/`correlation_id` em todos os logs financeiros.

### 7.3 Logs estruturados

Encontrado:

- `config/logging.php` com canais como `webhooks`, `gateway`, `audit`, `security`, `performance`, `payments`.
- `AdminAuditMiddleware`.
- `LogApiRequests`.
- Logs em controllers/jobs de webhook/gateway.

Lacunas:

- Padronização de formato estruturado ainda precisa ser definida formalmente.
- Redaction centralizada ainda é parcial.
- Retenção por domínio precisa ser confirmada contra LGPD e requisitos operacionais.
- Audit trail crítico ainda está em arquivo/log, não em tabela append-only imutável.

### 7.4 Métricas

Encontrado:

- `App\Services\GatewayMetricsService`.
- Métricas em cache por janelas `5m`, `15m`, `24h`.
- Contadores e latências simples.
- Alert incident por `PlatformAlertService`.

Lacunas:

- Sem evidência de export Prometheus/OpenTelemetry/StatsD canônico.
- Métricas em cache não substituem backend de observabilidade em produção.
- Não há matriz completa de métricas obrigatórias por domínio.
- Evitar alta cardinalidade ainda precisa ser formalizado.

### 7.5 Tracing

Encontrado:

- Correlation ID existe.

Lacunas:

- Não há evidência de tracing distribuído real com spans.
- Não há mapa ponta a ponta documentado para charge → gateway → webhook → worker → settlement.

### 7.6 Filas e workers

Encontrado:

- Horizon instalado/configurado.
- `config/horizon.php` com filas `critical`, `webhooks_ingestion`, `payments`, `exports`, `emails`, `notifications`.
- `ProcessGatewayWebhookJob` usa `webhooks_ingestion`.

Lacunas:

- Documentação exige filas separadas: `webhooks_ingestion`, `payments`, `settlements`, `withdrawals`, `reconciliation`, `notifications`, `connect`.
- Horizon atual não lista explicitamente `settlements`, `withdrawals`, `reconciliation`, `connect`.
- R6 deve auditar timeout/tries/backoff em jobs financeiros.

### 7.7 Scheduler

Comando read-only executado:

```bash
"E:/laragon/bin/php/php-8.3.30-Win32-vs16-x64/php.exe" artisan schedule:list
```

Resultado:

```text
php artisan inspire
php artisan subscriptions:renew
Closure at bootstrap/app.php:124
```

Lacunas:

- Não aparecem rotinas críticas de reconciliação financeira.
- Não aparecem rotinas de DLQ, rolling reserve, ledger audit, wallet reserve reconciliation, backup verification ou health freshness.
- Esta é uma lacuna importante para R6.

### 7.8 Failed jobs

Comando read-only executado:

```bash
"E:/laragon/bin/php/php-8.3.30-Win32-vs16-x64/php.exe" artisan queue:failed
```

Resultado:

```text
No failed jobs found.
```

Lacunas:

- Resultado local não prova monitoramento contínuo, alerta ou retenção operacional.

### 7.9 Webhooks, DLQ e gateways

Encontrado:

- `GatewayWebhookController`
- `ProcessGatewayWebhookJob`
- `WebhookDeadLetter`
- rotas de reprocess admin protegidas após R5.1
- métricas de webhook no `GatewayMetricsService`

Lacunas:

- Definir alertas de DLQ count/age por provider.
- Validar replay com SLA operacional.
- Validar PSP timeout e circuit breaker por provider/operação.

### 7.10 Banco de dados e Redis

Encontrado:

- Readiness checa DB e Redis.
- Horizon usa Redis.
- Docs exigem Redis HA em produção.

Lacunas:

- Não há validação operacional de Redis down/DB down nesta execução.
- Não há evidência de HA real, RPO/RTO real ou failover testado no ambiente atual.

### 7.11 Backups e restore

Documentação existente:

- `../docs/disaster_recovery/backup_validation.md`
- `../docs/runbooks/restore_database.md`
- `../docs/runbooks/restore_full.md`
- `../docs/runbooks/restore_redis.md`

Conflito/lacuna:

- Alguns docs de DR indicam PASS e RTO/RPO, mas precisam ser reconciliados com o roadmap atual e evidências reais do ambiente da OriginPay.
- R6 deve validar se essas evidências são atuais, reproduzíveis e ligadas ao build/commit correto.

## 8. Plano de logging da R6

### 8.1 Formato estruturado obrigatório

Todo log crítico deve ser JSON/estruturado com campos padronizados.

Campos base:

- `timestamp`
- `level`
- `event`
- `message`
- `environment`
- `service`
- `route`
- `method`
- `status_code`
- `duration_ms`
- `correlation_id`
- `request_id`
- `tenant_id`/`merchant_id`
- `user_id`
- `admin_id`
- `api_key_id` ou prefixo mascarado
- `job_id`
- `queue`
- `attempt`
- `gateway`
- `payment_id`/`charge_id`
- `transaction_id`
- `settlement_id`
- `webhook_event_id`
- `idempotency_key_hash`
- `result`
- `error_code`
- `error_class`
- `error_normalized`

### 8.2 Redaction obrigatória

Nunca logar em claro:

- API keys/secrets/tokens.
- Authorization headers.
- Senhas e transaction password.
- Assinaturas de webhook completas.
- Certificados e secrets PSP.
- CPF/CNPJ completos.
- Conta bancária completa.
- Payloads sensíveis brutos sem criptografia/controle.
- PAN/CVV.

### 8.3 Retenção

Proposta para discussão:

- `audit`: 90+ dias ou conforme compliance.
- `security`: 90+ dias.
- `payments`, `gateway`, `webhooks`: janela suficiente para chargeback/reconciliação, decisão pendente.
- Logs com PII: retenção mínima necessária e mascaramento forte.

A documentação canônica exige retenção LGPD e necessidade operacional, mas não fixa números finais para todos os domínios. Decisão pendente.

## 9. Plano de métricas da R6

### 9.1 Métricas de API

- total de requisições por rota normalizada;
- taxa de 2xx/4xx/5xx;
- latência p50/p95/p99;
- rate limit hits;
- auth failures;
- RBAC failures;
- API key invalid/revoked/expired/scope denied;
- idempotency conflicts.

Evitar labels de alta cardinalidade:

- não usar `charge_id`, `user_id`, `api_key` ou `webhook_event_id` como label.
- usar rota normalizada, método, ambiente, gateway, status class.

### 9.2 Métricas de pagamentos e métodos

- charge creation success/failure;
- tempo até confirmação;
- Pix success/failure/timeout;
- boleto created/paid/expired;
- card/crypto se aplicável;
- gateway selected/fallback;
- PSP timeout/error rate por provider/operação;
- refund/payout/settlement success/failure.

### 9.3 Métricas de webhooks

Obrigatórias conforme docs:

- received;
- validated;
- rejected;
- duplicate/replay blocked;
- processed;
- failed;
- DLQ count;
- DLQ age;
- processing latency;
- queue latency.

### 9.4 Métricas de filas/workers

- backlog por fila;
- oldest job age;
- throughput;
- failed jobs;
- retries;
- worker count;
- worker memory;
- job duration;
- job timeout;
- DLQ promotion count.

### 9.5 Métricas financeiras

- ledger mismatch count;
- wallet negative attempt count;
- settlement aging;
- withdrawal pending/failed;
- reconciliation divergence;
- rolling reserve aging;
- fee reconciliation mismatch.

### 9.6 Métricas de infraestrutura

- DB availability;
- DB latency;
- slow queries;
- lock wait/deadlocks;
- Redis availability;
- Redis latency;
- Redis memory/evictions;
- storage write/read health.

## 10. SLI, SLO e error budgets

A documentação canônica exige produção operável, dashboards/alertas, RTO/RPO e testes de restore, mas não define metas finais completas de SLO/error budget.

Portanto, a R6 deve propor faixas para decisão formal.

### 10.1 SLIs propostos

- Disponibilidade da API pública.
- Latência da API pública por rota crítica.
- Taxa de sucesso de criação de cobranças.
- Tempo de processamento de webhook validado.
- Tempo até confirmação de pagamento.
- Taxa de sucesso de settlement.
- Backlog/idade de filas críticas.
- Tempo de recuperação de Redis/DB/queue.
- Divergência de ledger/wallet.
- Sucesso de backup e restore.

### 10.2 SLOs pendentes de decisão

Propor para discussão, não como meta final:

- API availability: faixa candidata 99.5%–99.9%.
- p95 de rotas críticas: faixa candidata a definir por baseline real.
- Webhook processing latency: faixa candidata a definir por provider e fila.
- Queue oldest job age: limite por fila crítica.
- Settlement aging: limite por contrato operacional.
- RTO/RPO: usar docs existentes como ponto de partida, mas validar evidência real.

### 10.3 Error budget

Lacuna documental:

- Não há política canônica de error budget.

Plano R6:

- Definir se error budget será por API pública, pagamentos, webhooks e settlement.
- Vincular queima de budget a congelamento de mudanças, rollback ou modo degradado.

## 11. Plano de alertas

Cada alerta deve ser acionável, com runbook e responsável.

| Alerta | Condição | Severidade | Janela | Responsável | Ação inicial | Runbook |
| --- | --- | --- | --- | --- | --- | --- |
| API 5xx alto | aumento de 5xx em rotas críticas | Alta/Crítica | 5m/15m | SRE | verificar deploy, logs, DB, Redis | API indisponível / deploy rollback |
| Latência degradada | p95/p99 acima da baseline | Alta | 5m/15m | SRE | checar DB/Redis/PSP | banco lento / gateway degradado |
| Gateway timeout | timeout/error rate por PSP | Alta | 5m | SRE + Payments | abrir circuit breaker, pausar provider | `01-gateway-offline.md` |
| Webhook backlog | fila `webhooks_ingestion` acumulada | Alta | 5m/15m | SRE | checar Horizon/workers | webhook/fila travada |
| DLQ crescente | count/age acima do limite | Alta | 15m | SRE + Payments | triagem provider/erro | `05-dlq-crescente.md` |
| Worker parado | sem workers em fila crítica | Crítica | imediato | SRE | reiniciar Horizon/worker | `03-horizon-parado.md` |
| Failed jobs financeiros | failed jobs > 0 em jobs críticos | Alta | 5m | SRE + Engenharia | pausar/retry controlado | fila travada |
| Settlement falhou | settlement failed/aging | Alta | 15m/1h | Finance Ops | reconciliar e bloquear payout se necessário | settlement inconsistente |
| Divergência financeira | ledger/wallet mismatch | Crítica | imediato | Engenharia + Finance Ops | read-only financeiro e incidente | incidente financeiro |
| Backup falhou | backup job/report FAIL | Crítica | diário | SRE | abrir incidente DR | backup falha |
| Restore drill falhou | restore test FAIL | Crítica | por drill | SRE | bloquear RC/go-live | restore falha |
| DB indisponível | readiness DB false | Crítica | imediato | SRE/DBA | modo degradado/read-only | `04-banco-lento.md` |
| Redis indisponível | readiness Redis false | Alta/Crítica | imediato | SRE | avaliar locks/queues, read-only financeiro | `02-redis-indisponivel.md` |
| Auth anomaly | brute force/admin/API key abuse | Alta | 5m/15m | Security | rate limit/block/analisar | security incident |
| Scope denied spike | muitos `api.scope` 403 | Média/Alta | 15m | Security/API | investigar abuso ou integração quebrada | API key comprometida |

## 12. Health checks

### 12.1 Liveness

Deve responder apenas se o processo está vivo.

Regras:

- rápido;
- sem dependências externas;
- sem segredos;
- sem carga significativa.

### 12.2 Readiness

Deve validar se a instância pode receber tráfego.

Dependências planejadas:

- DB;
- Redis;
- queue backend;
- storage;
- migrations/schema compatível;
- config crítica;
- talvez circuit breaker global.

### 12.3 Deep health

Deve ser protegido e usado por operação, não por load balancer frequente.

Itens planejados:

- fila backlog/age;
- failed jobs;
- DLQ count/age;
- scheduler freshness;
- PSP sandbox/prod por ambiente;
- backup freshness;
- reconciliation freshness.

Cuidado:

- Não executar operações financeiras reais.
- Não fazer chamadas PSP caras com alta frequência.
- Não expor segredos.

## 13. Tracing ponta a ponta

Fluxos a rastrear:

1. Criação de cobrança:
   - request API/dashboard;
   - validação;
   - idempotency;
   - seleção de gateway;
   - chamada PSP;
   - persistência local;
   - resposta ao merchant.

2. Webhook:
   - recebimento;
   - validação de assinatura;
   - persistência do evento;
   - dispatch para fila;
   - processamento do worker;
   - atualização de charge/status;
   - ledger/wallet;
   - webhook outbound para merchant.

3. Settlement:
   - solicitação/autorização;
   - lock;
   - débito;
   - provider payout;
   - status;
   - reconciliation.

4. Falhas:
   - PSP timeout;
   - retry;
   - DLQ;
   - reprocess;
   - manual resolution.

R6 deve decidir ferramenta: OpenTelemetry, Sentry performance, logs correlacionados ou outro mecanismo documentado.

## 14. Backups e Disaster Recovery

### 14.1 Ativos protegidos

- Banco principal.
- Redis quando contém filas/sessions/locks relevantes.
- Storage de arquivos privados.
- Configurações e secrets.
- Certificados PSP.
- Logs/audit trail.
- Artefatos de deploy.

### 14.2 Política de backup

A documentação existente menciona RTO/RPO em runbooks e validações, mas R6 deve verificar evidência atual.

Itens obrigatórios:

- frequência;
- retenção;
- criptografia;
- cópia externa;
- controle de acesso;
- teste periódico de restore;
- evidência com data/ambiente/commit;
- reconciliation pós-restore.

### 14.3 RPO/RTO

Documentos existentes citam:

- Restore database: RTO alvo `< 60 minutos`, RPO alvo `< 24 horas`.
- Restore full: RTO alvo `< 90 minutos`, RPO alvo `< 24 horas`.
- Restore Redis: RTO alvo `< 15 minutos`.
- Backup validation antigo cita cenários PASS com RPO/RTO mais agressivos.

Decisão R6:

- Validar qual meta é canônica para OriginPay atual.
- Não aceitar evidência antiga sem reprodução ou vínculo com ambiente/commit atual.

### 14.4 Recuperação pós-restore

Obrigatório:

- `ledger:verify-integrity` ou equivalente;
- reconciliation financeira;
- validação wallet/ledger;
- validação de filas/DLQ;
- validação de PSP configs;
- remoção segura de modo read-only após checks.

## 15. Runbooks planejados

Runbooks existentes devem ser revisados e complementados.

Obrigatórios para R6:

- API indisponível.
- Banco indisponível/lento.
- Redis indisponível.
- Fila acumulada.
- Worker parado/Horizon parado.
- Webhook com falha.
- DLQ crescente.
- Gateway degradado/offline.
- Settlement inconsistente.
- Backup com falha.
- Restore com falha.
- Segredo comprometido.
- API Key comprometida.
- Incidente de isolamento entre tenants.
- Rollback de deploy.
- Incidente financeiro.

Cada runbook deve conter:

- sintomas;
- severidade;
- comandos seguros;
- ações proibidas;
- contenção;
- mitigação;
- validação;
- comunicação;
- critérios de encerramento;
- links para dashboards/queries.

## 16. Incident response

### 16.1 Severidades propostas

- SEV1: perda/duplicidade financeira, vazamento de segredo/dado sensível, indisponibilidade total de API financeira, divergência ledger/wallet.
- SEV2: degradação relevante de PSP/webhook/settlement sem perda confirmada, backlog crítico, restore/backup falho.
- SEV3: falha parcial com workaround, alerta de tendência, aumento moderado de erro.
- SEV4: anomalia informativa ou baixo impacto.

### 16.2 Papéis

- Incident Commander.
- SRE/Ops.
- Engenharia de Pagamentos.
- Segurança/Compliance.
- Finance Ops/Reconciliation.
- Comunicação/Customer support.

### 16.3 Processo

1. Detectar e classificar.
2. Abrir incidente com timeline.
3. Conter risco financeiro/security.
4. Mitigar serviço.
5. Preservar evidências.
6. Recuperar e reconciliar.
7. Comunicar stakeholders.
8. Postmortem sem culpa.
9. Criar testes/regressões e ações preventivas.
10. Encerrar apenas com critérios objetivos.

## 17. Riscos herdados do R5.1

| Risco | Impacto | Probabilidade | Urgência | Relação com R6 | Responsável sugerido | Fase apropriada | Bloqueia início da R6? |
| --- | --- | --- | --- | --- | --- | --- | --- |
| Pint global com baseline histórico | Médio: dificulta CI/lint global | Alta | Média | CI/CD e release gates | Engenharia | R6/R8 ou cleanup dedicado | Não |
| `permissions = ['*']` compatível | Alto se usado sem governança | Média | Média | Observabilidade deve monitorar uso e abusos de API keys | Segurança/API | R6 monitoramento; R7/R8 remoção | Não |
| Scopes adicionais em rotas não cobertas | Médio/Alto | Média | Média | Métricas/alertas devem observar scope denied/uso por endpoint | API/Security | R6 monitoramento; fase API subsequente | Não |
| Auditoria sensível não imutável | Alto para forense/compliance | Média | Alta | R6 inclui audit trail, logs e incident response | Security/Platform | R6 | Não para iniciar; pode bloquear aceite final se não endereçado |
| Ausência de step-up/dual control | Alto para ações financeiras críticas | Média | Alta | R6 deve alertar/auditar, mas controle é RBAC/segurança | Security/Finance Ops | R6 observabilidade + fase hardening posterior | Não para iniciar; pode bloquear go-live |

## 18. Fases internas propostas da R6

Compatível com `../docs/audit/05-product-roadmap.md` e produção operável.

### R6.1 — Baseline e inventário operacional

Objetivo:

- Capturar estado real de observabilidade, health, logs, queues, scheduler, runbooks, backups e restore.

Entregas:

- `../docs/audit/R6.1-observability-baseline.md`
- inventário de endpoints, filas, jobs, métricas e runbooks.

Testes:

- read-only commands;
- feature tests existentes para health/observability.

### R6.2 — Logging, redaction e correlation

Objetivo:

- Padronizar campos obrigatórios e redaction.
- Garantir `correlation_id` em API, jobs, webhooks e gateways.

Testes TDD:

- logs não expõem segredo;
- correlation ID propaga para resposta e contexto;
- jobs preservam contexto quando aplicável.

### R6.3 — Métricas e health checks

Objetivo:

- Definir/implementar métricas obrigatórias e health live/ready/deep seguros.

Testes TDD:

- health não expõe segredo;
- readiness falha fechado em DB/Redis down simulado;
- métricas incrementam sem alta cardinalidade.

### R6.4 — Tracing operacional

Objetivo:

- Rastrear ponta a ponta charge/gateway/webhook/worker/settlement.

Testes TDD:

- correlation ID presente em logs de cada etapa;
- IDs normalizados sem payload sensível.

### R6.5 — SLI/SLO, alertas e dashboards

Objetivo:

- Definir alertas acionáveis, severidade, janelas, responsáveis e runbooks.

Testes TDD:

- condições de alerta geram evento/PlatformAlert;
- alertas possuem runbook vinculado;
- alertas evitam labels de alta cardinalidade.

### R6.6 — Backups, restore e DR

Objetivo:

- Validar RPO/RTO, restore, evidência e reconciliation pós-restore.

Testes/validações:

- scripts/runbooks reproduzíveis;
- restore drill documentado;
- comandos de verificação financeira pós-restore.

### R6.7 — Incident response e runbooks

Objetivo:

- Formalizar severidades, papéis, comunicação, contenção, postmortem e encerramento.

Testes/validações:

- runbooks verificáveis;
- incident drill para Redis, DB, queue, DLQ, PSP timeout.

### R6.8 — Regressão e auditoria final R6

Objetivo:

- Consolidar evidências e validar critérios de aceite.

Validações:

- Pest completo;
- testes direcionados R6;
- Pint arquivos alterados;
- PHPStan se configurado;
- docs R6 final.

## 19. Critérios de entrada da R6

### 19.1 Já satisfeitos

- R5.1 concluído e validado.
- Relatórios R5.1 presentes.
- Próxima fase definida por roadmap canônico.
- Pest completo R5.1 verde.
- Pint arquivos alterados R5.1 verde.
- Não há bloqueador conhecido no escopo R5.1.

### 19.2 Pendentes antes de implementação

- Resolver/confirmar raiz Git válida para checkpoint.
- Criar checkpoint R5.1 aprovado.
- Confirmar se documentos antigos de go-live serão marcados como históricos ou reconciliados.
- Aprovar plano R6 antes de alterar código.

### 19.3 Impedem implementação

- Ausência de checkpoint Git confiável do R5.1 impede início seguro de implementação R6.

### 19.4 Impedem apenas go-live, não a R6

- Pint global baseline histórico.
- PHPStan ausente.
- Coverage crítico sem relatório numérico.
- SLO/error budget sem decisão formal.
- DR/restore ainda não validado no contexto atual.

### 19.5 Dependem de decisão arquitetural

- Ferramenta de tracing.
- Backend de métricas.
- Política final de SLO/error budget.
- Persistência imutável de auditoria.
- RTO/RPO oficial atual.

### 19.6 Dependem de infraestrutura externa

- Redis HA.
- Horizon/process manager em ambiente alvo.
- Backups reais e storage externo.
- Monitoramento externo, se usado.
- Alerting/on-call.
- Provedores PSP sandbox/prod.

## 20. Critérios de aceite da R6

A R6 só estará pronta quando houver evidência de:

- Dashboards/alertas definidos.
- Runbooks revisados e vinculados a alertas.
- RTO/RPO definidos e testados.
- Backup/restore testados.
- Health live/ready/deep seguros.
- Métricas obrigatórias implementadas/validadas.
- Logs estruturados e redaction testada.
- Correlation ID ponta a ponta em fluxos críticos.
- Filas críticas monitoradas por backlog/age/failed jobs.
- DLQ com SLA operacional e alertas.
- Incident drills documentados para Redis, DB, queue, DLQ e PSP timeout.
- Regressão Pest completa verde.
- Testes direcionados R6 verdes.
- Pint nos arquivos alterados verde.
- PHPStan executado se configurado.
- Documentação R6 final em `../docs/audit`.

## 21. Estratégia TDD da R6

A R6 deve ser implementada em TDD.

Testes planejados:

- Logs:
  - campos obrigatórios presentes;
  - segredos mascarados;
  - payloads sensíveis não aparecem em claro.

- Correlation ID:
  - header aceito/gerado;
  - resposta contém ID;
  - logs/jobs preservam contexto quando aplicável.

- Métricas:
  - incrementos por evento;
  - latência registrada;
  - labels normalizados;
  - sem alta cardinalidade.

- Health checks:
  - liveness não toca dependências;
  - readiness falha com DB/Redis indisponível simulado;
  - deep health protegido;
  - não expõe segredos.

- Filas/DLQ:
  - backlog/age gera alerta;
  - failed jobs críticos geram alerta;
  - DLQ crescente gera alerta;
  - reprocess tem auditoria.

- Backups/restore:
  - runbook verificável;
  - evidência de restore documentável;
  - pós-restore exige reconciliation.

- Incident response:
  - severidade atribuída corretamente;
  - runbook vinculado;
  - postmortem template/evidência.

## 22. Estratégia de regressão

Antes de concluir R6:

```bash
"E:/laragon/bin/php/php-8.3.30-Win32-vs16-x64/php.exe" artisan test
```

Testes direcionados esperados:

- Health/readiness/deep health.
- Correlation/logging/redaction.
- Gateway/webhook metrics.
- Queue/DLQ alerting.
- Backup/restore runbook validation.
- Incident response rules.
- Security logging/monitoring failure cases.

Pint:

```bash
"E:/laragon/bin/php/php-8.3.30-Win32-vs16-x64/php.exe" vendor/bin/pint --test [arquivos alterados]
```

PHPStan:

- Executar somente se configuração for criada/encontrada.

## 23. Riscos da R6

| Risco | Impacto | Mitigação |
| --- | --- | --- |
| Alertas ruidosos demais | Fadiga operacional | Thresholds por baseline e runbooks acionáveis |
| Métricas com alta cardinalidade | Custo/indisponibilidade do observability backend | Labels normalizados e revisão de cardinalidade |
| Health checks pesados | Load indevido e falsos negativos | Separar liveness/readiness/deep health |
| Logs vazando segredo | Incidente LGPD/security | Redaction central e testes negativos |
| DR documentado mas não testado | Falsa sensação de segurança | Restore drill com evidência atual |
| RTO/RPO conflitantes | Decisão operacional frágil | Formalizar meta canônica e aprovar |
| Sem Git checkpoint | Risco de misturar fases | Resolver raiz Git antes de implementar |

## 24. Decisões pendentes

1. Onde está a raiz Git válida ou qual procedimento para restaurá-la?
2. Os documentos de go-live antigos serão arquivados, marcados como históricos ou reconciliados?
3. Qual backend de métricas/tracing será usado?
4. Quais SLOs e error budgets oficiais serão adotados?
5. Qual RTO/RPO oficial prevalece?
6. Auditoria crítica será persistida em tabela append-only nesta R6 ou em fase separada?
7. PHPStan será configurado como gate?
8. Pint global será saneado agora ou mantido como baseline técnico separado?

## 25. Definição de pronto da R6

R6 estará pronta somente quando:

- Checklist de aceite R6 estiver completo.
- Evidências estiverem em `../docs/audit`.
- Pest completo estiver verde.
- Testes direcionados R6 estiverem verdes.
- Pint arquivos alterados estiver verde.
- PHPStan estiver executado ou formalmente não configurado.
- RPO/RTO e restore estiverem validados com evidência.
- Alertas tiverem runbooks e responsáveis.
- Incidents drills mínimos estiverem documentados.
- Nenhum crítico/alto de observabilidade, SRE ou DR permanecer aberto sem aceite formal.

## 26. Autorização para iniciar R6 vs go-live

Autorizado após checkpoint:

- Planejamento detalhado.
- Implementação incremental R6 em TDD.
- Testes de observabilidade/SRE/DR.
- Documentação de evidências.

Não autorizado:

- Go-live.
- Release Candidate.
- Deploy de produção.
- Migrations sem plano aprovado.
- Commit/tag sem autorização explícita.

## 27. Decisão final desta rodada

A R6 está tecnicamente planejada e canonicamente identificada, mas **não está pronta para implementação imediata** enquanto a raiz Git válida não for localizada/restaurada e o checkpoint R5.1 não for criado de forma segura.

Bloqueador de início de implementação:

- Ausência de repositório Git válido na árvore esperada do projeto.

Não bloqueia o planejamento:

- R5.1 validado.
- Roadmap canônico define R6.
- Documentação R6 suficiente para plano técnico inicial.

## 28. Checkpoint Git R5.1 executado nesta rodada

### 28.1 Estado encontrado do `.git`

A raiz inspecionada foi `E:/projetos/DigiKash v1.0.5/DigiKash v1.0.5`, a pasta ancestral mínima que contém simultaneamente `core/` e `docs/`.

Diagnóstico:

- `.git` existia na raiz correta.
- `git rev-parse --show-toplevel` retornou a própria raiz do projeto.
- `git status` funcionou e indicou branch `main` sem commits prévios.
- `.git/HEAD` existia e apontava para `refs/heads/main`.
- `.git/config` existia.
- `.git/objects` existia.
- `git fsck --full` não encontrou corrupção; reportou apenas ausência de referências padrão, compatível com repositório inicial sem commits.

Conclusão: o `.git` não estava corrompido; era um repositório Git inicial válido, sem histórico anterior a preservar.

### 28.2 Reparo/inicialização

Não foi necessário remover, substituir ou reinicializar o diretório `.git`. Não houve `reset`, `clean`, `checkout`, `restore` ou `stash` de arquivos de trabalho. A ação segura foi preservar o `.git` existente e usar a raiz correta.

### 28.3 Raiz, branch e remote

- Raiz Git final: `E:/projetos/DigiKash v1.0.5/DigiKash v1.0.5`.
- Branch: `main`.
- Remote: nenhum configurado.
- Push: não executado.

### 28.4 Auditoria pré-commit e `.gitignore`

O `.gitignore` raiz foi revisado/ajustado para proteger categorias locais e sensíveis, incluindo:

- `.env` e variações, preservando `.env.example`.
- `vendor/`.
- `node_modules/`.
- logs, caches, sessions, views e storage local.
- bancos SQLite/DB locais.
- dumps SQL/dump.
- coverage, dist e build.
- uploads/storage público gerado.
- arquivos de IDE.
- arquivos temporários e backups.
- certificados/chaves privadas.
- artefatos locais de agentes e backups.
- arquivos gerados/scratch observados na raiz.
- `core/_archive_legacy_review/` e `core/DB/` como arquivos locais/legados não pertencentes ao baseline canônico.

Auditoria de secrets:

- `.env` raiz e `core/.env` foram detectados como arquivos de ambiente local e permaneceram ignorados.
- `docs/` foi auditado por indicadores de credenciais reais; nenhum segredo real conhecido foi identificado.
- O stage foi verificado para impedir `.env`, `vendor`, `node_modules`, logs, caches, bancos locais, dumps, certificados/chaves privadas e segredos conhecidos.
- Resultado da verificação de caminhos sensíveis staged: `NONE`, com exceção intencional de `.env.example` versionável.

### 28.5 Validações executadas

PHP utilizado exclusivamente:

```text
E:/laragon/bin/php/php-8.3.30-Win32-vs16-x64/php.exe
```

Resultados:

- Pest completo: `413 passed (1426 assertions)`.
- Pint nos arquivos R5.1 alterados: `PASS 10 files`.
- PHPStan: não executado porque não foi encontrada configuração `phpstan.neon*`.

### 28.6 Stage, commit e tag

Antes do commit foram revisados:

- `git status --short`.
- `git status --ignored --short`.
- `git diff --cached --stat`.
- `git diff --cached --name-status`.
- verificação automatizada de caminhos sensíveis staged.

Quantidade de arquivos staged/versionados no baseline: `3275`.

Commit criado:

- Hash: `cbfa3938ac41b031ae29a76d1576b7b0dca57b0e`.
- Mensagem: `chore: establish OriginPay baseline through R5.1`.

Tag local criada:

- `r5.1-admin-hardening-final`.

### 28.7 Estado final esperado

Após commit/tag, o working tree deve permanecer sem alterações versionáveis pendentes; arquivos locais sensíveis/gerados devem aparecer apenas como ignored quando consultados com `git status --ignored --short`.

### 28.8 Autorização limitada para R6

Com o checkpoint Git R5.1 concluído, a OriginPay fica autorizada apenas a iniciar a implementação planejada da R6 em rodada posterior, seguindo a documentação canônica e TDD. Esta autorização não libera go-live, Release Candidate, deploy de produção, push, remote ou processamento financeiro real.
