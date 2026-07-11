<!-- ORIGINPAY PRODUCTION CONSTITUTION | generated 2026-07-10T10:14:39 | source: docs/audit | scope: documentation only -->

# 07 — Checklist de Release para Produção

## Regra
Todos os itens devem ser verificáveis. Marcar item sem evidência é proibido.

## Arquitetura
- [ ] Source of truth de gateway definido.
- [ ] Caminhos legados isolados ou removidos.
- [ ] Webhook pipeline único aprovado.
- [ ] DLQ única consolidada.
- [ ] Idempotência única consolidada.
- [ ] Módulos ativos/legados documentados.

## Segurança
- [ ] API keys hash-based; secrets não armazenados em claro.
- [ ] RBAC admin por ação validado.
- [ ] 2FA admin obrigatório.
- [ ] Webhooks com assinatura/timestamp/replay protection.
- [ ] CSRF/CORS/CSP/headers validados.
- [ ] Logs com masking validado.
- [ ] Uploads privados e validados.
- [ ] Dependency audit sem crítico/alto não aceito.
- [ ] Pentest ou security test suite executado.

## Financeiro
- [ ] Invariantes financeiras revisadas.
- [ ] Ledger imutável validado.
- [ ] Wallet não permite saldo negativo.
- [ ] Withdraw/settlement reconciliáveis.
- [ ] Refund/chargeback consistentes.
- [ ] Reconciliation automática verde.
- [ ] Nenhum ajuste manual sem audit log.

## Testes
- [ ] `php artisan test` verde.
- [ ] Coverage crítico >= 90% branch relevante.
- [ ] Testes concorrentes verdes.
- [ ] Contract tests API/webhook verdes.
- [ ] Security tests reproduzíveis verdes.
- [ ] Stress/soak sem divergência financeira.
- [ ] Migration fresh em testing verde.

## Performance e resiliência
- [ ] Horizon configurado por fila.
- [ ] Redis HA/monitorado.
- [ ] Circuit breaker real por provider.
- [ ] Retry/backoff/DLQ validados.
- [ ] Health live/ready/deep ativos.
- [ ] Timeouts configurados.
- [ ] Load test aprovado.

## Logs, métricas e alertas
- [ ] Request/correlation id em fluxos críticos.
- [ ] Métricas de PSP/webhook/queue/ledger/wallet.
- [ ] Alertas de DLQ, failed jobs, divergence, PSP errors.
- [ ] Dashboards operacionais revisados.
- [ ] Retenção LGPD definida.

## Backups e DR
- [ ] Backup criptografado configurado.
- [ ] Restore testado.
- [ ] RPO/RTO definidos.
- [ ] Runbook de reconstrução wallet pelo ledger.
- [ ] Reconciliation pós-restore testada.

## LGPD/PCI
- [ ] Dados pessoais minimizados.
- [ ] Logs mascaram PII.
- [ ] Retenção e exclusão definidas.
- [ ] PAN/CVV não armazenados sem escopo PCI formal.
- [ ] Tokenização de cartão validada.
- [ ] Acesso a dados sensíveis auditado.

## Deploy/Rollback
- [ ] Artefato versionado.
- [ ] Migrations revisadas para downtime/lock.
- [ ] Smoke tests definidos.
- [ ] Rollback testado.
- [ ] Plano de comunicação pronto.
- [ ] Janela de monitoramento definida.

## Aprovação final
- [ ] CTO/Tech Lead aprovado.
- [ ] Security aprovado.
- [ ] QA aprovado.
- [ ] SRE aprovado.
- [ ] Product/Operations aprovado.
- [ ] Zero críticos/altos abertos.
