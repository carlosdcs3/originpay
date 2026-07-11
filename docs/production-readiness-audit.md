# Production Readiness Audit - Final Enterprise Certification

Data da auditoria: 2026-06-27  
Escopo: auditoria estrutural final do backend/admin/API/gateway/wallet/financeiro, sem implementar funcionalidades e sem refatorar código.  
Projeto auditado: OriginPay/DigiKash Enterprise, Laravel 11.

## 1. Executive Summary

Status final: **NAO APTO para Go Live Enterprise**.

O sistema tem blocos Enterprise relevantes ja presentes: admin protegido por middleware, Horizon configurado, logs de API/gateway, modelos de DLQ/webhook, ledger, servicos financeiros, reconciliacao e circuit breaker. Porem a auditoria encontrou riscos estruturais reais que impedem certificacao de producao:

- Saque automatico pode ser marcado como concluido com `EFI_` simulado, sem chamada real ao PSP.
- Existem duas migrations criando a mesma tabela `webhook_events` com schemas incompatíveis.
- O model `WebhookEvent` esta alinhado com uma migration, enquanto `ProcessWebhookJob` e `ReplayWebhookJob` esperam outro schema.
- Webhooks de gateway sao enfileirados antes de validacao sincronica de assinatura/autenticidade.
- Idempotencia financeira e opcional e nao atomica.
- `WalletBalanceService` usa colunas que nao existem no schema (`balance`, `blocked_balance`), enquanto a tabela possui `available`, `pending`, `blocked`.
- Scheduler nao executa reconciliacao financeira, webhooks, rolling reserve, jobs criticos ou auditoria de ledger.
- Logs persistem payloads sensiveis em API, gateway, auditoria admin e DLQ.
- Jobs e comandos criticos carregam datasets inteiros ou nao possuem timeout/retry operacional.

Conclusao: a aplicacao deve passar por uma rodada de hardening antes de producao. A prioridade nao deve ser nova tela; deve ser corrigir integridade financeira, webhooks, idempotencia, scheduler e protecao de dados.

## 2. Scores de Readiness

| Pilar | Score | Justificativa |
|---|---:|---|
| Architecture | 5.5/10 | Existem servicos e DTOs, mas ha duplicidade de pipelines, migrations conflitantes e command/job lifecycle incompleto. |
| Security | 5.0/10 | CSRF/admin melhorou, mas webhook sem validacao sincronica, API sem HMAC real, payloads sensiveis em logs e SVG publico bloqueiam Go Live. |
| Scalability | 4.5/10 | Muitos comandos/jobs usam `all()`/`get()` em massa; scheduler nao orquestra rotinas criticas. |
| Performance | 5.0/10 | Rotas carregam, mas export, auditorias e reconciliacoes nao estao preparados para volume Enterprise. |
| Resilience | 4.5/10 | Circuit breaker existe, mas ha pipeline dummy/sem binding e DLQ/replay de webhook inconsistente. |
| Observability | 6.0/10 | Ha ApiLog, GatewayLog, metricas e alerts, mas logs vazam payload e rotinas de monitoramento nao estao agendadas. |
| Maintainability | 5.5/10 | Estrutura modular existe, mas duplicidades de migrations, commands, eventos e schemas reduzem confiabilidade. |
| Overall Readiness | 5.1/10 | Nao certificavel ate resolver os bloqueadores criticos. |

## 3. Validações Executadas

| Comando | Resultado |
|---|---|
| `php artisan route:list` | OK, 576 rotas carregadas, exit code 0. |
| `php artisan schedule:list` | OK, apenas `inspire` e limpeza de temp folder aparecem. Nenhuma rotina financeira critica. |
| `php artisan event:list` | OK, mas exibiu listeners duplicados para `ChargePaidEvent`. |
| `php artisan queue:failed` | OK, sem failed jobs no ambiente atual. |
| `php artisan list ledger` | OK, mostra `ledger:verify-integrity`, mas ha duas classes com a mesma signature no codigo. |
| `php artisan list reconcile` | OK, comandos existem, mas nao estao agendados. |

Observacao: comandos destrutivos ou que alteram cache/config como `config:cache`, `route:cache` e migrations nao foram executados por esta auditoria ser read-only.

## 4. Riscos Criticos

| ID | Severidade | Arquivo/linha | Motivo | Impacto | Risco | Recomendacao |
|---|---|---|---|---|---|---|
| CR-01 | Critico | `core/app/Jobs/ProcessWithdrawalJob.php:39-44` e `core/app/Services/Payment/WithdrawalService.php:142-144` | O job de saque simula envio para EFI (`EFI_` + timestamp) e chama `completeWithdrawal`. | Saque pode ser marcado como concluido sem transferencia real. | Perda financeira, ledger incorreto, exposicao regulatoria. | Bloquear automatic withdraw em producao ate integrar PSP real, confirmar retorno assinado e separar estado `PROCESSING` de `COMPLETED`. |
| CR-02 | Critico | `core/database/migrations/2026_06_24_000004_create_webhook_events_table.php:11-28` e `core/database/migrations/2026_06_27_000001_create_webhook_events_table.php:11-30` | Duas migrations criam `webhook_events` com schemas diferentes. | Fresh migrate quebra ou ambiente fica com schema diferente do esperado pelo codigo. | Deploy novo nao reprodutivel e webhooks quebrados. | Consolidar migration/schema canonico e criar migration corretiva, preservando dados existentes. |
| CR-03 | Critico | `core/app/Models/WebhookEvent.php:13-34`, `core/app/Jobs/ProcessWebhookJob.php:47-68`, `core/app/Jobs/ReplayWebhookJob.php:37-51` | Model usa `gateway`, `provider_reference`, `raw_payload`; jobs esperam `provider`, `payload`, `headers`, `external_reference`, `metadata`. | Processamento/replay de webhook pode falhar em runtime por colunas/propriedades inexistentes. | Pagamentos nao reconciliados, DLQ inutilizavel. | Definir um unico contrato de WebhookEvent e alinhar model, migrations, jobs e admin. |
| CR-04 | Critico | `core/app/Http/Controllers/Webhook/GatewayWebhookController.php:33-39` | Controller comenta validacao de assinatura como opcional e enfileira `$request->all()` antes de validar. | Webhook falso pode ser aceito para processamento. | Fraude de pagamento, processamento indevido, ataque de fila. | Validar assinatura, timestamp e provider antes de dispatch; rejeitar 4xx sem enfileirar. |
| CR-05 | Critico | `core/app/Http/Middleware/CheckIdempotency.php:15-18`, `core/routes/api.php:31-42`, `core/app/Http/Middleware/CheckIdempotency.php:29-31,70-87` | Idempotencia so roda se header existir e cria registro apos leitura nao atomica. | POST financeiro sem header passa; concorrencia pode gerar duplicidade ou 500 por unique key. | Cobrança/refund/payout duplicado. | Tornar idempotency obrigatoria nas rotas financeiras e usar insert atomico/lock com tratamento de unique violation. |
| CR-06 | Critico | `core/app/Services/Financial/WalletBalanceService.php:23-35,74-82,129-171`, `core/app/Models/WalletBalance.php:12-18`, `core/database/migrations/2026_06_27_181001_create_wallet_balances_table.php:20-25` | Service usa `balance` e `blocked_balance`, mas schema possui `available`, `pending`, `blocked`. | Operacoes por gateway falham ou gravam atributos nao persistidos. | Saldos incorretos por gateway e reconciliacao invalida. | Alinhar service ao schema canonico e cobrir com teste de credit/debit/block/release. |

## 5. Achados de Alta Severidade

| ID | Area | Arquivo/linha | Motivo | Impacto | Risco | Recomendacao |
|---|---|---|---|---|---|---|
| HI-01 | Financeiro | `core/app/Services/ChargeService.php:47-55`, `core/database/migrations/2026_06_25_220600_add_production_fields_to_gateway_tables.php:14-17` | Idempotency de charge consulta antes de salvar e a unique key e global, nao por usuario. | Concorrencia duplica tentativa ou causa erro; usuarios diferentes podem colidir por mesma chave. | Duplicidade/indisponibilidade em criacao de charges. | Criar unique composto por `user_id + idempotency_key` e transacao atomica. |
| HI-02 | Gateway/Financeiro | `core/app/Services/ChargeService.php:66-87,198-205` | Charge e persistida antes do PSP e deletada em falhas; nao ha unidade transacional com side effect externo. | Crash apos PSP aceitar e antes do save final deixa charge orfa/PENDING ou apagada localmente. | Divergencia PSP x banco. | Usar estados explicitos (`CREATING_EXTERNAL`, `WAITING_PAYMENT`), outbox e reconciliacao obrigatoria. |
| HI-03 | Wallet | `core/app/Services/Payment/WithdrawalService.php:216-241`, `core/app/Services/Handlers/WithdrawHandler.php:45-71` | `completeWithdrawal` reduz wallet e depois `WithdrawHandler` transfere fee/net novamente via ledger. | Possivel dupla movimentacao contabil ou divergencia entre sub-saldos e ledger. | Saldo incorreto em saques. | Revisar contrato: reservation, settlement e ledger devem acontecer uma vez, com invariantes testadas. |
| HI-04 | Scheduler | `core/bootstrap/app.php:109-120`, `core/routes/console.php:6-8` | Scheduler so executa `inspire` e limpeza temporaria. | Reconciliacao, rolling reserve, auditoria de ledger e webhooks nao rodam automaticamente. | Saldos presos, divergencias nao detectadas, DLQ acumulada. | Agendar `gateway:reconcile`, `reconcile:*`, release de rolling reserve, prune/logs e auditorias com locks. |
| HI-05 | Webhook/DLQ | `core/app/Jobs/ProcessWebhookJob.php:111-117`, `core/app/Jobs/ReplayWebhookJob.php:31-45` | DLQ salva payload/header mascarado e replay usa esse payload mascarado. | Replay pode nao parsear ou falhar assinatura. | Recuperacao de incidentes ineficaz. | Guardar payload bruto criptografado e payload mascarado separado para UI. |
| HI-06 | Dados sensiveis | `core/app/Jobs/ProcessGatewayWebhookJob.php:75-82,95-110` | GatewayLog e WebhookDeadLetter gravam payload bruto de webhook. | PII, Pix, documentos e segredos podem ficar persistidos em logs. | LGPD/incidente de seguranca. | Sanitizar/criptografar campos sensiveis; limitar retencao e acesso. |
| HI-07 | Dados sensiveis | `core/app/Http/Middleware/LogApiRequests.php:21-25,33-45,53-65` | Request e parcialmente sanitizado; response e salvo bruto. | Respostas podem conter QR code, documentos, tokens, dados de cliente. | Vazamento em ApiLog. | Sanitizar request e response por allowlist/mask centralizado. |
| HI-08 | Dados sensiveis | `core/app/Http/Middleware/AdminAuditMiddleware.php:35-58` | Admin audit grava URL completa e payload com sanitizer restrito. | Credenciais, documentos, Pix key e certificados podem aparecer em logs. | Vazamento interno e compliance. | Usar sanitizer recursivo central, remover query string sensivel, classificar eventos. |
| HI-09 | Dados sensiveis | `core/app/Gateway/Http/GatewayHttpClient.php:152-184` | Loga URL/resposta; lista sensivel nao cobre CPF/CNPJ/document/pix e compara `Authorization` de forma inconsistente. | Credenciais e dados financeiros em log gateway. | Exposicao de dados de PSP/clientes. | Normalizar chaves para lowercase e mascarar lista ampliada. |
| HI-10 | API | `core/app/Http/Middleware/MerchantApiAuth.php:15-19,21-65` | Docstring exige `X-Signature`, mas middleware valida so API key/merchant key. | Payload pode ser alterado/replayado se chave vazar. | Fraude/replay em API legada. | Implementar HMAC com timestamp/nonce ou remover promessa e bloquear endpoints sensiveis. |
| HI-11 | Eventos | `core/app/Providers/AppServiceProvider.php:91-99`, `php artisan event:list` | `ChargePaidEvent` aparece com `DispatchWebhooksListener` e `SendChargePaidEmailListener` duplicados no event list. | Webhooks/e-mails podem ser enviados duas vezes. | Notificacao duplicada e duplicidade de delivery externo. | Centralizar registro de eventos e validar provider/event discovery. |
| HI-12 | Jobs | `core/app/Jobs/NotifyUsers.php:36-67` | Job carrega todos usuarios em memoria e envia notificacao em lote sem timeout. | Explode memoria/fila em bases grandes. | Degradacao e jobs travados. | Chunk por usuario, timeout, retry/backoff e rate limit de mail/push. |
| HI-13 | Jobs | `core/app/Jobs/FinancialExportJob.php:14-18,37-66` | Job nao define `tries`/`timeout`; export longa abre arquivo local e depende de chunk. | Export pode travar worker ou ficar sem controle operacional. | Indisponibilidade de fila. | Definir timeout/retry, streaming para storage e progresso auditavel. |
| HI-14 | Jobs | `core/app/Jobs/Treasury/ReleaseRollingReserveJob.php:21-27` | Busca todas reservas vencidas com `get()` e job nao possui timeout/tries. | Grande volume de reservas pode travar worker. | Rolling reserve preso ou processamento parcial. | Processar em chunks com lock/idempotency e agendamento. |
| HI-15 | Comandos | `core/app/Console/Commands/VerifyLedgerIntegrityCommand.php:31-41` | Verificacao de ledger carrega todas transacoes em memoria. | Nao escala para ledger Enterprise. | Auditoria critica inutilizavel em producao. | Chunk ordenado por wallet/id preservando hash anterior. |
| HI-16 | Comandos | `core/app/Console/Commands/AuditGatewayBalances.php:34-68` | Usa `Wallet::all()`, consulta balances por wallet e gateway por balance. | N+1 e consumo alto em auditoria. | Auditoria lenta/indisponivel. | Chunk/eager load/agregacoes SQL. |
| HI-17 | Comandos | `core/app/Console/Commands/Reconciliation/ReconcileLedgerCommand.php:21-35` | Proprio codigo declara pseudo-code e assume schema generico. | Reconciliacao de ledger pode produzir falso positivo/negativo. | Confiança baixa na certificacao financeira. | Substituir por reconciliacao baseada no ledger canonico real. |
| HI-18 | Gateway | `core/app/Gateway/CircuitBreaker/DummyCircuitBreaker.php:7-19`, `core/app/Gateway/Pipeline/GatewayPipelineFactory.php:21-43`, `core/bootstrap/providers.php:3-11` | Pipeline novo usa `GatewayCircuitBreakerInterface`; so ha dummy e provider de integracao nem esta registrado. | Circuit breaker de pipeline pode sempre permitir ou nem resolver no container. | Falha em isolamento de PSP. | Registrar bindings reais e remover dummy de producao. |
| HI-19 | Upload/Security | `core/app/Traits/FileManageTrait.php:17-21,27-35,96-107` e requests/controllers com `mimes:*svg` | SVG e permitido e gravado em disco publico. | SVG com script pode gerar XSS quando servido. | Comprometimento de admin/user. | Bloquear SVG ou sanitizar e servir com headers seguros. |
| HI-20 | Concurrency | `core/app/Models/WalletTransaction.php:51-71` | Hash chain pega ultima transacao sem lock local da tabela. | Criacoes concorrentes podem usar o mesmo `previous_integrity_hash`. | Ledger auditavel quebrado. | Gerar ledger apenas sob lock da wallet e/ou unique constraint sequencial por wallet. |

## 6. Achados de Media Severidade

| ID | Area | Arquivo/linha | Motivo | Impacto | Risco | Recomendacao |
|---|---|---|---|---|---|---|
| MD-01 | Rate Limit | `core/app/Http/Middleware/AdvancedRateLimiter.php:52-73` | `increment()` seguido de `put(..., 1, ttl)` pode sobrescrever contador inicial. | Contagem de quota diaria/mensal pode ficar imprecisa sob concorrencia. | Quota burlavel ou injusta. | Usar increment atomico com TTL sem sobrescrever valor. |
| MD-02 | Webhook | `core/app/Jobs/ProcessGatewayWebhookJob.php:21-22` | Job possui tries/backoff, mas nao define timeout. | Worker pode ficar preso em provider/adapter lento. | Saturacao da fila de webhooks. | Definir `$timeout`, `retryUntil` e tags por provider. |
| MD-03 | Webhook | `core/app/Jobs/ProcessWebhookJob.php:26-33` | Job moderno tem tries/backoff mas sem timeout. | Mesmo risco de worker preso. | Atraso no processamento de pagamento. | Definir timeout por policy (`timeoutSeconds`) ou job. |
| MD-04 | Commands | `core/app/Console/Commands/VerifyLedgerIntegrityCommand.php:15` e `core/app/Console/Commands/VerifyLedgerIntegrity.php:17` | Duas classes declaram `ledger:verify-integrity`. | Uma implementacao pode ocultar a outra. | Comando executado nao ser o esperado. | Manter uma signature e remover/renomear a outra. |
| MD-05 | Scheduler | `core/app/Console/Commands/Reconciliation/ReconcileWebhooksCommand.php:21-39` | Comando busca todos eventos recentes e usa campos do schema antigo (`provider`). | Pode falhar no schema novo ou nao detectar anomalias. | Monitoramento falso. | Alinhar com schema canonico de webhook. |
| MD-06 | Gateway | `core/app/Providers/GatewayIntegrationServiceProvider.php:9-18`, `core/bootstrap/providers.php:3-11` | Service provider de gateway integration nao consta em `bootstrap/providers.php`. | Registry de auth EFI pode nao existir no runtime. | Pipeline novo sem autenticacao. | Registrar provider ou mover bindings para provider carregado. |
| MD-07 | Financeiro | `core/app/Services/ChargeService.php:257-267` | Fee de charge nao PIX permite `debitAvailable(..., true)`, aceitando saldo negativo. | Saldo disponivel negativo pode ser regra, mas nao ha policy formal. | Inconsistencia financeira/comercial. | Documentar policy e emitir anomalia/limite quando negativo for permitido. |
| MD-08 | Reconciliacao | `core/app/Console/Commands/GatewayReconcileCommand.php:27-32` | Busca todas charges elegiveis com `get()`. | Pode escalar mal com grande volume de pendencias. | Comando lento e PSP rate limit. | Chunk por gateway e paginação com limites. |
| MD-09 | Frontend/Admin | `core/app/Http/Middleware/AdminAuditMiddleware.php:48-50` | Comentario indica que log deveria ir para tabela `audit_logs`, mas vai para canal de log. | Busca/auditoria forense limitada. | Evidencia operacional fraca. | Persistir eventos criticos em tabela append-only com retencao. |
| MD-10 | API Logs | `core/app/Http/Middleware/LogApiRequests.php:32-48` | Falha de ApiLog e silenciada totalmente. | Perda de observabilidade sem alerta. | Incidentes sem trilha. | Contador/alerta quando log falhar, sem quebrar API. |

## 7. Performance

Principais gargalos:

- `NotifyUsers` usa `get()` e envia para todos usuarios no mesmo job.
- `VerifyLedgerIntegrityCommand` carrega todo ledger em memoria.
- `AuditGatewayBalances` e comandos de reconciliacao usam `all()`/`get()` e N+1.
- `GatewayReconcileCommand` carrega todas charges pendentes do periodo.
- Scheduler nao particiona rotinas pesadas por janela, lock ou tenant.

Prioridade: alta. Antes do Go Live, comandos financeiros devem rodar com chunking, locks, timeout e metricas.

## 8. Banco

Achados centrais:

- Migrations duplicadas para `webhook_events`.
- Schema de `WalletBalance` incompatível com service.
- Unique de `charges.idempotency_key` global, enquanto o service consulta por usuario.
- Wallet usa `float` em `core/database/migrations/2024_11_12_040813_create_wallets_table.php:19`; para dinheiro, isso deve migrar para `decimal` com estrategia segura.
- Comandos assumem schemas diferentes (`transactions`, `ledger_entries`, `wallet_transactions`, `webhook_events`) sem contrato unico.

Prioridade: critica para migrations/webhook/wallet balance; alta para tipos monetarios.

## 9. Segurança

Bloqueadores:

- Webhook sem validacao antes da fila.
- API legada documenta HMAC mas nao valida assinatura.
- Logs persistem payloads sensiveis.
- SVG publico permitido.
- DLQ mistura necessidade de payload bruto para replay com mascaramento de visualizacao.

Recomendacao geral: criar sanitizer central, criptografia para payload bruto sensivel, allowlist de logs por dominio e validacao forte de webhooks/API.

## 10. Concorrência

Achados:

- Idempotency middleware e charge idempotency fazem read-before-write sem atomicidade.
- Hash chain de `WalletTransaction` consulta ultima transacao sem lock proprio.
- `WithdrawalService` despacha job dentro de transacao de aprovacao.
- `releaseReservation` nao valida saldo reservado antes de subtrair.

Recomendacao: padronizar locks por wallet/charge/webhook, idempotency atomica e dispatch after commit.

## 11. Cache

Achados:

- Rate limiter/quota tem incremento e TTL nao atomicos.
- Circuit breaker real usa Redis, mas pipeline paralelo usa interface/dummy.
- Scheduler nao limpa/renova metricas financeiras alem de temp folder.

Recomendacao: revisar contratos de Redis/Cache para rate limit, circuit breaker e jobs idempotentes.

## 12. Gateway

Achados:

- Webhook de gateway enfileira sem assinatura sincronica.
- Duas arquiteturas coexistem: `App\Services\CircuitBreakerService` e `App\Gateway\CircuitBreaker\GatewayCircuitBreakerInterface`.
- Provider de integracao nao registrado.
- Logs de gateway podem persistir dados sensiveis.
- Reconciliacao existe, mas nao agendada e carrega dados em massa.

Prioridade: critica para webhook; alta para circuit breaker/pipeline.

## 13. Wallet

Achados:

- `WalletBalanceService` nao bate com tabela/model.
- Saque automatico simulado altera estado financeiro.
- Possivel dupla movimentacao em saque via `completeWithdrawal` + `WithdrawHandler`.
- Hash chain de wallet transaction pode quebrar sob concorrencia.
- Wallet principal usa `float` na migration historica.

Prioridade: critica.

## 14. Financeiro

Achados:

- Charge e criada antes do PSP e deletada em falha, sem outbox nem estado intermediario robusto.
- Idempotency de charge nao atomica.
- Reconciliacao financeira existe mas nao e orquestrada.
- Command `reconcile:ledger` ainda contem pseudo-code.
- Commands de auditoria nao escalam.

Prioridade: critica/alta.

## 15. APIs

Achados:

- `api.idempotency` e opcional por header, apesar de aplicado ao grupo `v1`.
- `api.log` registra response sem sanitizacao.
- API legada `merchant.auth` nao implementa assinatura HMAC prometida.
- Rate limit avancado existe como alias, mas grupo `v1` usa `throttle:api` e `throttle:payments`; revisar se `api.rate_limiter` deve ser aplicado.

Prioridade: alta.

## 16. Jobs

Jobs com risco operacional:

- `ProcessWithdrawalJob`: risco critico por PSP simulado.
- `ProcessGatewayWebhookJob`: sem timeout e loga payload bruto.
- `ProcessWebhookJob`: sem timeout e DLQ mascarada.
- `ReplayWebhookJob`: replay com payload mascarado.
- `NotifyUsers`: carrega todos usuarios.
- `FinancialExportJob`: sem tries/timeout.
- `ReleaseRollingReserveJob`: sem tries/timeout e `get()` em massa.
- `RetryWebhookDeliveryJob`: possui logica manual de retry, mas sem timeout do job Laravel.

Prioridade: critica para saque/webhook; alta para demais.

## 17. Eventos

`php artisan event:list` mostrou `ChargePaidEvent` com:

- `DispatchWebhooksListener@handle` duplicado.
- `SendChargePaidEmailListener@handle` duplicado.

Evidencia adicional: `AppServiceProvider` registra manualmente os dois listeners em `core/app/Providers/AppServiceProvider.php:91-99`. Se discovery ou cache tambem registra, pode duplicar envio.

Prioridade: alta, porque evento de pagamento pago pode disparar side effects externos duplicados.

## 18. Scheduler

`php artisan schedule:list` mostrou apenas:

- `inspire` hourly.
- Closure de limpeza de temp folder diaria em `bootstrap/app.php:110`.

Ausentes do scheduler:

- `gateway:reconcile`
- `reconcile:efi`
- `reconcile:efi-balance`
- `reconcile:efi-settlement`
- `reconcile:efi-withdraws`
- `reconcile:wallet-reserves`
- `reconcile:webhooks`
- `reconcile:transactions`
- `ledger:verify-integrity`
- release de rolling reserve
- prune/monitoramento de logs e DLQ

Prioridade: critica.

## 19. Frontend/Admin

Nao foi feita avaliacao visual profunda nesta auditoria final. Achados estruturais ligados ao admin:

- Rotas admin carregam.
- Admin audit middleware existe e esta aplicado ao grupo admin.
- Auditoria admin salva payload em log e nao em tabela append-only.
- SVG permitido em varios fluxos de upload admin/user pode virar XSS quando servido por storage publico.

Prioridade: alta para sanitizacao/logs/upload.

## 20. Go Live

Checklist de bloqueio:

| Item | Status |
|---|---|
| Rotas carregam | OK |
| Scheduler financeiro | Bloqueado |
| Webhook seguro | Bloqueado |
| Saque automatico real | Bloqueado |
| Idempotencia financeira obrigatoria/atomica | Bloqueado |
| Migrations fresh reproduziveis | Bloqueado |
| Wallet balance consistente | Bloqueado |
| Logs LGPD-safe | Bloqueado |
| Jobs com timeout/retry | Parcial |
| Reconciliacao escalavel | Parcial |
| Eventos sem duplicidade | Bloqueado |

Decisao: **nao liberar producao financeira real** ate CR-01 a CR-06 e HI-01 a HI-11 estarem resolvidos e testados.

## 21. Plano de Correção

### Fase 0 - Freeze de risco

Prioridade: imediata.

- Desativar saque automatico real enquanto `ProcessWithdrawalJob` nao chamar PSP real.
- Bloquear acceptance de webhooks sem assinatura sincronica.
- Impedir novos deploys fresh sem resolver migrations duplicadas de `webhook_events`.

### Fase 1 - Integridade financeira

Prioridade: critica.

- Corrigir idempotency obrigatoria/atomica para `/api/v1/payments`, `/api/v1/refunds`, `/api/v1/payouts`.
- Alinhar `WalletBalanceService` com `wallet_balances`.
- Revisar `WithdrawalService` e `WithdrawHandler` para garantir uma unica movimentacao contabil.
- Formalizar estados de charge e outbox/reconciliation para PSP side effects.

### Fase 2 - Webhook e Gateway

Prioridade: critica.

- Unificar schema/model/jobs de webhook.
- Separar payload bruto criptografado de payload mascarado para UI.
- Corrigir replay DLQ.
- Registrar/bindar gateway pipeline real ou remover pipeline dummy.
- Validar `ChargePaidEvent` sem listeners duplicados.

### Fase 3 - Scheduler e Operacao

Prioridade: alta.

- Agendar reconciliacoes com `withoutOverlapping`, locks e janelas.
- Agendar rolling reserve release.
- Agendar auditoria de ledger e wallet reserves.
- Adicionar timeouts, retries e tags nos jobs criticos.
- Ajustar comandos para chunking.

### Fase 4 - Segurança e Compliance

Prioridade: alta.

- Sanitizer central para logs API/Admin/Gateway/DLQ.
- HMAC/timestamp/nonce na API legada ou descontinuidade formal.
- Bloquear/sanitizar SVG em uploads publicos.
- Persistir admin audit critico em tabela append-only.

### Fase 5 - Certificacao

Prioridade: apos correcoes.

- Rodar `php artisan route:list`.
- Rodar `php artisan schedule:list` e validar rotinas financeiras.
- Rodar suite de testes de idempotencia, webhook replay, saque, charge, wallet e reconciliacao.
- Testar fresh migrate em ambiente limpo.
- Executar carga controlada para jobs e comandos.
- Revisar logs gerados para garantir ausencia de PII/segredos.

## 22. Ordem Ideal de Implementação

1. Saque automatico e wallet/ledger invariants.
2. Migrations/schema canonico de `webhook_events`.
3. Webhook validation + DLQ replay correto.
4. Idempotency financeira obrigatoria/atomica.
5. WalletBalanceService alinhado ao schema.
6. Event listener duplication.
7. Scheduler financeiro completo.
8. Sanitizacao/criptografia de logs.
9. Jobs/commands escalaveis com timeout.
10. Circuit breaker/pipeline gateway canonico.
11. Upload SVG e hardening admin/API.

## 23. Parecer Final

O painel e o backend tem base Enterprise, mas ainda nao estao em nivel de certificacao final. O maior risco nao e de UI: e de integridade operacional em dinheiro real. O foco recomendado e uma fase curta e rigorosa de hardening financeiro/gateway antes de qualquer nova tela.

Readiness final: **5.1/10 - Bloqueado para Go Live Enterprise financeiro**.
