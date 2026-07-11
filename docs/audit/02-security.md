<!-- ORIGINPAY AUDIT | generated 2026-07-10T03:24:28 | source: static code inspection | scope: documentation only -->

# 02 — Auditoria de Segurança

## Método
Inspeção estática de middlewares, rotas, models, serviços financeiros e controllers de webhook. Classificação: Crítico, Alto, Médio, Baixo. Sem exploração ativa.

## Achados críticos
### SEC-01 — Segredos/API keys legados possivelmente armazenados em claro — Crítico
Evidências:
- `app/Models/Merchant.php` contém campos `api_key`, `api_secret`, `merchant_key`, `test_api_key`, `test_api_secret`, `test_merchant_key`; comentário de `$hidden` está desativado.
- `MerchantApiAuth` busca diretamente `where('api_key', $apiKey)` e `where('merchant_key', $merchantKey)`.
- Camada nova `AuthenticateApiKey` usa hash SHA-256 em `ApiKey::where('key_hash', $hash)`, criando dois padrões.
Impacto: vazamento de banco expõe credenciais utilizáveis; inconsistência dificulta rotação e revogação.
Recomendação: migrar para credenciais hash + prefixo público, esconder atributos sensíveis, rotação obrigatória, invalidar modelo legado.

### SEC-02 — Webhooks duplicados e validação inconsistente — Crítico
Evidências:
- `Api\WebhookController` valida via `GatewayManager->webhookValidator`.
- `Webhook\GatewayWebhookController` usa `GatewayWebhookValidationService`.
- `Api\V1\Webhooks\EfiWebhookController` processa `pix` e consulta EFI para confirmar, mas não mostra validação local de assinatura antes da lógica.
Impacto: bypass/replay/aceite de eventos inválidos em algum caminho.
Recomendação: unificar entrada por provider, exigir assinatura/timestamp/nonce, persistir evento antes de processar, rejeitar replay por `event_id`.

### SEC-03 — Reprocessamento de DLQ pode perder contexto de segurança — Alto/Crítico
Evidência: `GatewayWebhookController::reprocess` recria `Request::create('/api/webhooks/gateway/' . gateway_code, 'POST', payload)` sem restaurar headers originais/assinaturas.
Impacto: reprocessamento pode falhar, ou pior, processar payload sem as garantias equivalentes ao webhook original dependendo do validator.
Recomendação: armazenar headers seguros necessários, validar integridade do item DLQ, reprocessar por pipeline interno idempotente, não por request HTTP simulado.

### SEC-04 — Admin financeiro amplo com rotas retired e ações críticas — Alto
Evidências: `routes/admin.php` possui operações sobre ledger, settlements, chargebacks, users, update-balance, gateway credentials, withdrawal, webhooks, DLQ. Também usa `$retiredAdminModule`.
Impacto: risco de autorização incorreta, superfície excessiva, rotas mortas acessíveis.
Recomendação: matriz RBAC por ação, testes de bypass, remover rotas retired, auditar permissões por controller/action.

## Achados altos
### SEC-05 — Idempotência com dois middlewares/padrões — Alto
Evidências: `CheckIdempotency.php` e `IdempotencyMiddleware.php`; um usa request all/json_encode, outro body cru e `IdempotencyService`.
Impacto: comportamento divergente, replay parcial, race condition em endpoints não cobertos.
Recomendação: middleware único obrigatório em POST financeiro; unique index; lock atômico; replay seguro.

### SEC-06 — CSRF exceção apenas para `ipn/*`, mas webhooks API dependem de middleware api — Médio/Alto
Evidências: `bootstrap/app.php` exclui `ipn/*`; rotas API usam middleware `api`, webhooks ficam em `/api/...`.
Risco: aceitável para API se assinatura forte existir; crítico se assinatura inconsistente.

### SEC-07 — HTML customizado sem XSS middleware — Alto
Evidências: rotas admin de custom landing/component usam `withoutMiddleware('XSS')`.
Impacto: XSS armazenado no CMS/admin/frontend.
Recomendação: sanitização por allowlist, CSP forte, preview isolado, testes XSS.

### SEC-08 — Logs de payload/headers podem conter dados sensíveis — Alto
Evidências: webhooks salvam `payload` e `headers`; há `MaskHelper` e scrubber, mas precisa provar cobertura. `AuthenticateApiRequest` grava user agent/IP e tamanhos; Gateway logs registram exceção arquivo/linha.
Impacto: vazamento de PII, tokens, dados financeiros.
Recomendação: política de masking central, retenção LGPD, criptografia/controle de acesso a logs.

### SEC-09 — Mass assignment em models financeiros — Alto
Evidências: `Wallet` tem `balance`, `available_balance`, `pending_balance`, `withdrawn_balance` em `$fillable`, com override `fill()` que remove em updates existentes, mas permite em create/unguarded.
Impacto: risco em create/seed/admin se request passar saldos.
Recomendação: tirar saldos de fillable; alteração só via LedgerService/WalletService.

## Médios / baixos a confirmar
- CORS: arquivo config deve ser revisado em runtime.
- CSP/headers: `SecureHeaders` existe; confirmar política final e não apenas headers básicos.
- Brute force: middlewares throttle aparecem em algumas rotas, mas login/register/admin/API precisam testes específicos.
- Sanctum: presente; confirmar se realmente necessário em API pública e se stateful domains não ampliam superfície.
- OAuth/certificados gateway: drivers existem; confirmar armazenamento, permissões filesystem e rotação.
- Uploads: FileController/image upload/KYC docs/support attachments exigem validação de MIME, tamanho, storage privado e antivírus se produção.
- SQL Injection: uso Eloquent reduz risco, mas filtros com search/daterange precisam testes; não foi identificado SQL cru crítico nesta fase.

## Controles positivos
- Middleware global `SecureHeaders`, `CorrelationId`, `PerformanceLogging`, manutenção financeira/read-only.
- `MaskHelper` e `SentryEventScrubber` presentes.
- Locks/transações em Ledger/Wallet e muitos services.
- Testes existentes para webhook signature, idempotency, war room security, gateway validation.

## Prioridade de correção antes de produção
1. Unificar autenticação API e armazenamento hash de chaves.
2. Unificar webhooks com assinatura, timestamp, anti-replay e DLQ segura.
3. Remover saldos de fillable e bloquear alterações fora do ledger.
4. RBAC/admin action audit com testes de bypass.
5. Sanitização/CSP para CMS/custom HTML.

## Autossuficiência
Este documento registra achados de segurança com evidências por arquivo e deve guiar Fase 3 e roadmap.
