# Chaos Engineering & Disaster Recovery (Game Days)
**DigiSynk SRE Playbooks - Fase 3**

Este documento rege a execução de exercícios de Chaos Engineering em ambiente de Staging (isolado). Nenhum teste desta natureza deve ser executado contra a Base de Dados de Produção, exceto sob supervisão explícita de Engenharia de Confiabilidade (Game Day Global).

## Regras de Engajamento
1. **Nunca** rodar `FLUSHALL` em Redis compartilhado que atenda mais de um ambiente.
2. **Sempre** gerar um Snapshot do PostgreSQL (`AWS RDS Snapshot` ou similar) antes do início do Game Day.
3. Registrar horário exato de início e fim.
4. Qualquer falha sistêmica (Corrupção de Ledger ou Duplicidade de Crédito) interrompe o teste imediatamente e gera um P1 (Incident Response).

---

## Estrutura do Relatório de Evidência (Template Obrigatório)
Para cada Playbook executado, um engenheiro SRE deve preencher:
- **Hipótese:** O que estamos testando.
- **Procedimento:** Como o ataque será feito.
- **Horário:** [YYYY-MM-DD HH:mm:ss]
- **Carga Ativa:** (Ex: `soak_mixed.js` em 300 VUs)
- **Falha Injetada:** Comando exato executado.
- **Comportamento Esperado:** ...
- **Comportamento Observado:** ...
- **Alertas Emitidos:** (Datadog, Slack, PagerDuty)
- **Impacto Financeiro:** ...
- **Resultado `finance:reconcile`:** ...
- **Resultado `ledger:verify-integrity`:** ...
- **Conclusão:** (Aprovado / Reprovado com Ticket de Correção)

---

## Playbook A: Queda do Banco Primário
- **Objetivo:** Validar rollback transacional, failover e ausência de corrupção.
- **Injetor:** `tests/chaos/playbook_a_db_kill.ps1`
- **Esperado:** K6 acusa 500/503. Após failover, recupera 200 OK. Nenhuma transação fica "presa" no estado pendente caso não conste no Ledger.

## Playbook B: Perda de Redis
- **Objetivo:** Validar que Redis é apenas cache rápido, não fonte final de idempotência.
- **Injetor:** `tests/chaos/playbook_b_redis_flush.ps1`
- **Esperado:** `ProcessedEvent` (no Postgres) segura a onda. Nenhum crédito duplicado. Emissão de `PlatformIncident` de replay.

## Playbook C: PSP Timeout / Isolamento
- **Objetivo:** Validar Circuit Breaker e Rolling Reserve.
- **Injetor:** `tests/chaos/playbook_c_network_isolate.ps1`
- **Esperado:** Workers lançam falha após timeout externo. Saldo cativo (`escrow`) não vira pó e pode ser devolvido/reconciliado futuramente.

## Playbook D: Worker Crash
- **Objetivo:** Validar resiliência do Horizon sob SIGKILL brusco.
- **Injetor:** `tests/chaos/playbook_d_worker_crash.ps1`
- **Esperado:** Nenhuma transação comitada pela metade no Ledger. Job volta para fila de Retries ou cai em DLQ.

## Playbook E: Storage/S3 Indisponível
- **Objetivo:** Validar que falha em uploads/exportações não para o motor financeiro.
- **Injetor:** `tests/chaos/playbook_e_storage_down.ps1`
- **Esperado:** APIs core continuam saudáveis. Jobs de arquivo falham p/ DLQ isoladamente.

## Playbook F: Horizon/Fila Travada
- **Objetivo:** Validar acúmulo e ingestão resiliente.
- **Injetor:** `tests/chaos/playbook_f_horizon_halt.ps1`
- **Esperado:** Webhooks entram no Redis saudáveis (retorno rápido de 200 da API). A fila visivelmente incha. Ao ligar o Horizon, processamento corre sem gargalo financeiro/duplicidade.

---
## Exercício de Disaster Recovery (DR) Completo
1. Engatilhar K6 (Estresse Elevado).
2. Forçar destruição do Cluster RDS (Staging).
3. Monitorar pânico e perda de conectividade (K6 caindo).
4. Acionar Restore via Snapshot (Point-in-Time).
5. Virar DNS da API pro novo banco.
6. Rodar K6 (Tráfego Normal).
7. Rodar `finance:reconcile`, `ledger:verify-integrity` e `wallet:rebuild-balances --dry-run`.
**Critério de Sucesso Final:** Zero perda irreversível de saldo de cliente do que já havia comitado e Hash HMAC perfeitamente linear até o ponto exato da queda.
