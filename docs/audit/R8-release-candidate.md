# R8 — Release Candidate

## Documento canônico

A fase é definida por `docs/audit/05-product-roadmap.md`: congelar escopo e validar produção por full suite, carga/soak, pentest, reconciliação sandbox, UAT merchant/admin e rollback. O aceite exige zero achados críticos/altos abertos e checklist de produção assinado.

## R8.1 — Congelamento de escopo e gate de readiness

O comando `release:readiness` lê um checklist JSON explícito e falha fechado quando o arquivo está ausente, ilegível, malformado, incompleto ou contém tipos inválidos.

Gates obrigatórios: `scope_frozen`, `full_suite`, `load_soak`, `pentest`, `sandbox_reconciliation`, `merchant_uat`, `admin_uat`, `rollback`, zero em `open_critical_findings` e `open_high_findings`, e `production_checklist_signed`.

## R8.2 — Gate de carga e soak com evidência executável

O gate `load_soak` agora exige simultaneamente o booleano do checklist e uma evidência JSON válida fornecida por `--load-soak-evidence` (padrão: `storage/app/release-candidate/load-soak-evidence.json`). O contrato versionado `1.0` exige: `schema_version`, `executed_at`, `environment`, `duration_seconds`, `requests_total`, `error_rate`, `p95_ms`, `p99_ms`, `throughput_rps`, `functional_failures`, `thresholds` e `result`.

`result = PASS` não é suficiente: todos os campos, tipos, frescor, duração, métricas e thresholds declarados são validados. Ausência, ilegibilidade, JSON inválido, versão incompatível, execução futura/expirada, duração insuficiente, ausência de requisições, erros funcionais ou métricas fora dos limites mantêm o gate bloqueado.

Uso:

```text
E:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe artisan release:readiness --checklist=<checklist.json> --load-soak-evidence=<load-soak-evidence.json>
```

### Thresholds provisórios e configuráveis

Não constituem SLA final. Os padrões estão em `config/release.php` e podem ser ajustados por ambiente:

- validade máxima: 168 horas (`RELEASE_LOAD_SOAK_MAX_AGE_HOURS`);
- duração mínima: 3600 segundos (`RELEASE_LOAD_SOAK_MIN_DURATION_SECONDS`);
- error rate máximo: 0,01 (`RELEASE_LOAD_SOAK_MAX_ERROR_RATE`);
- p95 máximo: 500 ms (`RELEASE_LOAD_SOAK_MAX_P95_MS`);
- p99 máximo: 1000 ms (`RELEASE_LOAD_SOAK_MAX_P99_MS`);
- throughput mínimo: 1 rps (`RELEASE_LOAD_SOAK_MIN_THROUGHPUT_RPS`).

A evidência deve declarar exatamente os thresholds configurados, impedindo aprovação com limites artificialmente permissivos. A execução de carga real não integra o comando; nenhuma carga destrutiva ou operação financeira real foi disparada. O teste de stress existente de idempotência de webhook foi reutilizado na validação direcionada.

## Evidência TDD e validação

O ciclo iniciou em vermelho pela ausência da opção `--load-soak-evidence`; após a implementação, os direcionados cobrem evidência válida, ausente, expirada, duração insuficiente, error rate, p95/p99, falhas funcionais, JSON inválido, contrato incompleto, thresholds divergentes e permanência dos demais gates bloqueados.

- direcionados: 16 aprovados, 38 assertions;
- regressão: 490 aprovados, 3 ignorados, 2351 assertions;
- Pint: aprovado nos 4 arquivos PHP alterados;
- PHPStan: configuração ausente.

## R8.3 — Gate de pentest controlado com evidência canônica

O gate `pentest` exige simultaneamente o booleano do checklist e evidência JSON fornecida por `--pentest-evidence` (padrão: `storage/app/release-candidate/pentest-evidence.json`). O contrato `1.0` contém os campos mínimos definidos para execução, ambiente, metodologia, escopo, responsável, resumo de achados, contagens por severidade, aceite de risco medium, reteste, referência, aprovação operacional e resultado. A validade padrão é de 168 horas (`RELEASE_PENTEST_MAX_AGE_HOURS`).

A validação fail-closed bloqueia ausência, ilegibilidade, JSON inválido, versão incompatível, execução futura ou expirada, contrato/tipos inválidos, critical/high aberto, medium não integralmente aceito, reteste obrigatório pendente, aprovação ausente e escopo mínimo incompleto. `result = PASS` isolado não libera o gate. O escopo canônico cobre autenticação, autorização/RBAC, isolamento entre tenants, IDOR, API Keys/scopes, webhooks, uploads, SSRF, XSS, rate limiting, mass assignment, filas/jobs, endpoints administrativos, exposição de segredos e configurações de produção.

A evidência guarda apenas resumo executivo e referência externa, sem detalhes sensíveis de exploração ou payloads ofensivos completos. Nenhum ataque, PSP real ou dado real de cliente foi acessado.

## Evidência TDD R8.3

O ciclo vermelho confirmou a inexistência de `--pentest-evidence`; após a implementação, os direcionados R8 ficaram verdes.

- direcionados: 27 aprovados, 61 assertions;
- regressão: 502 aprovados, 3 ignorados, 2378 assertions;
- Pint: aprovado nos 3 arquivos PHP alterados;
- PHPStan: configuração ausente.

## R8.4 — Gate de reconciliação financeira sandbox

O gate `sandbox_reconciliation` exige o booleano do checklist e evidência JSON `1.0` fornecida por `--sandbox-reconciliation-evidence` (padrão: `storage/app/release-candidate/sandbox-reconciliation-evidence.json`). O contrato contém todos os campos canônicos de execução, período, amostra, itens verificados, correspondência, divergências, moeda, reteste, referência, aprovação e resultado.

A validação fail-closed aceita somente `sandbox` ou `test`, exige evidência com no máximo 168 horas e amostra mínima configurável de 100 (`RELEASE_RECONCILIATION_MAX_AGE_HOURS` e `RELEASE_RECONCILIATION_MIN_SAMPLE_SIZE`). Bloqueia contrato ausente, ilegível, malformado, incompatível, expirado ou futuro; contagens negativas; período/totais incoerentes; moeda fora do formato ISO 4217 alfa-3; pagamentos ou ledger sem correspondência; duplicidades; divergências de valor ou settlement; diferença financeira não zerada; reteste pendente; aprovação ausente; ou `PASS` sem cumprimento integral das regras.

O comando apenas valida a evidência fornecida. Não executa reconciliação, pagamento, chamada a PSP, mutação de ledger ou correção automática. A evidência deve conter somente referência operacional, sem dados pessoais, credenciais, payload PSP ou identificadores reais de clientes.

## Evidência TDD R8.4

O RED confirmou a ausência da opção `--sandbox-reconciliation-evidence` em 22 cenários. Após a implementação:

- direcionados R8.4: 22 aprovados, 47 assertions;
- todos os testes R8: 49 aprovados, 108 assertions;
- regressão: 524 aprovados, 3 ignorados, 2425 assertions;
- Pint: aprovado nos 3 arquivos PHP alterados;
- PHPStan: configuração ausente.

## R8.5 — Gate de UAT merchant/admin com evidência canônica

O gate conjunto recebe `--uat-evidence` (padrão: `storage/app/release-candidate/uat-evidence.json`) sob contrato JSON `1.0`, mas merchant e admin são avaliados e reportados independentemente. A evidência exige execução, ambiente `sandbox`/`test`/`staging`, referência, aprovação, resultado e blocos completos com escopo, totais, falhas, achados abertos, aceite, signatário e referência de notas. Validade padrão: 168 horas (`RELEASE_UAT_MAX_AGE_HOURS`).

A validação fail-closed bloqueia ausência, JSON inválido, versão incompatível, expiração/futuro, ambiente impróprio, bloco ausente, totais inválidos ou incoerentes, qualquer cenário falho, blocker/critical/high aberto, aceite não assinado, signatário/referências ausentes, escopo mínimo incompleto e `PASS` isolado. O comando apenas valida a evidência: não executa interface. O contrato não deve armazenar credenciais, tokens, dados pessoais, screenshots embutidos, payloads de clientes, segredos ou detalhes sensíveis de falhas.

Escopo merchant: login/sessão, cobrança, pagamentos/estados, webhooks, refunds quando expostos, settlement/saldo, API keys, isolamento do tenant, erros e dashboard. Escopo admin: login/sessão, RBAC, merchants, saldo/taxas/limites, settlement, gateways/credenciais, webhook admin, DLQ, auditoria e negações por permissão.

## Evidência TDD R8.5

O RED inicial confirmou a ausência da opção de UAT; após a implementação: direcionados R8.5, 20 aprovados e 50 assertions; todos R8, 69 aprovados e 158 assertions; regressão, 544 aprovados, 3 ignorados e 2475 assertions; Pint aprovado nos 3 arquivos PHP alterados; PHPStan sem configuração.

## R8.6 — Gate de rollback com evidência canônica

### Implementação técnica do gate

O gate `rollback` exige o booleano do checklist e uma evidência JSON versionada `1.0`, fornecida por `--rollback-evidence` (padrão: `storage/app/release-candidate/rollback-evidence.json`). A validade máxima configurável é 168 horas (`RELEASE_ROLLBACK_MAX_AGE_HOURS`). O comando somente lê e valida o artefato: não executa deploy, rollback, migrations, mutações de banco, limpeza de filas, reinício de workers, chamadas a PSP ou transações.

O contrato valida o envelope (`schema_version`, execução/ambiente/versões, início/fim/duração, referência, aprovação e resultado) e os subcontratos completos de aplicação, banco, workers, health e integridade financeira. A validação fail-closed bloqueia artefato ausente/malformado/incompatível/expirado, produção, versões ou datas incoerentes, rollback incompleto, restauração incompleta, erros, incompatibilidade de schema/migrations/fila, backup ou restore test ausente, perda de dados, workers incorretos, jobs perdidos/duplicados, health degradado, ledger desbalanceado e qualquer duplicidade, ausência ou divergência financeira. `PASS` isolado não libera.

### Evidência TDD e validação R8.6

O RED confirmou a ausência de `--rollback-evidence`; após a implementação e formatação: direcionados R8.6, 37 aprovados e 75 assertions; todos R8, 106 aprovados e 233 assertions; regressão completa única, 581 aprovados, 3 ignorados e 2550 assertions; Pint aprovado nos 3 arquivos PHP alterados; PHPStan não executado por ausência de configuração.

### Estado operacional, RC e go-live

A implementação técnica de todos os gates canônicos está concluída e o comando é funcional/fail-closed. Na execução com o estado local atual, `release:readiness` retornou código 1: `Checklist inválido: arquivo ausente ou ilegível`. Não foi criada evidência artificial. Permanecem ausentes no estado local o checklist canônico acessível e, portanto, a comprovação operacional conjunta de todos os artefatos, incluindo evidência real de rollback aprovada.

A existência dos validadores não constitui evidência operacional. A Release Candidate permanece bloqueada até que checklist e evidências reais, atuais e aprovadas sejam fornecidos e validados em conjunto. Go-live não está autorizado.

## Riscos restantes

Os contratos comprovam consistência da evidência recebida, mas não assinam criptograficamente sua origem; referência, aprovação e identidade dos signatários dependem do processo operacional. A segurança do rollback real depende de ensaio controlado não produtivo, backup verificável e validações externas referenciadas, ainda não presentes localmente.

## Próximo passo

Executar o ensaio operacional de rollback em sandbox/test/staging, produzir e aprovar a evidência canônica sem dados sensíveis, restaurar o checklist real e executar novamente `release:readiness` com todos os artefatos.
