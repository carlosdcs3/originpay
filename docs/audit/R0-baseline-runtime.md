<!-- ORIGINPAY AUDIT | R0 Baseline Runtime | generated 2026-07-10 | scope: runtime validation only, no product code changes -->

# R0 — Baseline Executável Runtime

## Objetivo
Validar o baseline executável da OriginPay conforme `docs/audit/05-product-roadmap.md` e regras permanentes em `docs/production/*`, usando PHP/Composer instalados via Laragon. Esta fase não implementa correções e não altera código de produção.

## Escopo e método
- Repositório: `E:\projetos\DigiKash v1.0.5\DigiKash v1.0.5`
- Aplicação principal: `E:\projetos\DigiKash v1.0.5\DigiKash v1.0.5\core`
- Documentação lida antes da execução:
  - `docs/production/00-architecture.md`
  - `docs/production/03-testing-strategy.md`
  - `docs/production/07-release-checklist.md`
  - `docs/production/08-coding-standards.md`
  - `docs/audit/05-product-roadmap.md`
  - `docs/audit/EXECUTIVE_SUMMARY.md`
- Ferramentas procuradas primeiro em `C:\laragon\...`, conforme instrução; no ambiente real elas foram encontradas em `E:\laragon\...`.
- Logs completos foram salvos em `docs/audit/runtime-logs/`.

## Ferramentas encontradas
| Ferramenta | Caminho | Resultado |
|---|---|---|
| PHP | `E:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe` | Encontrado, compatível com Laravel 11 |
| Composer | `E:\laragon\bin\composer\composer.phar` | Encontrado e executável via PHP |
| Redis | `E:\laragon\bin\redis\redis-x64-5.0.14.1\redis-server.exe` | Encontrado; não iniciado por esta fase |

## Comandos executados
Todos os comandos foram executados dentro de:

```text
E:\projetos\DigiKash v1.0.5\DigiKash v1.0.5\core
```

Usando PHP absoluto:

```text
E:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe
```

## Resultado resumido
| Comando | Exit code | Resultado | Log |
|---|---:|---|---|
| `php -v` | 0 | PHP 8.3.30 OK | `docs/audit/runtime-logs/php-v.log` |
| `composer -V` | 0 | Composer 2.9.4 OK | `docs/audit/runtime-logs/composer-V.log` |
| `php artisan about` | 0 | Laravel/OriginPay boot OK | `docs/audit/runtime-logs/artisan-about.log` |
| `php artisan route:list` | 0 | 674 rotas carregadas | `docs/audit/runtime-logs/artisan-route-list.log` |
| `php artisan migrate:status` | 0 | 198 migrations executadas; nenhuma pendente detectada | `docs/audit/runtime-logs/artisan-migrate-status.log` |
| `php artisan test` | 0 | 396 testes passaram; 1353 assertions | `docs/audit/runtime-logs/artisan-test.log` |

## Evidências principais
### PHP
```text
PHP 8.3.30 (cli) (built: Jan 13 2026 22:50:40) (ZTS Visual C++ 2019 x64)
Zend Engine v4.3.30
```

### Composer
```text
Composer version 2.9.4 2026-01-22 14:08:50
PHP version 8.3.30 (E:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe)
```

### Laravel about
```text
Application Name: OriginPay
Laravel Version: 11.54.0
PHP Version: 8.3.30
Environment: local
Debug Mode: ENABLED
Database: pgsql
Queue: database
Cache: file
Session: file
Routes: NOT CACHED
Config: NOT CACHED
Views: CACHED
```

### Rotas
```text
Showing [674] routes
```

Áreas visíveis no route list incluem admin, billing, compliance, gateway, finance, KYC, merchant, developer portal, user wallet/withdraw, API e páginas públicas.

### Migrations
```text
198 migrations com status Ran
0 pendentes detectadas no output
```

### Testes
```text
Tests: 396 passed (1353 assertions)
Duration: 20.72s
Exit code: 0
```

Cobertura nominal observada no output inclui unit tests, admin UI/content, auth middleware, backup/disaster, boleto, charge engine, platform fees, circuit breaker, compliance, Connect, customer subscriptions, EFI, finance concurrency/chaos, ledger immutability, wallet integrity, webhook validation/signature/stress, withdrawal flow e war room security.

## Análise de impacto
Como esta fase executou apenas comandos de validação e gerou documentação:

| Área | Impacto |
|---|---|
| Código de produto | Nenhum |
| Banco | Leitura via `migrate:status`; testes podem usar ambiente/config de teste da aplicação, mas não houve correção manual |
| Financeiro | Nenhuma mutação de produção implementada |
| Autenticação/autorização | Apenas validação indireta pela suíte |
| API/webhooks | Apenas route list e testes |
| Ledger/wallet/settlement | Apenas testes existentes |
| Redis/queues | Redis encontrado, mas não iniciado; queue driver runtime local reportado como database |
| Documentação | Criado este documento R0 |

## Riscos e observações
1. **R0 executável foi desbloqueado**: a conclusão anterior de PHP ausente no PATH não se aplica quando usamos Laragon por caminho absoluto.
2. **Ferramentas estão em `E:\laragon`, não `C:\laragon`** neste ambiente. Futuras validações devem procurar nos dois locais.
3. **`APP_DEBUG` aparece ENABLED em `artisan about`** porque ambiente é `local`. Isso é aceitável para R0 local, mas continua bloqueador para produção, coerente com `WarRoomSecurityTest` e `docs/production/07-release-checklist.md`.
4. **Drivers locais não representam produção**: cache=file, queue=database, session=file. Produção deve validar Redis/Horizon/HA conforme `docs/production/05-high-availability.md`.
5. **Composer Version no `artisan about` aparece `-`**, apesar de `composer -V` funcionar via `composer.phar`. Não bloqueia R0, mas deve ser documentado se automações dependerem de composer no PATH.
6. **Route list carrega 674 rotas**, confirmando superfície grande; riscos de admin/rotas legadas continuam válidos até hardening específico.
7. **Testes passam**, mas isso não elimina os riscos arquiteturais/security previamente documentados: source of truth de gateways, credenciais legadas, webhooks/DLQ/idempotência e RBAC ainda exigem fases R1–R4.
8. **Não foi executado `migrate:fresh --env=testing`** porque a instrução desta tarefa pediu `migrate:status`. Para cumprir integralmente o roadmap R0 original, recomenda-se uma validação separada em banco descartável.

## Conclusão R0
O baseline runtime local está **operacional** com PHP 8.3 do Laragon:

- PHP executa.
- Composer executa via `composer.phar`.
- Laravel inicializa.
- Rotas carregam.
- Migrations atuais estão aplicadas no banco configurado.
- Suíte automatizada atual passa integralmente: 396 testes / 1353 assertions.

Status: **R0 local aprovado com ressalvas**.

## Próximos passos recomendados
1. Padronizar script/documentação para usar `E:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe` quando PHP não estiver no PATH.
2. Executar R0 complementar em banco descartável com `php artisan migrate:fresh --env=testing`.
3. Iniciar R1 — Segurança de autenticação e credenciais — conforme `docs/audit/05-product-roadmap.md`.
4. Manter bloqueio de produção até resolver riscos críticos de API keys, webhooks/DLQ/idempotência, gateway source of truth e RBAC/admin.
