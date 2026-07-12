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

## 29. R6.1 — Incremento 1: readiness fail-closed do monitor token

### 29.1 Decisão arquitetural

A documentação canônica exige que health checks não exponham segredos e que validações de segurança falhem fechado. A readiness anterior aceitava fallback textual `default-secret-token` quando `MONITOR_TOKEN` não estava configurado, o que criava um segredo padrão conhecido.

Decisão R6.1:

- Remover fallback de token padrão conhecido.
- Exigir configuração explícita de `MONITOR_TOKEN` via `config('app.monitor_token')`.
- Quando o token não estiver configurado, `/api/health/ready` retorna `503` com `monitor_token=ERROR`.
- Quando o token estiver configurado e o header for inválido, retorna `401` sem vazar o valor esperado.
- Comparação feita com `hash_equals`.

### 29.2 Arquivos alterados

- `core/app/Http/Controllers/HealthCheckController.php`
- `core/config/app.php`
- `core/.env.example`
- `core/tests/Feature/R6HealthCheckTest.php`
- `docs/audit/R6-observability-sre-dr-plan.md`

### 29.3 Testes TDD criados

- `test_readiness_fails_closed_when_monitor_token_is_not_configured`
- `test_readiness_rejects_invalid_monitor_token_without_leaking_expected_value`

### 29.4 Validação direcionada

```text
E:/laragon/bin/php/php-8.3.30-Win32-vs16-x64/php.exe artisan test tests/Feature/R6HealthCheckTest.php
```

Resultado:

```text
2 passed (6 assertions)
```

### 29.5 Lacunas documentais registradas

- A documentação exige health live/ready/deep, mas ainda não define contrato JSON final completo para readiness/deep health.
- A documentação exige dashboards/alertas e runbooks, mas ainda não define backend canônico de métricas/tracing.
- A documentação existente de observabilidade/DR contém documentos antigos com status PASS; estes devem continuar tratados como evidência histórica até serem revalidados contra o estado atual.

### 29.6 Próximo incremento recomendado

Implementar R6.1/health readiness ampliado em TDD, adicionando verificação segura e não sensível para fila/queue backend e/ou migrations, sem executar operações financeiras e sem expor segredos.

## 30. R6.1 — Incremento 2: readiness de queue backend e migrations

### 30.1 Verificação autônoma do aviso do verificador

Antes deste incremento, foi relido o estado pelos caminhos corretos a partir da raiz do projeto:

- `core/config/app.php`
- `core/app/Http/Controllers/HealthCheckController.php`
- `core/.env.example`
- `core/tests/Feature/R6HealthCheckTest.php`

Resultado objetivo:

1. `core/config/app.php` contém `monitor_token`.
2. A configuração usa `env('MONITOR_TOKEN')`.
3. Não há default secreto para o monitor token.
4. `HealthCheckController` consome `config('app.monitor_token')`.
5. `core/.env.example` contém `MONITOR_TOKEN=` sem valor real.
6. Os testes existentes representam o comportamento atual de fail-closed e não vazamento do token.

Conclusão: o aviso anterior era falso positivo de resolução de caminho causado por duplicação de `core/` no caminho do verificador. Nenhum arquivo foi alterado apenas para satisfazer esse aviso.

### 30.2 Decisão arquitetural

A documentação canônica exige que readiness valide DB, Redis, queue, storage, migrations e configuração crítica sem expor segredos. Este incremento adiciona apenas dois checks pequenos:

- `queue`: valida que o backend de fila configurado existe em `queue.connections`.
- `migrations`: valida que a tabela de controle de migrations configurada existe no banco.

Decisões de segurança/critique:

- O endpoint continua protegido por `X-Monitor-Token`.
- Valores internos de conexão, nomes inválidos e nomes de tabela não são retornados no JSON.
- Falhas geram apenas `ERROR` por categoria.
- Erros de runtime, incluindo ausência de extensão Redis ou falhas de conectividade, são capturados como estado operacional `DOWN`, evitando 500 e evitando leak de stack trace.
- O check de queue é intencionalmente de configuração neste incremento; backlog/idade/failed jobs ficam para deep health ou incremento posterior.

### 30.3 Arquivos alterados

- `core/app/Http/Controllers/HealthCheckController.php`
- `core/tests/Feature/R6HealthCheckTest.php`
- `docs/audit/R6-observability-sre-dr-plan.md`

### 30.4 Testes TDD criados

- `test_readiness_reports_queue_backend_configuration_state`
- `test_readiness_reports_migrations_repository_state`

### 30.5 Validação direcionada

```text
E:/laragon/bin/php/php-8.3.30-Win32-vs16-x64/php.exe artisan test tests/Feature/R6HealthCheckTest.php
```

Resultado:

```text
4 passed (14 assertions)
```

### 30.6 Lacunas documentais registradas

- A documentação exige readiness para `queue`, mas não define se o check mínimo deve validar apenas configuração, conexão do backend, capacidade de enqueue/dequeue sintético, backlog ou todos esses itens.
- A documentação exige migrations em readiness, mas não define contrato para distinguir tabela ausente, migrations pendentes ou schema incompatível.
- A documentação exige failed jobs, backlog e scheduler freshness, mas estes pertencem a deep health ou incrementos posteriores, não a este incremento pequeno.

### 30.7 Próximo incremento recomendado

Implementar R6.1/health readiness para configuração crítica PSP segura ou iniciar deep health protegido com checks read-only de failed jobs/backlog/scheduler freshness, mantendo TDD e sem executar operações financeiras reais.

## 31. R6.1 — Incremento 3: readiness de configurações críticas da plataforma

### 31.1 Escopo

Este incremento fortalece apenas o `GET /api/health/ready`. Não implementa deep health completo, métricas externas, tracing distribuído, dashboards, alertas de produção, backup automatizado, DR completo, deploy ou processamento financeiro real.

O endpoint permanece somente leitura:

- não cria registros;
- não publica jobs;
- não executa pagamentos;
- não chama gateways;
- não altera banco;
- não altera filas.

### 31.2 Dependências críticas incluídas

A partir da documentação canônica e configuração existente, foram consideradas críticas para aceitar tráfego:

- `app_key`: `config('app.key')`, derivado de `APP_KEY`, obrigatório para criptografia Laravel.
- `monitor_token`: já validado em incremento anterior via `config('app.monitor_token')`.
- `database`: conexão DB já validada.
- `redis`: já validado por ser usado por queue/cache/locks e requerido nos documentos de HA.
- `queue`: backend configurado já validado.
- `migrations`: tabela de migrations já validada.
- `storage`: check local já existente.
- `gateway_efi`: configuração mínima Efí existente em `config/services.php`, sem chamada externa.

Para `gateway_efi`, o readiness verifica somente presença de configuração mínima:

- `client_id`;
- `client_secret`;
- `pix_key`;
- `certificate_path`.

O readiness não valida existência do certificado em disco nesta rodada, porque o contrato pedido é de configurações críticas e porque o check de arquivo pode exigir decisão operacional sobre path relativo/absoluto e ambiente. Essa lacuna fica registrada para incremento posterior.

### 31.3 Contrato JSON consolidado do readiness

Endpoint:

```text
GET /api/health/ready
```

Headers:

```text
X-Monitor-Token: <token configurado em MONITOR_TOKEN>
```

Resposta autorizada:

```json
{
  "status": "UP|DOWN",
  "service": "originpay",
  "checked_at": "ISO-8601 timestamp",
  "checks": {
    "app_key": "OK|ERROR",
    "database": "OK|ERROR",
    "redis": "OK|ERROR",
    "queue": "OK|ERROR",
    "migrations": "OK|ERROR",
    "storage": "OK|ERROR",
    "gateway_efi": "OK|ERROR"
  }
}
```

Significado:

- `status=UP`: todos os checks executados retornaram `OK`.
- `status=DOWN`: pelo menos um check retornou `ERROR`.
- `checks.*=OK`: categoria crítica disponível/configurada para readiness.
- `checks.*=ERROR`: categoria crítica indisponível ou incompleta.
- HTTP `200`: readiness `UP`.
- HTTP `503`: readiness `DOWN`.
- HTTP `401`: token de monitoramento ausente/incorreto quando `MONITOR_TOKEN` está configurado.
- HTTP `503` com `checks.monitor_token=ERROR`: `MONITOR_TOKEN` não configurado.

Regras de segurança do contrato:

- nunca retornar valores de secrets;
- nunca retornar nomes de variáveis secretas como `APP_KEY`, `EFI_CLIENT_SECRET` etc.;
- nunca retornar stack traces;
- nunca retornar detalhes de provider que permitam enumeração de credenciais;
- usar apenas categorias estáveis de check.

### 31.4 Diferenças entre live, ready e deep health

- `live`: prova que o processo HTTP está vivo. Deve ser rápido, público/seguro e sem dependências externas.
- `ready`: prova que a instância pode receber tráfego de forma segura. Pode checar dependências críticas e configuração mínima, mas deve permanecer leve, read-only e sem chamadas financeiras/PSP.
- `deep health`: inspeção operacional protegida para SRE. Pode avaliar backlog, failed jobs, DLQ age/count, scheduler freshness, PSP sandbox/prod e backup freshness. Não foi implementado nesta rodada.

### 31.5 Decisões arquiteturais

- `APP_KEY` é readiness crítico porque criptografia/session/cookies/segredos dependem dele.
- `gateway_efi` é readiness crítico porque a arquitetura atual contém provider Efí e documentação exige config PSP crítica em readiness.
- O check `gateway_efi` é propositalmente de configuração, não de conectividade PSP.
- Não há exposição de valores nem nomes de segredos no JSON.
- Checks continuam independentes: uma falha marca somente sua categoria como `ERROR` e o status global como `DOWN`.

### 31.6 Testes TDD criados

- `test_readiness_reports_missing_application_key_without_exposing_secret_names_or_values`
- `test_readiness_reports_missing_efi_gateway_config_without_exposing_secret_names_or_values`

### 31.7 Validação direcionada

```text
E:/laragon/bin/php/php-8.3.30-Win32-vs16-x64/php.exe artisan test tests/Feature/R6HealthCheckTest.php
```

Resultado:

```text
6 passed (29 assertions)
```

### 31.8 Lacunas e riscos restantes

- O contrato ainda não define se readiness deve validar existência de certificado PSP em disco ou deixar isso para deep health.
- O contrato ainda não define gateways múltiplos além de Efí como readiness crítico.
- O contrato ainda não valida migrations pendentes, apenas existência da tabela de controle.
- Backlog, failed jobs, DLQ age/count e scheduler freshness continuam fora do readiness e devem ser tratados em deep health protegido.
- Não há backend canônico de métricas/tracing definido.

### 31.9 Próximo incremento recomendado

Concluir R6.1 com eventual decisão sobre certificado PSP/configuração de múltiplos gateways, ou avançar para o primeiro incremento pequeno de deep health protegido e read-only, começando por failed jobs/backlog/scheduler freshness, sem chamadas PSP e sem operações financeiras reais.


## 32. R6.1 — Revisão arquitetural final do readiness

### 32.1 Evidências objetivas sobre gateways

- Existem múltiplos gateways no código: `mock`, `efi`, `sicoob` na camada moderna `App\Services\Gateways`, além de providers legados em `app/Payment/*` e registros em `PaymentGatewaySeeder` como `moneroo`, `strowallet`, `binance`, `airtel`, `blockchain`, `blockio`, `bitpayserver`, `cashmaal`, `coingate`, `coinpayments` e outros.
- Existe abstração/adaptador: `App\Contracts\Gateways\GatewayInterface`, `GatewayRegistry`, `GatewayManager`, adapters `EfiGatewayAdapter`, `SicoobGatewayAdapter` e `MockGatewayAdapter`.
- Existe failover por merchant: `GatewayManager::authorize()` lê `MerchantGateway::where('merchant_id', ...)`, filtra `enabled=true`, ordena por `priority asc` e tenta o próximo gateway quando há falha técnica, health failure ou circuit breaker aberto.
- Existe prioridade: `merchant_gateways.priority` e `payment_gateways.priority`.
- Existem credenciais por tenant/merchant em `merchant_gateways.configuration` e também credenciais globais/configuracionais em `config/services.php` e `payment_gateways.credentials`.
- Efí não é o único gateway e não está demonstrada como dependência global obrigatória de toda instância.
- A aplicação consegue operar parte do tráfego sem Efí: sandbox usa fallback `mock`; rotas administrativas, auth, ledger, webhooks, developer portal e outros fluxos não dependem de Efí para aceitar requisição HTTP.
- A ausência de gateway deve desabilitar capacidade operacional específica de pagamento/PSP, não derrubar globalmente a instância, salvo política futura explícita que exija PSP global ativo.
- Existe gateway padrão/fallback de sandbox (`mock`) e status/habilitação por `merchant_gateways.enabled`, `payment_gateways.status`, `is_maintenance` e flags de suporte.
- Certificado é requisito de fluxos Efí específicos, não evidência de requisito global para todos os gateways ou todos os fluxos.
- Há ambientes de teste/desenvolvimento/sandbox sem certificado real: `mock` sandbox e `services.efi.env` com default `sandbox`; o seed deixa Efí inativa por padrão até configurar.

### 32.2 Decisão formal sobre `gateway_efi`

Cenário aplicado: **B — Efí é opcional, substituível ou específica de tenant**.

- `gateway_efi` foi removido do readiness global.
- Ausência de configuração Efí global em `.env` não retorna `503` global.
- Disponibilidade/configuração de PSP passa a ser capacidade operacional para deep health futuro ou endpoint operacional específico, protegido e sem exposição de segredos.
- O readiness global valida apenas infraestrutura global necessária à instância.

### 32.3 Decisão sobre certificado PSP

- O certificado físico não faz parte do readiness global nesta R6.1.
- Para Efí, certificado deve ser validado no momento de habilitar/configurar gateway, no boot apenas se política futura marcar Efí como globalmente obrigatória, ou em deep health operacional protegido.
- Readiness não deve falhar globalmente por certificado de gateway opcional ausente.
- Qualquer check futuro de certificado deve ser local, leve, sem leitura/retorno de conteúdo, sem caminho absoluto no JSON e sem parsing pesado por requisição.

### 32.4 Matriz de criticidade dos checks atuais

| Check | Global/opcional | Criticidade | Operação | Efeito colateral | Custo/timeout | Falha | Vazamento | Adequação |
| --- | --- | --- | --- | --- | --- | --- | --- | --- |
| `monitor_token` | Global | Crítica | compara header com config via `hash_equals` | nenhum | mínimo | 401 inválido; 503 se não configurado | baixo | readiness adequado |
| `app_key` | Global | Crítica | valida presença de `config('app.key')` | nenhum | mínimo | `ERROR` + 503 | baixo | readiness adequado |
| `database` | Global | Crítica | `DB::connection()->getPdo()` | nenhum intencional | baixo, depende timeout PDO | `ERROR` + 503 | baixo | readiness adequado |
| `redis` | Documentado como baseline R0/R6 | Alta/Crítica | `Redis::connection()->ping()` | nenhum | baixo, depende timeout Redis | `ERROR` + 503 | baixo | readiness adequado enquanto Redis permanecer baseline canônico; se Redis se tornar opcional, revisar |
| `queue` | Global como capacidade configurada | Alta | valida `queue.default` presente em `queue.connections` | nenhum; não publica job | mínimo | `ERROR` + 503 | baixo | readiness mínimo; conectividade/backlog ficam para deep health |
| `migrations` | Global DB/schema | Alta | `Schema::hasTable(migrations)` | consulta metadata; sem migration | baixo | `ERROR` + 503 | baixo | readiness mínimo; pendências ficam para deploy gate/deep health |
| `storage` | Global local | Alta | escreve e remove arquivo sintético em disk local | temporário, sem resíduo esperado | baixo | `ERROR` + 503 | baixo | aceitável para readiness com cleanup; pode ser reavaliado |
| `gateway_efi` | Opcional/tenant/capacidade PSP | Não global | removido | nenhum | nenhum | não afeta status global | nenhum | deep health/endpoint operacional futuro |

### 32.5 Contrato final do readiness R6.1

```text
GET /api/health/ready
X-Monitor-Token: <MONITOR_TOKEN>
```

```json
{
  "status": "UP|DOWN",
  "service": "originpay",
  "checked_at": "ISO-8601",
  "checks": {
    "app_key": "OK|ERROR",
    "database": "OK|ERROR",
    "redis": "OK|ERROR",
    "queue": "OK|ERROR",
    "migrations": "OK|ERROR",
    "storage": "OK|ERROR"
  }
}
```

Códigos HTTP: `200` quando todos os checks globais estão `OK`; `503` quando qualquer check global está `ERROR` ou `MONITOR_TOKEN` não está configurado; `401` quando o token configurado não confere.

Política: checks retornam apenas `OK`/`ERROR`; não retorna exceções, stack trace, hostname, DSN, SQL, caminhos, nomes de secrets, tokens, certificados, credenciais ou dados de tenant; não chama PSP, não cria transação financeira, não publica job e não consulta credenciais de tenant.

### 32.6 Arquivos alterados

- `core/app/Http/Controllers/HealthCheckController.php`
- `core/tests/Feature/R6HealthCheckTest.php`
- `docs/audit/R6-observability-sre-dr-plan.md`

### 32.7 Testes TDD criados/ajustados

- `test_readiness_does_not_fail_globally_when_optional_efi_gateway_config_is_missing`

Ciclo TDD: RED falhou com HTTP 503 por `gateway_efi`; GREEN removeu `gateway_efi` do controller; teste direcionado passou.

### 32.8 Validação final executada

Teste direcionado:

```text
E:/laragon/bin/php/php-8.3.30-Win32-vs16-x64/php.exe artisan test tests/Feature/R6HealthCheckTest.php
```

Resultado:

```text
6 passed (28 assertions)
```

Regressão completa:

```text
E:/laragon/bin/php/php-8.3.30-Win32-vs16-x64/php.exe artisan test
```

Resultado:

```text
419 passed (1454 assertions)
```

Pint nos arquivos PHP alterados:

```text
E:/laragon/bin/php/php-8.3.30-Win32-vs16-x64/php.exe vendor/bin/pint --test app/Http/Controllers/HealthCheckController.php tests/Feature/R6HealthCheckTest.php
```

Resultado:

```text
PASS 2 files
```

PHPStan: não executado porque não existe `phpstan.neon*` no projeto.

### 32.9 Limitações, lacunas e riscos restantes

- Redis permanece como dependência crítica por baseline documental; se a arquitetura futura permitir execução sem Redis, o readiness deve ser revisto.
- `queue` ainda é check de configuração, não prova conectividade do backend nem capacidade de processamento.
- `migrations` ainda valida apenas existência da tabela, não pendências de schema.
- `storage` realiza escrita sintética local temporária; pode ser reavaliada se houver política estrita de zero escrita em readiness.
- Deep health, backlog, failed jobs, DLQ, scheduler freshness, métricas, tracing, dashboards, backup/restore e DR continuam fora do escopo desta rodada.

### 32.10 Decisão formal de encerramento

R6.1 — CONCLUÍDA

Justificativa: o contrato global de readiness ficou estável, sem dependência PSP incorreta, com autenticação fail-closed, checks globais leves/read-only ou localmente seguros, redaction preservada e teste automatizado cobrindo a decisão arquitetural sobre Efí opcional.


## R6.2 — Logging estruturado, Correlation ID e contexto operacional

### Escopo executado

Este incremento criou a fundação local de observabilidade para logs e contexto operacional. Não foram implementados métricas, tracing distribuído, dashboards, alertas, deep health, Prometheus, OpenTelemetry, Grafana, Loki ou Jaeger.

### Validação inicial e lacunas confirmadas

Inspeção realizada em `config/logging.php`, `bootstrap/app.php`, middleware globais, usos de `Log::`, jobs, filas/listeners/eventos e documentação canônica. O estado local já possuía `CorrelationIdMiddleware`, canais de log por domínio e alguns logs/auditoria pontuais, porém com lacunas:

- havia Correlation ID básico por request, mas o contexto operacional era incompleto e usava nomes divergentes (`method`, `route`, `authenticated_user_id`);
- havia Request ID, mas não havia contrato claro entre request id e correlation id;
- havia contexto global via Laravel Context, mas sem padronização completa dos campos mínimos da R6.2;
- havia redaction parcial em pontos específicos, mas não mecanismo centralizado para logs operacionais;
- havia canais `payments`, `webhooks`, `gateway`, `security`, `audit`, `performance`, `single` e `daily`, mas sem processor comum para injetar contexto e aplicar redaction de forma uniforme;
- jobs/listeners/eventos já existem e alguns carregam correlation id no domínio, mas a propagação ponta a ponta para todos os jobs financeiros ainda requer incremento dedicado.

### Arquitetura adotada

Arquitetura mantida simples e compatível com Laravel:

- `app/Http/Middleware/CorrelationIdMiddleware.php` permanece como middleware dedicado global;
- `app/Support/Observability/StructuredLogContext.php` centraliza o contrato de contexto operacional mínimo;
- `app/Support/Observability/LogRedactor.php` centraliza mascaramento de dados sensíveis;
- `app/Logging/StructuredLogProcessor.php` atua como tap de Monolog/Laravel para injetar contexto e redigir payloads de log;
- `config/logging.php` conecta o processor aos canais operacionais existentes.

### Fluxo do Correlation ID

1. A request entra no middleware global.
2. Se o cliente enviar `X-Correlation-ID`, esse valor é preservado exatamente.
3. Se o header não existir, é gerado UUID v4.
4. O valor é registrado no `EventContext` e no `Illuminate\Support\Facades\Context`.
5. O mesmo valor permanece disponível durante a request para controllers, services, actions, logs e futuras integrações.
6. A resposta sempre recebe `X-Correlation-ID`.
7. O middleware não gera novo ID dentro da mesma request.

### Formato de contexto mínimo dos logs

Campos suportados pela fundação R6.2, omitindo valores inexistentes:

- `correlation_id`;
- `timestamp`;
- `tenant_id`;
- `merchant_id`;
- `user_id`;
- `api_key_id`;
- `gateway`;
- `payment_id`;
- `request_method`;
- `request_path`;
- `ip`;
- `status_code`;
- `duration_ms`.

### Política de redaction

`LogRedactor` é o ponto central de mascaramento. Ele redige chaves sensíveis em arrays aninhados e padrões sensíveis em strings. Escopo inicial:

- authorization / proxy-authorization;
- bearer tokens;
- cookies / set-cookie;
- API keys (`x-api-key`, `api_key`, variações);
- tokens;
- secrets / `client_secret`;
- password / senha;
- pix key;
- certificados / private key.

A política evita `replace()` manual espalhado e deve ser estendida no próprio redactor conforme novos campos sensíveis forem identificados.

### Testes criados

Arquivo: `core/tests/Feature/R62ObservabilityFoundationTest.php`.

Cobertura adicionada:

- gera Correlation ID quando ausente;
- preserva Correlation ID recebido;
- retorna `X-Correlation-ID`;
- duas requests diferentes possuem IDs diferentes;
- mesma request mantém o mesmo ID;
- contexto de log/request recebe Correlation ID;
- redaction remove `Authorization`;
- redaction remove `Bearer`;
- redaction remove `client_secret`;
- redaction remove API Key;
- redaction remove cookies/headers sensíveis em payload aninhado.

### Validação executada

Com PHP exclusivo do Laragon (`E:\laragonin\php\php-8.3.30-Win32-vs16-x64\php.exe`):

- Teste direcionado: `PASS — 7 passed (16 assertions)`;
- Regressão completa: `PASS — 426 passed (1470 assertions)`;
- Pint nos arquivos alterados: `PASS — 6 files`;
- PHPStan: não executado porque não há `phpstan.neon` ou `phpstan.neon.dist` no diretório `core`.

### Riscos restantes

- Propagação explícita para todos os jobs financeiros, listeners e eventos internos ainda deve ser auditada caso a caso;
- nem todo uso legado de `Log::` foi reescrito para contexto semântico de domínio (`gateway`, `payment_id`, `merchant_id` etc.);
- logs de exception usam o canal/processors configurados, mas ainda falta teste dedicado para exceções reportadas pelo handler sem alterar comportamento funcional;
- redaction central cobre padrões principais, mas deve evoluir com novos payloads reais de PSPs e integrações;
- formato estruturado ainda depende dos formatters atuais dos canais Laravel/Monolog; o incremento não troca backend nem adota JSON formatter obrigatório.

### Próximo incremento recomendado

R6.3 deve focar propagação operacional em jobs/eventos/listeners e exceptions: serializar/restaurar correlation id em jobs críticos, padronizar contexto semântico em logs financeiros (`gateway`, `payment_id`, `merchant_id`, `api_key_id`) e criar testes dedicados para exceções reportadas com correlation id e redaction.


## R6.2 — Retomada e encerramento formal com integração real do logging

### Estado real de `core/config/logging.php`

A inspeção objetiva confirmou que os canais `payments`, `webhooks`, `gateway`, `security`, `audit`, `performance`, `single` e `daily` existem em `core/config/logging.php`. Os canais de domínio usam driver `daily`; `single` usa driver `single`; `daily` usa driver `daily`. Todos preservam seus `path`, `level`, `days`/retenção quando aplicável e `replace_placeholders`, e todos declaram `tap => [App\Logging\StructuredLogProcessor::class]` exatamente uma vez.

| Canal | Driver | Processor conectado | Duplicação | Carregamento |
| --- | --- | --- | --- | --- |
| `payments` | `daily` | sim, via `tap` | não | PASS |
| `webhooks` | `daily` | sim, via `tap` | não | PASS |
| `gateway` | `daily` | sim, via `tap` | não | PASS |
| `security` | `daily` | sim, via `tap` | não | PASS |
| `audit` | `daily` | sim, via `tap` | não | PASS |
| `performance` | `daily` | sim, via `tap` | não | PASS |
| `single` | `single` | sim, via `tap` | não | PASS |
| `daily` | `daily` | sim, via `tap` | não | PASS |

O aviso anterior sobre `config/logging.php` foi classificado como falso positivo de aplicação de patch: o arquivo já continha a integração pretendida, e nenhum ajuste artificial foi feito nele nesta retomada. A evidência deixou de ser textual e passou a ser executável, emitindo logs reais por canal temporariamente redirecionado para artefatos isolados.

### Integração real do processor

Foram adicionados testes que emitem logs reais nos oito canais acima, com arquivo temporário isolado por execução. O teste comprova que o processor injeta `correlation_id`, `request_method`, `request_path`, `ip` e `timestamp`, redige segredos em contexto aninhado e preserva valores não sensíveis. Também foi corrigida a duplicação observada entre `context` e `extra`: `StructuredLogProcessor` agora remove de `extra` as chaves de contexto propagadas antes de retornar o `LogRecord`, evitando duplicar `correlation_id` na linha final do log.

### Exception reporting

Foi criado teste dedicado para uma exceção reportada durante o ciclo de request. A validação comprova que o log contém o `correlation_id`, o mesmo ID é retornado no header, `client_secret` e `Authorization: Bearer ...` não aparecem no log, o payload HTTP em ambiente não-debug não retorna classe de exceção nem stack trace, e o status HTTP funcional permanece `500` para a falha simulada. A arquitetura real usa `bootstrap/app.php` com configuração moderna de exceptions.

### Política final de redaction R6.2

`LogRedactor` mantém allowlist explícita de nomes sensíveis, com comparação case-insensitive e exata após normalização para lowercase. São redigidos: `authorization`, `proxy-authorization`, `bearer`, `access_token`, `refresh_token`, `token`, `api_key`, `api-key`, `x-api-key`, `apikey`, `client_secret`, `client-secret`, `secret`, `password`, `passwd`, `senha`, `cookie`, `set-cookie`, `private_key`, `certificate`, `certificado`, `pix_key` e `pix key`.

Decisão crítica: não redigir todo campo que contenha a palavra `key`, para evitar mascarar identificadores não secretos. Testes garantem que `payment_key_type`, `idempotency_key_hash` e `public_key_id` permanecem intactos.

### Política segura de `X-Correlation-ID`

A política final é: UUID válido recebido é preservado exatamente; header ausente gera UUID v4; valor com CR/LF, longo demais ou arbitrário inválido é rejeitado e substituído por UUID v4; CR/LF nunca é refletido no header de resposta ou nos logs. O limite aplicado é de 64 caracteres e o formato aceito é UUID validado por `Str::isUuid()`.

### Arquivos alterados nesta retomada

- `core/app/Http/Middleware/CorrelationIdMiddleware.php`;
- `core/app/Logging/StructuredLogProcessor.php`;
- `core/app/Support/Observability/LogRedactor.php`;
- `core/tests/Feature/R62ObservabilityFoundationTest.php`;
- `docs/audit/R6-observability-sre-dr-plan.md`.

`core/config/logging.php` foi inspecionado e testado, mas não foi alterado.

### Testes de integração reais adicionados

`core/tests/Feature/R62ObservabilityFoundationTest.php` agora cobre matriz de canais, presença única do `StructuredLogProcessor`, emissão real em `payments`, `webhooks`, `gateway`, `security`, `audit`, `performance`, `single` e `daily`, `correlation_id` no log, contexto HTTP, redaction de `Authorization`, Bearer token, `client_secret`, API key, cookies sensíveis e contexto aninhado, preservação de valor comum não sensível, ausência de duplicação de `correlation_id`, exception reporting com correlation id e política segura para `X-Correlation-ID` recebido.

### Validação executável

PHP utilizado exclusivamente:

```text
E:/laragon/bin/php/php-8.3.30-Win32-vs16-x64/php.exe
```

Teste direcionado:

```text
E:/laragon/bin/php/php-8.3.30-Win32-vs16-x64/php.exe artisan test tests/Feature/R62ObservabilityFoundationTest.php
23 passed (211 assertions)
```

PHPStan: não executado porque não existe `phpstan.neon*` em `core`.

### Riscos restantes

- A propagação explícita de correlation id para todos os jobs, listeners e eventos financeiros ainda requer incremento dedicado.
- O formato global dos logs ainda usa os formatters existentes; esta rodada não migrou para JSON global.
- A política de redaction cobre os principais campos atuais, mas deve evoluir com payloads reais de PSPs e integrações futuras.
- Métricas, tracing, dashboards, alertas, deep health, backup e DR continuam fora do escopo deste incremento.

### Decisão formal

R6.2 — CONCLUÍDA

Justificativa: o processor está comprovadamente ativo em logs reais dos canais críticos, redaction foi testada contra logs emitidos, exception reporting preserva `correlation_id`, e o `X-Correlation-ID` recebido está protegido contra injeção e cardinalidade arbitrária.


## R6.3 — Propagação de contexto operacional em filas, jobs, eventos e listeners financeiros críticos

### Escopo executado
Este incremento implementou somente a propagação mínima de contexto operacional para o fluxo assíncrono crítico de webhooks financeiros. Não foram implementados métricas, Prometheus, OpenTelemetry, tracing externo, dashboards, alertas, deep health, backup, restore, DR, deploy, go-live, Release Candidate ou processamento financeiro real.

### Inventário assíncrono validado antes da implementação
- Jobs existentes identificados: `ProcessGatewayWebhookJob`, `ProcessWebhookJob`, `ReplayWebhookJob`, `WebhookProcessingJob`, `RetryWebhookDeliveryJob`, `ProcessWithdrawalJob`, `FinancialExportJob`, `Treasury\ReleaseRollingReserveJob`, jobs de disputes e jobs do módulo Connect.
- Jobs financeiros críticos priorizados nesta rodada: `ProcessGatewayWebhookJob`, por estar no pipeline de webhook gateway → charge/status → DLQ.
- Jobs de webhook: `ProcessGatewayWebhookJob`, `ProcessWebhookJob`, `ReplayWebhookJob`, `WebhookProcessingJob`, `RetryWebhookDeliveryJob`.
- Jobs de pagamento/refund/settlement/reconciliação: não há job dedicado de refund/settlement/reconciliation equivalente no inventário atual; há comandos de reconciliação e serviços de settlement. Lacuna mantida para incremento posterior.
- Jobs de notificação: listeners e delivery de webhooks outbound (`RetryWebhookDeliveryJob`, listeners de subscription/charge/webhooks) existem, mas não foram alterados nesta rodada.
- DLQ/reprocessamento: `WebhookDeadLetter`, `WebhookDlq`, ações admin de reprocessamento e `ReplayWebhookJob` existem; a cobertura R6.3 focou o job gateway inbound e payload seguro.
- Middleware de fila existente: não havia middleware de queue específico para contexto operacional antes da R6.3.
- Traits/contratos: não havia trait reutilizável de contexto operacional em jobs; foi criado `CarriesOperationalContext`.
- Driver de fila: `config/queue.php` usa default `database`, com conexões `sync`, `database`, `redis`, `sqs`, `beanstalkd`; Horizon possui filas críticas documentadas anteriormente.
- Serialização atual: Laravel serializa o job no payload de queue. O teste R6.3 inspeciona o payload Laravel efetivo produzido por `createPayload`.
- Retries/backoff/timeout: `ProcessGatewayWebhookJob` possui `tries=3` e `backoff=[10,30,60]`; `ProcessWithdrawalJob` possui `tries=3` e `timeout=120`; outros jobs variam e ficam para auditoria posterior.
- Logs em jobs: `ProcessGatewayWebhookJob` já emitia logs de skip/failure e `GatewayLog`; R6.3 garante que o processor receba contexto restaurado para logs emitidos durante a execução do worker.
- Contexto pré-existente: request HTTP já possuía `correlation_id` por R6.2; não havia continuidade centralizada para queue/worker/job.

### Arquitetura adotada
- `App\Support\Observability\CarriesOperationalContext`: trait pequeno e serializável para capturar `correlation_id`, `tenant_id`, `merchant_id`, `user_id`, `api_key_id`, `payment_id`, `gateway`, `webhook_event_id` e `job_id`, omitindo valores inexistentes.
- `App\Support\Observability\QueueOperationalContext`: helper central para restaurar contexto no worker e limpar contexto após processamento.
- `App\Providers\AppServiceProvider`: registra hooks `Queue::before`, `Queue::after` e `Queue::exceptionOccurred` para restaurar/limpar contexto em workers Laravel e registrar falhas com contexto.
- `App\Jobs\ProcessGatewayWebhookJob`: passou a carregar o contexto operacional e a sanitizar headers serializados.

### Política de correlation_id e job_id
- Job despachado dentro de request herda `Context::get('correlation_id')` quando disponível e válido; sem request/contexto, gera UUID seguro.
- Worker restaura o contexto antes de executar o job; logs emitidos durante o job recebem o mesmo contexto via `StructuredLogProcessor`.
- Eventos/listeners que usarem o trait podem herdar o contexto ativo do job.
- Em retry, `correlation_id` e `job_id` permanecem estáveis porque nascem no objeto serializado do job.
- Após job processado ou com exceção, o contexto é limpo para evitar contaminação do próximo job no mesmo worker.
- O `job_id` operacional nasce no momento de construção/captura do job como UUID próprio do payload OriginPay; em redispatch manual ou novo job, nasce novo `job_id`.
- O `job_id` não é `correlation_id`, não é `payment_id` e não é `webhook_event_id`.

### Segurança de serialização
O payload Laravel efetivo foi testado para não conter headers/segredos removidos de `ProcessGatewayWebhookJob`: `Authorization`, bearer token, API key bruta (`X-Api-Key`/`api-key`), `client_secret`, cookies e headers sensíveis bloqueados.

### Testes TDD criados
Arquivo: `core/tests/Feature/R63QueueOperationalContextTest.php`.

Cobertura executável: contexto de request no job, geração sem request, restore no worker, logs com `correlation_id`/`job_id`, retry estável, falha com contexto, propagação de `merchant_id`, `payment_id`, `gateway`, `webhook_event_id`, inspeção do payload Laravel contra segredos, limpeza pós-job, isolamento entre jobs, herança por evento/listener compatível e `job_id` distinto entre jobs.

### Validação executada
PHP utilizado exclusivamente: `E:/laragon/bin/php/php-8.3.30-Win32-vs16-x64/php.exe`.

- Direcionados R6.3 + R6.2: `30 passed (235 assertions)`.
- Regressão completa: `449 passed (1689 assertions)`.
- Pint nos arquivos alterados: `PASS 5 files` após correção automática de estilo.
- PHPStan: não executado porque não há `phpstan.neon*` no projeto.

### Lacunas documentais e técnicas restantes
- A documentação canônica não define ainda uma matriz final de todos os jobs financeiros que devem carregar contexto; esta rodada priorizou o fluxo gateway webhook inbound.
- Eventos/listeners financeiros existentes ainda não possuem padronização universal de contexto; foi entregue infraestrutura reutilizável e teste de herança, não migração ampla.
- Jobs de settlement/refund/reconciliation dedicados não foram encontrados como jobs Laravel equivalentes; há comandos/serviços que devem ser avaliados em rodada posterior.
- `RetryWebhookDeliveryJob`, `ProcessWithdrawalJob`, jobs Connect e listeners de subscription/charge não foram alterados para evitar expansão de escopo sem requisito específico.
- Alguns fluxos legados ainda persistem payloads de webhook/DLQ; redaction/logging existe, mas política de armazenamento de payload sensível deve ser revisada em incremento de segurança separado.

### Decisão formal
R6.3 — CONCLUÍDA para o incremento pequeno planejado: continuidade verificável de contexto operacional no fluxo crítico `HTTP request → dispatch → payload Laravel → queue/worker → job → logs`, com infraestrutura reutilizável para eventos/listeners e limpeza pós-job.
