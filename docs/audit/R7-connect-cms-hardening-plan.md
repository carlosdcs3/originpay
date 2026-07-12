# R7 — Connect/CMS/áreas não-core

Fonte: `docs/audit/05-product-roadmap.md`.

## R7.1 — Guards estruturais das rotas Connect

Implementado:

- todas as rotas sob `user/connect` mantêm autenticação e controles existentes;
- `EnsureConnectEnabled` aplicado ao grupo inteiro;
- `EnsureConnectSubscriptionActive` aplicado ao grupo inteiro;
- nenhuma funcionalidade financeira ou integração externa foi adicionada.

Verificação:

- teste direcionado real em `tests/Feature/Connect/R71ConnectRouteHardeningTest.php`;
- direcionados: 21 aprovados, 139 assertions;
- regressão: 486 aprovados, 2 ignorados, 2336 assertions;
- Pint aprovado;
- PHPStan não configurado.

Riscos restantes da fase:

- testes antigos de acesso Connect ainda contêm casos placeholder;
- isolamento cross-tenant deve ser validado em controllers/repositories reais;
- XSS armazenado, quotas de campanha, falhas de providers, uploads e separação de filas permanecem pendentes.

## Execução consolidada da R7

Entregue:

- hardening de rotas Connect;
- sanitização de HTML e inspeção segura de ZIP da custom landing;
- fundação fail-closed para SSRF de providers;
- catálogo configurável de quotas técnicas provisórias;
- testes direcionados de guards, XSS, URLs e quotas.

Validação final:

- direcionados R7: 55 aprovados, 1 ignorado, 182 assertions;
- regressão: 489 aprovados, 3 ignorados, 2348 assertions;
- Pint aprovado;
- PHPStan e coverage indisponíveis no ambiente.

## Fechamento adicional

- rotas mutáveis e ações especiais cujos controllers eram stubs foram removidas da superfície HTTP;
- permaneceram expostas apenas consultas/telas implementadas e tenant-scoped;
- `ProcessCampaignRecipientJob` passou a carregar `merchant_id` e `correlation_id`, sem credenciais, e revalida o tenant nas duas leituras antes de efeitos;
- o retry preserva a mesma identidade do job e continua idempotente por status/locks existentes;
- regressão final: 491 aprovados, 3 ignorados, 2283 assertions;
- Pint aprovado nos arquivos alterados.

## Encerramento da R7

- quota técnica `campaign_jobs` conectada ao `PrepareCampaignExecutionJob` antes de criar execution/recipients e revalidada no `ProcessCampaignRecipientJob`;
- contagem tenant-scoped de recipients queued/processing;
- excesso falha fechado, antes do dispatch, com transação revertida e log sanitizado;
- retries em estados finais retornam antes da quota e não consomem novamente;
- `AccessControlTest` reescrito integralmente com requests e inspeção de middleware/rotas reais; nenhum `assertTrue(true)` permanece;
- direcionados R7: 41 aprovados, 1 ignorado, 151 assertions;
- regressão: 475 aprovados, 3 ignorados, 2317 assertions;
- Pint aprovado; PHPStan não configurado.

Estado: R7 concluída. As duas pendências finais foram fechadas e a fase R8 está desbloqueada.
