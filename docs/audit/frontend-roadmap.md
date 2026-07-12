# Roadmap canônico do frontend

## Baseline

O frontend real é server-rendered no próprio Laravel: `resources/views`, `resources/js` e `resources/css`, com rotas em `routes/web.php`, `routes/auth.php`, `routes/admin.php` e `routes/connect.php`. Não há SPA separada.

Stack: Blade/PHP, JavaScript ES modules, Alpine.js, Axios, Laravel Echo/Pusher, Tailwind CSS 3/PostCSS/Autoprefixer e Vite 5 via `laravel-vite-plugin`. npm é o gerenciador (`package-lock.json`). Sessões e guards são Laravel; não há armazenamento frontend explícito de tokens. Não há state manager adicional nem suíte frontend configurada. Scripts canônicos disponíveis: `dev` e `build`.

Aplicações: landing/páginas públicas, autenticação user/merchant/admin, merchant dashboard (`frontend/user`), Merchant Connect, developer portal/documentação, checkout/payment links e painel admin (`admin` + `backend`). Há componentes Blade compartilhados em `resources/views/components/{ui,ds,admin}` e layouts separados.

Baseline de 2026-07-12: `npm run build` passou (59 módulos; aviso não bloqueante de `caniuse-lite` desatualizado). `package.json` não define typecheck, lint ou testes; dependências já estavam instaladas. Estado heterogêneo: áreas funcionais convivem com views legadas, mocks e placeholders, especialmente no admin; checkout tem auditoria própria concluída. Documentação relacionada encontrada: `docs/admin-audit.md`, `docs/checkout-production-review.md`, `docs/dashboards_proposal.md` e auditorias estruturais em `docs/audit`.

## Fases

1. **Baseline e arquitetura** — consolidar mapa de superfícies, contratos de layouts/assets, comandos de qualidade e matriz de risco sem alterar regras backend.
2. **Design system e shells** — convergir componentes Blade existentes; estabilizar separadamente shells merchant e admin, navegação, responsividade e acessibilidade.
3. **Auth e sessões** — validar login, recuperação, verificação, 2FA, guards, expiração e respostas 401/403 sem duplicar autorização.
4. **Merchant dashboard** — remover mocks críticos e completar estados loading/error/empty nas jornadas prioritárias.
5. **Admin** — substituir placeholders priorizados conforme `admin-audit.md`, preservando isolamento e permissões do servidor.
6. **Developer portal** — consolidar chaves, webhooks, logs, sandbox e documentação com integração real.
7. **Checkout** — preservar o baseline aprovado e evoluir somente por contratos/documentação específicos.
8. **Integração e resiliência** — padronizar CSRF/Axios e tratamento 401, 403, 404, 422, 429 e 5xx; eliminar mocks críticos.
9. **Qualidade transversal** — loading/error/empty, responsividade, WCAG, segurança frontend e performance.
10. **Testes e release readiness** — adicionar unitários/integrados e E2E em blocos maiores; gates de build, lint, typecheck e testes antes da prontidão de release.

## Primeiro bloco

**Baseline e arquitetura**, concluído nesta rodada: localização, stack, superfícies, integração, documentação e gates executáveis foram registrados. Nenhum código foi alterado porque o build está saudável e não existe script canônico de teste/lint/typecheck; inventar infraestrutura ou reabrir regras backend violaria o escopo.

## Riscos e próximo bloco

Riscos: ausência de gates frontend além do build; JavaScript/CSS inline e famílias de design system concorrentes; placeholders/mocks; cobertura frontend e E2E inexistente no `package.json`; aviso de Browserslist (não atualizar dependências nesta rodada).

Próximo bloco recomendado: **Design system e shell merchant**, começando por testes de contrato das rotas/layout e pela navegação/guards do `resources/views/frontend/layouts/user-v2.blade.php`, sem redesenho amplo. O shell admin deve permanecer em bloco separado e seguir `docs/admin-audit.md`.
