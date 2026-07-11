<!-- ORIGINPAY PRODUCTION CONSTITUTION | generated 2026-07-10T10:14:39 | source: docs/audit | scope: documentation only -->

# 01 — Security Baseline

## Status normativo
Este documento define os controles mínimos obrigatórios para produção. Violações devem bloquear implementação, deploy ou go-live.

## Modelo de ameaça principal
A OriginPay processa dinheiro, dados pessoais, credenciais de merchants, webhooks de PSPs e ações administrativas críticas. O adversário pode tentar roubar credenciais, falsificar webhooks, repetir eventos, escalar privilégios, explorar race conditions, manipular valores, vazar dados sensíveis ou abusar de APIs.

## Regras obrigatórias gerais
1. Falhar fechado em toda validação de segurança.
2. Nunca armazenar segredo recuperável quando hash é suficiente.
3. Nunca logar segredo, token, senha, assinatura, cartão, CPF completo ou payload sensível sem masking.
4. Toda ação financeira exige autenticação, autorização, idempotência, auditoria e teste.
5. Toda entrada externa deve ser validada por allowlist.
6. Toda mudança de permissão, segredo ou saldo deve gerar audit log.
7. Segurança não pode depender apenas do frontend.

## Autenticação
### Usuários web/admin
- Senhas com hashing forte padrão Laravel atual.
- 2FA obrigatório para admin e recomendado para merchants.
- Sessões com Secure, HttpOnly e SameSite adequados.
- Regenerar sessão após login e mudança de privilégio.
- Lockout/rate limit por IP + usuário + fingerprint.
- Login admin deve ter monitoramento e alerta de anomalias.

### API pública
- API keys devem seguir modelo hash-based: o segredo completo nunca deve ser armazenado em claro.
- Usar prefixo público identificável e secret exibido apenas uma vez na criação.
- Suportar revogação, expiração, rotação e escopos.
- API key legacy em `Merchant` é proibida como mecanismo final de produção.
- Durante migração, qualquer fallback legado deve ser explicitamente marcado como depreciado, não pode gerar novas chaves em claro e deve ter prazo/critério de remoção documentado.
- Todas as respostas devem evitar leak de existência de merchant/chave.

## Autorização e RBAC
- Permissões devem ser por ação, não apenas por área/tela.
- Admin financeiro deve ter matriz explícita para ledger, settlement, withdrawal, gateway credentials, API keys, webhooks e user management.
- Acesso cross-tenant é proibido; toda query merchant/user scoped deve validar ownership.
- Ações de alto impacto devem exigir step-up authentication e/ou dual control.
- Rotas retired devem estar removidas ou bloqueadas.
- Reprocessamento de DLQ, settlement pay, alteração de gateway credentials, alteração de taxas/limites, ajuste manual de saldo, login-as-user e emissão/rotação/revogação administrativa de credenciais são ações críticas: exigem permissão granular, motivo estruturado, responsável, timestamp, correlation/request id e audit log.
- Nenhuma rota administrativa pode existir em `routes/api.php` sem autenticação administrativa explícita, autorização granular e auditoria; endpoints administrativos devem preferir o grupo `routes/admin.php` com middleware admin global.

## API Keys
Obrigatório:
- Hash SHA-256/HMAC ou mecanismo equivalente com salt/pepper quando aplicável.
- Prefixo público para lookup seguro.
- Scopes por endpoint: read/write charges, refunds, payouts, webhooks, customers.
- Ambientes separados: sandbox e production não podem compartilhar segredo.
- Rotação sem downtime com old/new temporário controlado.
- Revogação imediata e auditada.
- Rate limit por key, merchant, IP e endpoint.

## OAuth e certificados PSP
- Certificados devem ficar fora do web root.
- Permissões filesystem mínimas.
- Senhas de certificado em secret manager ou env seguro.
- Rotação documentada em runbook.
- Nunca logar certificado, client secret ou access token.
- Access token cacheado deve ter TTL menor que expiração real.

## Webhooks
Obrigatório para todo provider:
- Identificar provider por rota/registro confiável.
- Validar assinatura localmente antes de mutação financeira.
- Validar timestamp com tolerância curta.
- Validar event_id/txid único por provider.
- Persistir payload bruto e headers seguros antes de processar.
- Processar idempotentemente.
- Retornar 2xx apenas após persistência durável.
- Nunca confiar apenas em campo de status recebido; quando necessário, confirmar com PSP.

## Replay Protection
- Unique constraint: `(provider, event_id)` ou equivalente.
- Janela de timestamp por provider.
- Nonce/event id obrigatório quando provider suportar.
- Eventos repetidos devem retornar sucesso idempotente sem side effect.
- Payload igual com mesmo event id não pode criar ledger novamente.
- Payload diferente com mesmo event id deve ser incidente de segurança.

## Idempotência
- Middleware/serviço único para POST financeiro.
- Chave idempotente vinculada a merchant, método, path e hash do corpo canônico.
- Reuso com payload diferente retorna 409.
- Requisição concorrente retorna 409/202 conforme contrato, nunca duplica side effect.
- Resposta anterior pode ser replayed somente após finalização segura.

## Rate limit e abuso
- Limites por IP, API key, merchant, rota, método e ação sensível.
- Limites separados para login, password reset, API key creation, checkout, webhook, refunds e payouts.
- Alertas para credential stuffing, enumeração e bursts anormais.
- Webhooks legítimos devem ter política por provider sem permitir DDoS lógico.

## Headers e browser security
- HSTS em produção.
- CSP forte; CMS/custom HTML deve usar sandbox/allowlist.
- X-Frame-Options ou frame-ancestors.
- X-Content-Type-Options: nosniff.
- Referrer-Policy restritiva.
- Permissions-Policy mínima.
- Cookies Secure, HttpOnly e SameSite.

## CSRF/CORS
- CSRF obrigatório para rotas web state-changing.
- Exceções CSRF apenas para callbacks/webhooks assinados e documentados.
- CORS fechado por allowlist; nunca `*` com credentials.
- APIs públicas devem usar autenticação explícita e não depender de cookies stateful exceto quando intencional.

## Segredos e logs
- Usar secret manager ou env seguro.
- `.env` não deve ser versionado nem compartilhado.
- Logs devem usar masking central para: api_key, secret, token, authorization, password, certificate, card, CPF/document, bank account.
- Retenção de logs deve seguir LGPD e necessidade operacional.
- Acesso a logs financeiros deve ser restrito e auditado.

## Defesa em profundidade
- Validação server-side em todos os casos.
- Policies/gates para models sensíveis.
- Database constraints para invariantes críticas.
- Auditoria imutável de ações administrativas.
- Alertas de anomalia financeira.
- Read-only/emergency mode testado.
- Backups criptografados e restore testado.

## PCI DSS
- Não armazenar PAN/CVV salvo escopo e certificação específicos.
- Preferir tokenização por PSP.
- CVV nunca deve ser armazenado/logado.
- Segmentar ambiente de pagamento.
- Manter inventário de fluxos que tocam dados de cartão.
- Executar ASV scan/pentest quando aplicável.

## OWASP ASVS mínimo
- ASVS nível 2 para toda aplicação.
- ASVS nível 3 para autenticação, API keys, admin, webhooks e fluxos financeiros.
- Evidências devem ser teste automatizado, configuração versionada ou runbook validado.
