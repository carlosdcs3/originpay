<!-- ORIGINPAY PRODUCTION CONSTITUTION | generated 2026-07-10T10:14:39 | source: docs/audit | scope: documentation only -->

# 06 — Operational Runbook

## Status
Procedimentos operacionais obrigatórios para incidentes, deploy e manutenção. Executar com registros em incident log e comunicação apropriada.

## Gateway/PSP indisponível
1. Confirmar alertas: timeout/error rate/circuit breaker.
2. Verificar se é provider único ou múltiplos.
3. Abrir incidente com provider, operação afetada e início.
4. Ativar circuit breaker/open se erro alto.
5. Pausar criação de cobranças/saques no provider afetado se necessário.
6. Garantir que webhooks continuam sendo persistidos.
7. Rodar reconciliation após normalização.
8. Comunicar merchants afetados.

## Webhook parado ou com falhas
1. Verificar health, logs e fila `webhooks_ingestion`.
2. Verificar assinatura/rejeições por provider.
3. Verificar DLQ count/age.
4. Se fila travada, pausar workers problemáticos sem perder eventos persistidos.
5. Reprocessar DLQ em batches pequenos e idempotentes.
6. Rodar reconciliation PSP para cobrir eventos perdidos.
7. Nunca marcar evento como resolvido sem confirmação.

## Fila travada
1. Verificar Horizon e failed jobs.
2. Identificar fila e job que bloqueia.
3. Pausar fila periférica antes de fila financeira se houver contenção.
4. Aumentar workers apenas se DB/Redis suportam.
5. Mover jobs venenosos para DLQ com auditoria.
6. Reprocessar após correção validada.

## Redis indisponível
1. Confirmar impacto: queue, cache, locks, sessions.
2. Se locks/idempotência dependem de Redis e DB não substitui, ativar read-only financeiro.
3. Evitar operações que exigem lock distribuído.
4. Restaurar Redis/cluster.
5. Validar Horizon, failed jobs e locks órfãos.
6. Rodar reconciliation.

## Banco lento ou indisponível
1. Ativar status degradado/readiness false.
2. Bloquear operações financeiras se transações/locks não confiáveis.
3. Verificar locks longos, queries lentas, conexões e storage.
4. Escalar DBA/infra.
5. Após recuperação, executar health, jobs pendentes e reconciliation.

## Incidente financeiro
1. Declarar severidade máxima se há risco de perda/duplicidade.
2. Ativar emergency read-only para fluxos afetados.
3. Preservar logs, payloads, ledger e snapshots.
4. Identificar janela, merchants, providers, event ids.
5. Rodar reconciliation manual controlada.
6. Corrigir via lançamentos compensatórios, nunca editando ledger.
7. Produzir postmortem com testes regressivos obrigatórios.

## Rollback de funcionalidade
1. Confirmar que rollback não reintroduz bug financeiro/security.
2. Parar workers da versão nova se necessário.
3. Drenar ou pausar filas com jobs incompatíveis.
4. Reverter deploy por artefato versionado.
5. Rodar migrations rollback apenas se seguro e testado; preferir forward fix para schema.
6. Rodar smoke tests e reconciliation.

## Deploy
1. Checklist release aprovado.
2. Backup/snapshot quando houver migração crítica.
3. Migrations avaliadas para lock/downtime.
4. Deploy canário quando possível.
5. Smoke tests: health, login, API auth, charge sandbox, webhook sandbox, queue.
6. Monitorar métricas por janela mínima.
7. Plano de rollback pronto.

## Rotação de segredos
1. Criar novo segredo/certificado sem remover antigo.
2. Ativar período de overlap controlado.
3. Validar chamadas/webhooks com novo segredo.
4. Revogar antigo.
5. Auditar logs para uso tardio do antigo.
6. Registrar rotação.

## Troca de certificado PSP
1. Validar certificado em staging/sandbox.
2. Armazenar fora do web root com permissões mínimas.
3. Atualizar secret manager/env.
4. Reiniciar workers se necessário.
5. Testar OAuth/status/charge/refund/webhook.
6. Monitorar erros de TLS/OAuth.

## Recuperação pós-incidente
- Reconciliation completa.
- DLQ zerada ou justificada.
- Failed jobs analisados.
- Merchants impactados notificados.
- Postmortem sem culpa.
- Testes regressivos criados.
- Documentação atualizada.
