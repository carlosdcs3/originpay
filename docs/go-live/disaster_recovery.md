# Disaster Recovery Validation Sign-off

**Ambiente:** Staging (Pre-Prod)
**Data de Assinatura:** 

## Critérios de Avaliação (PASS / FAIL)

- [ ] **RTO (Recovery Time Objective):** O tempo total desde a perda do banco até a subida do snapshot foi documentado e considerado aceitável pelo negócio.
- [ ] **RPO (Recovery Point Objective):** A perda de dados não comitados está perfeitamente alinhada com o último _Point-In-Time_ restaurado.
- [ ] **Integridade do Ledger (`verify-integrity`):** Validado com sucesso após o Restore. Nenhuma Hash HMAC rompida.
- [ ] **Reconciliação Financeira (`reconcile`):** Validado com sucesso após o Restore. Nenhum saldo órfão.
- [ ] **Dry-Run Rebuild:** Nenhuma carteira acusou erro de consistência entre transações antigas e Snapshot.
- [ ] **Smoke Test Pós-Restore:** API assumiu o tráfego e operou sem atritos no novo banco.

## Evidências
*(Forneça os logs da AWS RDS e a saída do console dos comandos de integridade após o Restore)*

## Status Final
[ ] PASS
[ ] FAIL

---
Assinatura: ___________________________
Cargo: Engenheiro(a) de Banco de Dados (DBA) / SRE
