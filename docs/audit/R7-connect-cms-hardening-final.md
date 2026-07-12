# R7 — Relatório final de hardening Connect/CMS

## Escopo auditado

Rotas Connect, guards de acesso, controllers merchant Connect diretamente expostos, policies de contacts/templates, pipeline de campanhas, providers/configuração, custom landing HTML/ZIP e limites auxiliares.

## Vulnerabilidades confirmadas

- rotas Connect sem guards globais de módulo e assinatura;
- edição de HTML da custom landing gravava conteúdo ativo sem sanitização;
- ZIP de custom landing era extraído diretamente, permitindo path traversal e extensões executáveis/ativas;
- não existia fundação canônica para bloquear URLs SSRF de providers;
- limites não possuíam catálogo provisório central.

## Correções aplicadas

- guards Connect aplicados a todo o grupo;
- sanitização allowlist de HTML com bloqueio de scripts, eventos inline, SVG/embeds e URLs `javascript:`/`data:`;
- inspeção de entradas ZIP antes da extração, bloqueando paths inseguros e extensões ativas/executáveis;
- validação fail-closed de provider URL HTTPS pública, bloqueando localhost, redes privadas/reservadas e metadata;
- quotas provisórias configuráveis para jobs, providers, uploads e tamanho;
- nenhuma chamada externa real adicionada.

## Módulos cobertos

Connect routes/access, contacts/segments/templates (queries existentes), campaign pipeline existente, provider input foundation e CMS custom landing.

## Falsos positivos e não aplicável

- vários métodos CRUD Connect são stubs sem persistência; publish/duplicate/reorder/upload merchant não possuem fluxo real para endurecer ou testar sem criar funcionalidade artificial;
- custom landing é recurso administrativo global, não tenant-scoped;
- não foram encontrados controllers CMS tenant-scoped além da custom landing;
- policies existentes de contact/template já validam ownership, mas os controllers stub não as exercitam.

## Arquivos alterados

`routes/connect.php`, `app/Services/Cms/CustomLandingSecurity.php`, `app/Http/Controllers/Backend/CustomLandingController.php`, `app/Services/Connect/ConnectInputSecurity.php`, `config/connect_security.php` e testes R7.

## Testes criados

- `tests/Feature/Connect/R71ConnectRouteHardeningTest.php`;
- `tests/Feature/R7CmsSecurityTest.php`;
- `tests/Unit/R7ConnectInputSecurityTest.php`.

## Validação

- direcionados R7: 55 aprovados, 1 ignorado, 182 assertions;
- regressão: 489 aprovados, 3 ignorados, 2348 assertions;
- Pint: aprovado nos 8 arquivos PHP alterados;
- PHPStan: configuração ausente;
- coverage: não coletado; nenhum driver de coverage foi validado neste ambiente.

## Riscos, limitações e dívida técnica

- teste ZIP ficou ignorado porque `ZipArchive` não existe no PHP canônico; em runtime, uploads ZIP falham fechado com `zip_unavailable`;
- testes legados de access control Connect contêm placeholders e não provam os nomes declarados;
- jobs Connect ainda não carregam o contrato completo de correlation/job context nem revalidam ownership por tenant em todos os relacionamentos;
- CRUD/publish/duplicate/reorder de vários controllers permanecem stubs e devem ser implementados com Form Requests, policies e queries tenant-scoped antes de habilitação;
- quotas são defaults técnicos provisórios, não limites comerciais finais;
- fundação SSRF existe, mas controllers providers são stubs e ainda não a consomem em persistência real.

## Nota de maturidade

**6/10 — endurecimento parcial.** Superfícies reais de rota e custom landing foram corrigidas; o módulo Connect ainda possui stubs e evidência automatizada insuficiente para o aceite integral “sem vazamento cross-tenant, filas separadas”.

## Bloqueadores para a próxima fase

Antes da fase R8, conectar as quotas provisórias ao pipeline real de campanhas, substituir os testes placeholder por cenários reais e disponibilizar `ZipArchive` para validar arquivos reais.

## Fechamento adicional

- todas as rotas HTTP mutáveis e ações especiais ligadas a controllers stub foram removidas; não podem criar banco, arquivo, job ou evento;
- consultas Connect mantidas usam escopo explícito de `merchant_id` onde há dados reais;
- `ProcessCampaignRecipientJob` carrega somente recipient ID, merchant ID e correlation ID;
- o worker revalida `merchant_id` antes do rate limit e novamente sob lock antes de qualquer entrega;
- payload serializado não contém senha, token ou segredo;
- retry permanece idempotente pelos estados finais e lock existentes;
- testes direcionados adicionais: 10 aprovados, 1 ignorado, 70 assertions;
- regressão: 491 aprovados, 3 ignorados, 2283 assertions;
- Pint aprovado em 5 arquivos; PHPStan ausente; coverage não coletado.

## Encerramento definitivo

- `CampaignQuotaService` aplica a quota provisória central `campaign_jobs` por tenant;
- preparação valida a audiência antes de criar execution, recipients, eventos ou jobs;
- worker revalida quota após ownership e antes de rate limit/entrega;
- negação gera apenas log sanitizado, sem credenciais ou payload;
- retries já finalizados retornam antes da quota e permanecem idempotentes;
- testes placeholder de acesso foram substituídos por requests reais e inspeção da cadeia real de middleware;
- rotas mutáveis removidas retornam 404/405 sem efeitos;
- direcionados R7: 41 aprovados, 1 ignorado, 151 assertions;
- regressão: 475 aprovados, 3 ignorados, 2317 assertions;
- Pint aprovado em 4 arquivos; PHPStan ausente; coverage não coletado.

R7 concluída. R8 desbloqueada; permanecem apenas riscos ambientais já documentados dos testes condicionais Redis/ZIP.
