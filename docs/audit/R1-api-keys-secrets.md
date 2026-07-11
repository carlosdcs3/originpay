<!-- ORIGINPAY AUDIT | R1 API Keys and Secrets | generated 2026-07-10 -->

# R1 — Autenticação, API Keys e Segredos

## Objetivo
Eliminar/reduzir riscos críticos ligados a credenciais/API keys legadas possivelmente armazenadas em claro e iniciar a unificação do modelo seguro de autenticação sem interromper rotas existentes.

## Documentação seguida
- `docs/audit/R0-baseline-runtime.md`
- `docs/audit/02-security.md`
- `docs/audit/05-product-roadmap.md`
- `docs/production/01-security-baseline.md`
- `docs/production/08-coding-standards.md`

## Análise de impacto da migração

### 1. Rotas, serviços e middlewares que ainda dependem do modelo legado
Dependências diretas identificadas:

- `routes/api.php`
  - `POST /api/v1/initiate-payment`
  - `GET /api/v1/verify-payment/{trxId}`
  - middleware `merchant.auth`.
- `app/Http/Middleware/MerchantApiAuth.php`
  - ainda possui fallback temporário para `X-API-Key` + `X-Merchant-Key` contra colunas legadas em `merchants`.
- `app/Services/TransactionService.php`
  - IPN legado usa `merchants.api_secret` como segredo HMAC quando presente.
- Views/admin que exibem `merchant_key` como identificador histórico:
  - `resources/views/backend/merchant/index.blade.php`
  - `resources/views/backend/merchant/partials/_review_modal.blade.php`
  - `resources/views/backend/user/manage/merchant.blade.php`
- `resources/views/frontend/user/credentials/index.blade.php`
  - chamava getters de credenciais atuais; agora recebe mensagem segura para API key e o identificador merchant quando aplicável.
- `app/Models/Merchant.php`
  - ainda mantém colunas legadas por compatibilidade de schema, mas elas estão escondidas em serialização e fora de mass assignment.

Dependências indiretas/legadas observadas:
- `app/Observers/MerchantObserver.php` gerava credenciais em claro para novos merchants; isso foi interrompido.
- `app/Http/Controllers/Frontend/CredentialsController.php` criava secrets em claro; isso foi interrompido.
- `app/Http/Controllers/User/Developer/ApiKeyController.php` usa tabela `api_keys` e agora grava hash sha256 compatível com `AuthenticateApiKey`.
- `app/Services/Auth/ApiKeyManagementService.php` e `api_credentials` seguem como caminho recomendado hash-based.

### 2. Evidência de risco de quebra de integrações atuais
Sim. Há evidência de que desligar imediatamente o legado quebraria integrações atuais:

- `routes/api.php` ainda expõe rotas legadas protegidas por `merchant.auth`.
- `MerchantApiAuth` historicamente esperava `X-API-Key` e `X-Merchant-Key`.
- `TransactionService::sendMerchantPaymentIPN()` ainda usa `merchants.api_secret` para assinatura HMAC de IPN legado.

Conclusão: remoção abrupta das colunas/credenciais legadas **não é segura neste momento**. A migração segura exige compatibilidade temporária, emissão de credenciais modernas e telemetria/depreciação.

### 3. Estratégia de migração compatível
Implementada estratégia de compatibilidade controlada:

1. **Não gerar novos secrets legados em claro**
   - `MerchantObserver` não cria mais `api_key`, `api_secret`, `test_api_key`, `test_api_secret`, `test_merchant_key`.
   - `CredentialsController` não popula mais esses campos.
2. **Blindar o model legado**
   - `Merchant` removeu credenciais legadas de `$fillable`.
   - `Merchant` adicionou `$hidden` para segredos/identificadores sensíveis.
   - Accessors de API key/secret não retornam segredo real.
3. **Preferir autenticação moderna**
   - `MerchantApiAuth` agora tenta `Authorization: Bearer sk_...` via `ApiAuthenticationService` antes do fallback legado.
4. **Fallback legado temporário**
   - `MerchantApiAuth` mantém `X-API-Key` + `X-Merchant-Key` apenas para compatibilidade e marca resposta com:
     - `X-OriginPay-Auth-Mode: legacy_deprecated`
     - `Deprecation: true`
5. **Corrigir hash em `api_keys`**
   - `ApiKeyController` grava `hash('sha256', $secret)` para compatibilidade com `AuthenticateApiKey`.
6. **Reduzir vazamento em logs**
   - `LogApiRequests` e `AdminAuditMiddleware` mascaram/removem mais nomes sensíveis.
7. **Preservar IPN legado sem expor accessor**
   - `TransactionService` usa `getRawOriginal('api_secret')` apenas para assinatura IPN legada quando existir; se não existir, o IPN é pulado com warning sem segredo.

### 4. Descontinuação do modelo legado
Plano recomendado:

1. **Fase de compatibilidade atual**
   - Fallback legado existe, depreciado e sinalizado por headers.
   - Novas credenciais em claro não são mais geradas.
2. **Fase de migração ativa**
   - Criar script/comando seguro para emitir `api_credentials` hash-based para merchants que ainda dependem de legado.
   - Comunicar merchants sobre rotação para Bearer `sk_...`.
   - Medir uso do fallback por logs/métricas usando `api_auth_mode=legacy_deprecated`.
3. **Fase de bloqueio gradual**
   - Desativar fallback em sandbox primeiro.
   - Depois bloquear novos usos em produção com allowlist temporária por merchant crítico.
4. **Fase de remoção**
   - Remover `merchant.auth` legado das rotas ou redirecionar para middleware moderno.
   - Remover uso de `merchants.api_secret` em IPN legado ou migrar assinatura para webhook secrets hash/secret manager.
   - Criar migration para limpar/dropar colunas legadas somente após janela de compatibilidade.

### 5. Momento seguro para remover definitivamente credenciais em claro do Merchant
Será seguro remover quando todos os critérios abaixo forem verdadeiros:

- Nenhuma rota ativa usa `merchant.auth` com fallback legado.
- Métricas mostram zero uso de `legacy_deprecated` por uma janela operacional definida.
- Todos os merchants ativos possuem credenciais modernas em `api_credentials` ou `api_keys` hash-based.
- IPN/webhooks legados foram migrados para secrets próprios não armazenados em claro ou foram descontinuados.
- Testes de contrato/API e regressão passam sem colunas `api_key/api_secret/test_*` em `merchants`.
- Runbook de rollback e comunicação de breaking change estão aprovados.

## Arquivos modificados
- `app/Models/Merchant.php`
- `app/Observers/MerchantObserver.php`
- `app/Http/Controllers/Frontend/CredentialsController.php`
- `app/Http/Controllers/User/Developer/ApiKeyController.php`
- `app/Http/Middleware/MerchantApiAuth.php`
- `app/Http/Middleware/LogApiRequests.php`
- `app/Http/Middleware/AdminAuditMiddleware.php`
- `app/Services/Auth/ApiAuthenticationService.php`
- `app/Services/TransactionService.php`
- `tests/Feature/ApiCredentialsSecurityTest.php`
- `docs/production/01-security-baseline.md`

## Validação de segurança implementada
- `Merchant` não serializa credenciais legadas.
- Credenciais legadas não são mass assignable.
- Novos merchants não recebem API secrets em claro pelo observer.
- Tela de credenciais não cria secrets legados em claro.
- `api_credentials` continua usando secret hash e secret exibido só no retorno de criação.
- Public key (`pk_...`) não autentica como Bearer secret.
- Rotação/revogação do modelo hash-based foi validada.
- `api_keys` usa hash sha256 compatível com middleware existente.
- Logs de API e auditoria ampliaram masking/remoção de nomes sensíveis.

## Testes executados

### Teste novo específico
```text
E:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe artisan test tests/Feature/ApiCredentialsSecurityTest.php
```
Resultado:
```text
7 passed, 35 assertions
```

### Regressão mínima planejada
```text
E:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe artisan test tests/Feature/PaymentsAuthenticationTest.php tests/Feature/PaymentsApiSkeletonTest.php tests/Feature/PaymentsIdempotencyTest.php tests/Feature/CustomerSubscriptionApiTest.php
```
Resultado:
```text
21 passed, 105 assertions
```

### Regressão completa
```text
E:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe artisan test
```
Resultado:
```text
403 passed, 1388 assertions
```

## Riscos restantes
1. Fallback legado ainda existe em `MerchantApiAuth` para evitar quebra imediata.
2. Colunas legadas em `merchants` ainda existem e podem conter dados históricos em claro.
3. IPN legado ainda pode usar `merchants.api_secret` para merchants antigos que possuem esse campo.
4. `api_keys` antigas geradas com `Hash::make()` provavelmente já não autenticavam pelo middleware sha256; novas chaves passam a autenticar corretamente.
5. `merchant_key` ainda existe como identificador histórico e aparece em algumas telas admin; deve ser tratado como identificador sensível/operacional, não como segredo de API.

## Status R1
R1 foi implementado como **migração mínima compatível**.

Status: **Concluído com risco residual controlado**.

Bloqueio de produção permanece para remoção total do legado até conclusão da fase de migração/descontinuação descrita acima.
