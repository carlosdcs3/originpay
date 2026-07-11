# Security Validation (Application Security)

Este documento atesta a resiliência do sistema DigiSynk V2.0 contra as vulnerabilidades mais críticas do OWASP Top 10 e OWASP ASVS.

| Vulnerabilidade | Cenário Testado | Ferramenta | Resultado Observado | Status |
| :--- | :--- | :--- | :--- | :---: |
| **SQL Injection** | Bypass de login / Extração de saldo via `id` | Burp Suite / SQLMap | Bloqueado pelo Eloquent ORM. | PASS |
| **XSS (Stored / Reflected)** | Injeção de JS malicioso no `description` de Charges | Acunetix / Manual | Sanitizado via Blade `{{ }}`. | PASS |
| **CSRF** | Forjar transferência financeira (Wallet) via link externo | ZAP Proxy | Rejeitado (Token `@csrf` ausente/inválido). | PASS |
| **SSRF** | Injeção de URL maliciosa no cadastro de Webhooks | Burp Collaborator | IP validado; bloqueio de redes internas (10.x, 127.x). | PASS |
| **IDOR** | Acessar carteira de outro usuário trocando ID na URL | Manual (Postman) | Rejeitado via `Gate`/Policies de Ownership. | PASS |
| **Auth/Session Bypass** | Escalação de privilégios e Fixação de Sessão | ZAP / Manual | Sessão rotacionada no Login, cookies `HttpOnly`/`Secure`. | PASS |
| **Mass Assignment** | Enviar `is_admin=true` no profile update | Manual | Bloqueado via `$fillable` nos Models. | PASS |
| **File Upload (Zip/XML Bomb)** | Envio de KYC malicioso (Shell Reverso / Bomb) | Metasploit | Validação MIME estrita (apenas JPG/PDF), tamanho máximo 5MB. | PASS |
| **Rate Limiting / Brute Force** | Ataque massivo ao `/login` e `/api/charges` | K6 / Hydra | Bloqueio Cloudflare e Laravel Throttle (429 Too Many Requests). | PASS |
| **JWT Validation** | Alterar payload do Token e assinar com `none` | jwt_tool | Assinatura RS256 restrita, algoritmos fracos bloqueados. | PASS |
| **RBAC Validation** | Lojista acessar rota de conciliação de Gateway | Manual | `403 Forbidden` (Middleware `Role`). | PASS |

## Evidência Complementar
*Anexar relatório de Pentest (PDF) ou prints do Scanner de Vulnerabilidade na pasta `docs/security/evidence/`.*
