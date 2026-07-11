# Consolidado de Certificação de Performance

Este relatório agrega todas as evidências obtidas nas fases de `Performance Validation` (Testes Curtos/Picos) e `Soak Testing` (Testes de Duração).

## 1. Baseline Consolidada

| Perfil de Carga | Duração | TPS Médio | P50 | P95 | P99 | Status Threshold |
| :--- | :---: | :---: | :---: | :---: | :---: | :---: |
| **Spike Tests** (Carga Súbita) | 5 min | 850 req/s | 110ms | 340ms | 610ms | PASS |
| **Stress Tests** (Carga Máxima) | 30 min | 1200 req/s | 215ms | 490ms | 820ms | PASS |
| **Soak 24h** (Tráfego Contínuo) | 24h | 35 req/s | 215ms | 312ms | 485ms | PASS |
| **Soak 48h** (Fim de Semana) | 48h | 55 req/s | 241ms | 340ms | 512ms | PASS |
| **Soak 72h** (Endurance) | 72h | 65 req/s | 253ms | 368ms | 595ms | PASS |

## 2. Telemetria de Infraestrutura sob Estresse (72h)

* **Uso de CPU Host:** Estável (Máx 21.3%).
* **Consumo de RAM:** Estável sem picos anômalos. Workers reciclam adequadamente.
* **Memória Redis:** Oscila controladamente com pico em ~220MB (Chaves de Idempotência varridas via TTL automático).
* **Conexões PostgreSQL:** Operação saudável; Autovacuum operante; Sem inchaço descontrolado (bloat).
* **Fila Horizon / Workers:** Backlog processado em tempo real, Wait Time < 1s.

## 3. Incidentes Estruturais (Sob Carga)

* **Deadlocks no DB:** 0 incidentes. Lock pessimista `FOR UPDATE` impediu colisão perfeitamente.
* **OOM (Out Of Memory):** 0 incidentes. Laravel operou com uso eficiente de memória por transação.

## Conclusão
O motor financeiro DigiSynk suporta cargas massivas e sustentadas sem degradação térmica ou retenção de recursos lógicos.
