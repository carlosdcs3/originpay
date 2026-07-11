# Deploy Pipeline Validation

Este documento formaliza os bloqueios automáticos do CI/CD (Continuous Integration / Continuous Deployment) da DigiSynk, garantindo que nenhum código perigoso suba para Produção.

## Fluxo Obrigatório CI/CD
| Estágio da Pipeline | Condição de Aprovação | Condição de Falha (Rollback) |
| :--- | :--- | :--- |
| **1. Build & Lint** | Sintaxe Limpa (Sem warnings graves) | Falha no PHPStan / ESLint / Prettier |
| **2. Static Analysis** | SonarQube OK, Dependências Seguras | Vulnerabilidade Alta encontrada (Trivy/Composer Audit) |
| **3. Unit Tests** | 100% Core coverage | Qualquer falha de Teste Unitário |
| **4. Integration Tests** | Testes de Banco/Gateway limpos | Timeout de serviço externo / Query malformada |
| **5. Financial Tests** | Prova matemática (Ledger/Wallet) íntegra | Colisão de Race Condition simulada falhar |
| **6. K6 / Soak / Chaos** | TPS e Latência mantidas; Resistência provada | Threshold K6 ultrapassado; Memory Leak detectado |
| **7. Backup (Pre-Deploy)**| Snapshot tirado pela AWS com sucesso | Falha de API AWS no Snapshot (Impede Migration) |
| **8. Migration** | Transações DDL completas sem lock preso | Erro de Foreign Key / Data Truncation |
| **9. Zero Downtime Deploy**| Load Balancer vira o tráfego sem 502 Bad Gateway | Erro na esteira (Envoyer/Forge) |
| **10. Smoke Tests** | Rotas Críticas e `/up` = 200 OK | Retorno `500 Internal Server Error` |
| **11. Release** | Tag de versão consolidada no Git | Pipeline cancelado antes deste passo |

## Automação de Rollback Pós-Deploy
O deploy acionará `Rollback Automático` no Load Balancer se:
- O Health Check falhar (`/up` 5xx).
- Latência média aumentar subitamente.
- A taxa de erro (Error Rate) ultrapassar o _threshold_ tolerado (1%).
- Smoke Test pós-virada falhar.

---
**Status de Homologação da Pipeline:** [ PASS ]
