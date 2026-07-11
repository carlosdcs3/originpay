# K6 Performance Validation Report - SOAK 72H

## 1. Metadados
- **Script:** soak_mixed.js
- **Release:** soak-72h
- **Perfil:** soak_72h
- **Commit:** 9b3d1f4 (main)
- **Ambiente:** staging
- **Host:** STAGING-API-01
- **Início:** 2026-06-29 00:00:00
- **Término:** 2026-07-02 00:00:00
- **Regressão Detectada:** FALSE

## 2. Métricas HTTP (K6)
- **Total Requests:** 16,848,000
- **TPS:** 65.00 req/s
- **P50:** 253.90ms
- **P95:** 368.10ms
- **P99:** 595.40ms
- **Erros Totais:** 0
- **Avaliação de Baseline:** Performance DENTRO dos limites da Baseline oficial.

## 3. Infraestrutura (Host Baseline)
- **CPU Média Inicial:** 15.0%
- **CPU Média Final:** 21.3% (Aumento tolerável devido ao crescimento da tabela de Ledger)
- **RAM Média Inicial:** 3.7 GB Livre
- **RAM Média Final:** 3.4 GB Livre (Laravel Workers reciclados adequadamente sem memory leak)
- **Redis Memória:** Máximo atingido: ~220MB. Idempotency chaves limpas via TTL de 24h sem falhas.
- **PostgreSQL Bloat:** Autovacuums dispararam 12 vezes nas tabelas `ledger_entries` e `transactions`. Lock Pessimista nunca reteve fila por mais de 50ms.
- **Fila Horizon:** Master Supervisor perfeitamente equilibrado, 0 DLQ.

## 4. Evidência Financeira
- **Reconciliação (finance:reconcile):** SUCESSO (Exit 0) - 6,739,200 cobranças validadas, 0 divergências com as Wallets e PSP simulados.
- **Integridade (ledger:verify-integrity):** SUCESSO (Exit 0) - Toda a extensão dos blocos de 72h foi confirmada através das hashes SHA256. 
- **Dry-Run (wallet:rebuild-balances):** SUCESSO (Exit 0) - Nenhuma carteira perdeu precisão de ponto flutuante.
- **Health Check (/up):** 200 OK

---
*Relatório gerado automaticamente por DigiSynk Runner V2.*
*Nota de Certificação: O sistema provou que suporta alto fluxo ininterrupto, sem degradação prejudicial ou desvio de integridade por 3 dias inteiros.*
