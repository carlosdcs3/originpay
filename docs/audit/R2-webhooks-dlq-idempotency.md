# R2 — Webhooks, DLQ e Idempotência

## Diagnóstico

### Pipelines atuais mapeados

1. `Api\WebhookController` (`POST api/webhooks/{gateway}`)
   - Valida via `GatewayManager::webhookValidator()`.
   - Normaliza payload.
   - Persiste em `webhook_events` com `provider + event_id`.
   - Enfileira `ProcessWebhookJob`.

2. `Webhook\GatewayWebhookController` (`POST api/webhooks/gateway/{provider}`)
   - Valida assinatura/timestamp em `GatewayWebhookValidationService`.
   - Antes do R2, enfileirava direto `ProcessGatewayWebhookJob` sem persistência prévia e salvava headers sanitizados.
   - Agora persiste primeiro em `webhook_events`, bloqueia replay/duplicidade por `provider + event_id` e só depois enfileira.

3. `Api\V1\Webhooks\EfiWebhookController` (`POST api/webhooks/efi`)
   - Processa diretamente `pix[]`, consulta status EFI e altera cobrança.
   - Em falhas usa `webhook_dlqs` (DLQ paralela), não a DLQ de gateway.
   - Risco residual: ainda precisa ser migrado para o pipeline canônico sem quebrar endpoint.

4. `ProcessGatewayWebhookJob`
   - Recria `Request`, normaliza no adapter e altera estado financeiro via `ChargeService`.
   - Antes do R2 criava DLQ sem headers/assinatura/timestamp.
   - Agora atualiza `webhook_events`, pula evento já processado e grava DLQ canônica preservando payload, headers, assinatura e timestamp.

5. `WebhookDispatcher`
   - Pipeline outbound para notificar merchants.
   - Já possui `idempotency_key` opcional e assinatura HMAC com timestamp.
   - Fora do escopo principal de inbound financeiro, mas deve manter `dispatchOnce()` para eventos financeiros.

### Duplicidades encontradas

- Duas tabelas/modelos de DLQ: `webhook_dead_letters` e `webhook_dlqs`.
- Múltiplas migrations de `webhook_events` com formatos divergentes (`provider/event_id/payload` e `gateway/provider_reference/payload_hash`).
- Dois pipelines inbound financeiros paralelos: genérico por gateway e EFI v1 direto.
- Reprocessamento anterior recriava request sem preservar headers/assinatura/timestamp, abrindo espaço para inconsistência de auditoria.

### Source of truth definido

- Inbound financeiro: `webhook_events` é a trilha primária de idempotência/auditoria.
- DLQ canônica para gateway inbound: `webhook_dead_letters`.
- Chave de idempotência mínima compatível: `provider + event_id` (com fallback `payload_<sha256(raw)>` quando provider não envia id explícito).
- `correlation_id`: header `X-Correlation-ID`/`X-Request-ID` ou UUID gerado no controller.

### Migração compatível proposta

1. Manter endpoints existentes.
2. Manter `webhook_dlqs` apenas como legado temporário para EFI v1/admin antigo.
3. Migrar `EfiWebhookController` para usar o mesmo registro em `webhook_events` e DLQ `webhook_dead_letters` em próxima etapa controlada.
4. Após validação operacional, criar backfill de `webhook_dlqs` para `webhook_dead_letters` e alterar telas/admin para ler DLQ consolidada.
5. Só depois remover rotas/consultas legadas.

## Correção mínima implementada

- `GatewayWebhookController` agora:
  - valida antes de persistir;
  - persiste evento em `webhook_events` antes da fila;
  - preserva payload bruto e headers originais;
  - gera/extrai `event_id` e `correlation_id`;
  - bloqueia replay/duplicidade antes de enfileirar;
  - registra métrica/log de duplicidade bloqueada.

- `ProcessGatewayWebhookJob` agora:
  - aceita `webhook_event_id` mantendo compatibilidade com chamadas antigas;
  - não reprocessa evento já marcado como `PROCESSED`;
  - atualiza status `PROCESSING`, `PROCESSED` ou `DEAD_LETTER` em `webhook_events`;
  - grava/atualiza `webhook_dead_letters` com payload, headers, provider, assinatura e timestamp originais;
  - marca dead letter como `reprocessed` quando reprocessamento conclui.

- Reprocessamento admin agora:
  - reenfileira o payload original da DLQ;
  - não recria request sem headers;
  - preserva headers/assinatura/timestamp armazenados.

## Arquivos modificados

- `app/Http/Controllers/Webhook/GatewayWebhookController.php`
- `app/Jobs/ProcessGatewayWebhookJob.php`
- `app/Models/WebhookDeadLetter.php`
- `database/migrations/2026_07_10_000001_harden_webhook_dead_letters_and_events.php`
- `tests/Feature/GatewayWebhookIdempotencyTest.php`
- `docs/audit/R2-webhooks-dlq-idempotency.md`

## Testes obrigatórios listados

1. Webhook com assinatura inválida retorna 401/400 e não cria/atualiza `webhook_events`, `webhook_dead_letters` nem cobrança.
2. Webhook válido cria `webhook_events` antes de enfileirar job.
3. Mesmo `provider + event_id` enviado duas vezes retorna 200 idempotente e enfileira apenas uma vez.
4. Job com evento já `PROCESSED` não chama `ChargeService` novamente.
5. Falha final do job cria/atualiza `webhook_dead_letters` com payload, headers, assinatura, timestamp e `webhook_event_id`.
6. Reprocessamento usa payload/headers originais da DLQ e não revalida com headers ausentes.
7. Evento inválido nunca altera estado financeiro.
8. Evento duplicado nunca credita saldo duas vezes.
9. EFI v1 deve ser coberto por teste de migração para pipeline canônico antes da consolidação total.

## Testes criados/ajustados

- `tests/Feature/GatewayWebhookIdempotencyTest.php`
  - `test_valid_gateway_webhook_is_persisted_before_queue_and_duplicate_is_blocked`
  - `test_invalid_signature_does_not_persist_or_enqueue`

## Testes executados

- Tentativa: `php -l app/Http/Controllers/Webhook/GatewayWebhookController.php && php -l app/Jobs/ProcessGatewayWebhookJob.php && php -l database/migrations/2026_07_10_000001_harden_webhook_dead_letters_and_events.php && php -l app/Models/WebhookDeadLetter.php`
- Resultado: não executado; ambiente atual não possui `php` no PATH (`php: command not found`).
- Pest/PHPUnit não executado pelo mesmo bloqueio de ambiente.

## Riscos restantes

- `Api\V1\Webhooks\EfiWebhookController` ainda processa diretamente e usa `webhook_dlqs`; precisa de migração controlada para o pipeline canônico.
- Existem migrations históricas divergentes para `webhook_events`; a migration R2 adiciona colunas de forma compatível, mas não resolve todos os formatos legados.
- A unicidade real depende dos índices existentes no banco em produção; confirmar índice único `provider,event_id` ou equivalente antes do go-live.
- `WebhookDispatcher` outbound ainda permite `dispatch()` sem idempotency key; eventos financeiros devem usar `dispatchOnce()`.

## Validação de segurança

- Spoofing: assinatura continua obrigatória via `GatewayWebhookValidationService` antes de qualquer persistência/processamento financeiro.
- Replay: duplicidade por `provider + event_id` é bloqueada antes da fila.
- Anti-replay temporal: timestamp segue validado quando o gateway está configurado com `webhook_requires_timestamp`.
- Auditoria: payload bruto, headers originais, assinatura e timestamp passam a ser preservados na DLQ canônica.

## Validação financeira

- Webhook inválido não chega ao job financeiro.
- Webhook duplicado não é reenfileirado no pipeline gateway.
- Job não reexecuta evento já processado.
- DLQ não altera estado financeiro; apenas armazena material original para reprocessamento auditável.

## Documentação atualizada

- Este arquivo registra diagnóstico, decisão de source of truth, plano de migração compatível, correção mínima, testes e riscos residuais.
