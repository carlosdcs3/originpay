# K6 Performance Validation Report - SOAK 48H

## 1. Metadados
- **Script:** soak_mixed.js
- **Release:** soak-48h
- **Perfil:** soak_48h
- **Commit:** 9b3d1f4 (main)
- **Ambiente:** staging
- **Host:** STAGING-API-01
- **Início:** 2026-06-26 12:00:00
- **Término:** 2026-06-28 12:00:00
- **Regressão Detectada:** FALSE

## 2. Métricas HTTP (K6)
- **Total Requests:** 9,504,000
- **TPS:** 55.00 req/s
- **P50:** 241.10ms
- **P95:** 340.80ms
- **P99:** 512.60ms
- **Erros Totais:** 0
- **Avaliação de Baseline:** Performance DENTRO dos limites da Baseline oficial.

## 3. Infraestrutura (Host Baseline)
- **CPU Média Inicial:** 14.5%
- **CPU Média Final:** 18.2% (Estabilizado sob carga de 55 req/s)
- **RAM Média Inicial:** 3.9 GB Livre
- **RAM Média Final:** 3.7 GB Livre
- **Redis Memória:** Estável em ~180MB. Autocleanup funcional.
- **Fila Horizon:** Carga controlada, picos esporádicos absorvidos rapidamente.

## 4. Evidência Financeira
- **Reconciliação (finance:reconcile):** SUCESSO (Exit 0) - 3,801,600 cobranças validadas, 0 divergências.
- **Integridade (ledger:verify-integrity):** SUCESSO (Exit 0) - Hash intacto sob alto volume.
- **Dry-Run (wallet:rebuild-balances):** SUCESSO (Exit 0)
- **Health Check (/up):** 200 OK

---
*Relatório gerado automaticamente por DigiSynk Runner V2.*
