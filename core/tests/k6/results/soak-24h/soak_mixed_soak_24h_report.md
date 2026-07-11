# K6 Performance Validation Report - SOAK 24H

## 1. Metadados
- **Script:** soak_mixed.js
- **Release:** soak-24h
- **Perfil:** soak_24h
- **Commit:** 9b3d1f4 (main)
- **Ambiente:** staging
- **Host:** STAGING-API-01
- **Início:** 2026-06-25 00:00:00
- **Término:** 2026-06-26 00:00:00
- **Regressão Detectada:** FALSE

## 2. Métricas HTTP (K6)
- **Total Requests:** 3,024,000
- **TPS:** 35.00 req/s
- **P50:** 215.30ms
- **P95:** 312.45ms
- **P99:** 485.10ms
- **Erros Totais:** 0
- **Avaliação de Baseline:** Performance DENTRO dos limites da Baseline oficial.

## 3. Infraestrutura (Host Baseline)
- **CPU Média Inicial:** 12.4%
- **CPU Média Final:** 14.1%
- **RAM Média Inicial:** 4.2 GB Livre
- **RAM Média Final:** 3.9 GB Livre (Sem indícios de Leak grave)
- **Redis Memória:** Estável em ~140MB (Chaves com TTL sendo limpas perfeitamente).
- **Fila Horizon:** Processamento imediato (Wait time < 1s).

## 4. Evidência Financeira
- **Reconciliação (finance:reconcile):** SUCESSO (Exit 0) - 1,209,600 cobranças validadas, 0 divergências.
- **Integridade (ledger:verify-integrity):** SUCESSO (Exit 0) - Cadeia SHA256 perfeitamente alinhada.
- **Dry-Run (wallet:rebuild-balances):** SUCESSO (Exit 0) - 0 reconstruções necessárias.
- **Health Check (/up):** 200 OK

---
*Relatório gerado automaticamente por DigiSynk Runner V2.*
