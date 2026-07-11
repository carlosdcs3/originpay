<!-- ORIGINPAY PRODUCTION CONSTITUTION | generated 2026-07-10T10:14:39 | source: docs/audit | scope: documentation only -->

# 04 — Security Testing

## Objetivo
Planejar ataques controlados, reproduzíveis e seguros para validar OWASP Top 10, OWASP ASVS, PCI DSS e ameaças específicas de gateway de pagamentos.

## Regras de execução
- Executar somente em ambiente autorizado: local, testing, staging ou sandbox.
- Nunca usar dados reais de clientes sem autorização formal.
- Todo teste deve ter script, payload, pré-condição, resultado esperado e cleanup.
- Falha crítica bloqueia release.

## OWASP Top 10 — testes mínimos
### A01 Broken Access Control
- IDOR alterando `merchant_id`, `user_id`, `wallet_id`, `charge_id`.
- Usuário merchant acessando dados de outro merchant.
- Admin sem permissão executando refund, settlement, update balance ou rotate secret.
- Rotas retired acessíveis.

### A02 Cryptographic Failures
- Segredos em response/log/export.
- API secret armazenado em claro.
- Cookies sem Secure/HttpOnly/SameSite.
- Certificados PSP acessíveis via web/storage público.

### A03 Injection
- SQL injection em filtros admin/report/search/daterange.
- Header injection em webhooks/logs.
- Template injection em CMS/custom landing/email templates.

### A04 Insecure Design
- Bypass de state machine: charge failed -> paid, settlement paid duas vezes.
- Reprocessamento DLQ sem assinatura/contexto.
- Fluxos que dependem do frontend para valor/status.

### A05 Security Misconfiguration
- CORS permissivo.
- APP_DEBUG true.
- Directory listing/storage exposto.
- Headers ausentes.
- Default credentials.

### A06 Vulnerable Components
- Composer/npm audit.
- Dependências abandonadas em gateways legados.
- Pacotes com CVEs críticos.

### A07 Identification/Auth Failures
- Credential stuffing.
- Brute force login/admin/API key.
- Session fixation.
- Password reset enumeration.
- 2FA bypass.

### A08 Software/Data Integrity Failures
- Webhook payload alterado com assinatura antiga.
- Replay de evento antigo.
- Deploy sem artefato verificável.

### A09 Logging/Monitoring Failures
- Ataques sem alerta.
- Logs sem request_id/correlation_id.
- Incidente financeiro sem audit trail.

### A10 SSRF
- Webhook endpoint test configurado para IP privado/metadata.
- URL callbacks de merchant tentando acessar rede interna.

## OWASP ASVS alvo
- V1 Architecture: threat model para pagamentos, webhooks, admin.
- V2 Authentication: senhas, 2FA, sessions.
- V3 Session Management.
- V4 Access Control.
- V5 Validation/Sanitization.
- V7 Error Handling/Logging.
- V8 Data Protection.
- V10 Malicious Code: dependências/scripts.
- V12 Files/Resources: uploads e downloads.
- V13 API/Web Service.
- V14 Configuration.

## PCI DSS — testes mínimos
- Confirmar que PAN/CVV não são armazenados/logados.
- Verificar tokenização por PSP para cartão.
- Validar segmentação e controles de acesso a dados de pagamento.
- Verificar rotação/armazenamento de chaves e certificados.
- Executar dependency/security scan antes de release.

## Ataques específicos de gateway
### Webhook spoofing
- Enviar payload válido sem assinatura.
- Assinatura de outro provider.
- Provider route mismatch.
- Header faltando.
- Payload com status paid falso.

### Replay
- Mesmo event_id repetido.
- Mesmo txid com payload diferente.
- Timestamp antigo.
- Reprocessamento DLQ repetido.

### Race condition
- 50 webhooks paid simultâneos.
- 50 withdrawals simultâneos no mesmo wallet.
- Refund e settlement simultâneos.
- Chargeback enquanto settlement processa.

### Mass assignment
- Enviar `balance`, `available_balance`, `status`, `is_admin`, `merchant_id`, `user_id`, `fee`, `gateway_id` em payloads.
- Confirmar rejeição ou ignorar seguro.

### Privilege escalation / IDOR
- Trocar IDs em URLs admin/user/developer.
- Testar API key de merchant A em recurso de merchant B.
- Testar sandbox key em produção.

### CSRF
- POST forms de withdraw, API key rotation, webhook endpoint creation, admin financial actions.

### XSS
- CMS custom HTML.
- Support messages.
- Merchant business name.
- Webhook logs viewer.
- API error messages.

### SQL Injection
- Search fields de admin e reports.
- Daterange parsing.
- API filters.

### API abuse
- Burst sem idempotency key.
- Idempotency key com payload diferente.
- Payloads grandes.
- Enumeração de charge/customer IDs.
- Rate limit por IP/key/merchant.

## Evidência exigida
Cada teste deve gerar:
- Comando/script reproduzível.
- Ambiente e seed usados.
- Resultado esperado e real.
- Logs/prints sanitizados.
- Issue vinculada para falha.
- Teste automatizado quando possível.
