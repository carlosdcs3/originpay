<!-- ORIGINPAY PRODUCTION CONSTITUTION | generated 2026-07-10T10:14:39 | source: docs/audit | scope: documentation only -->

# 02 — Invariantes Financeiras

## Status crítico
Este é o documento mais importante para qualquer implementação financeira. Nenhuma alteração em pagamentos, wallet, ledger, fees, settlement, withdrawal, refunds, chargebacks ou webhooks pode ser aprovada se violar uma invariante abaixo.

## Princípio absoluto
A OriginPay nunca pode criar dinheiro, perder dinheiro, duplicar dinheiro, ocultar divergência ou deixar uma mutação financeira sem trilha auditável e reconciliável.

## Invariantes fundamentais
### FI-01 — Conservação de valor
Para toda mutação financeira, a soma das entradas e saídas deve fechar considerando taxas, reservas, chargebacks, refunds e ajustes formalmente registrados.

### FI-02 — Ledger é autoridade contábil
Saldo de wallet, settlement, reports e dashboards derivam do ledger ou devem ser reconciliáveis com ele. Se wallet e ledger divergem, ledger auditado prevalece até investigação.

### FI-03 — Toda entrada possui contrapartida
Nenhum crédito pode existir sem origem: PSP event, transferência interna, ajuste administrativo aprovado ou migração auditada. Nenhum débito pode existir sem destino/razão.

### FI-04 — Ledger imutável
Ledger entries não devem ser editadas ou removidas. Correções são lançamentos reversos/compensatórios com referência ao evento original.

### FI-05 — Saldo disponível nunca negativo
`available_balance` nunca pode ficar negativo. Qualquer tentativa deve falhar antes do side effect externo ou ficar em estado reversível controlado.

### FI-06 — Saldo bloqueado/reservado não é utilizável
Valores em pending, blocked, reserved ou rolling reserve não podem ser usados para saque, transferência ou settlement até liberação formal.

### FI-07 — Saque nunca excede saldo disponível
Withdraw/payout deve validar saldo disponível, limites, KYC, antifraude, transaction password e restrições antes de reservar/debitar.

### FI-08 — Webhook nunca altera saldo duas vezes
Mesmo provider event id/txid não pode gerar side effect financeiro duplicado. Replays devem retornar idempotentemente.

### FI-09 — Chargeback/refund não quebra consistência
Chargeback/refund deve lançar reversões, taxas e retenções de forma explícita. Estado da charge, ledger, wallet e settlement devem permanecer reconciliáveis.

### FI-10 — Rollback não deixa estado parcial
Falhas no meio de fluxo financeiro devem terminar em estado consistente: pending, failed, compensating_required ou completed. Nunca estado ambíguo sem alerta.

### FI-11 — Chamadas externas não ficam dentro de transação longa
Transações de banco devem proteger estado local. Chamadas PSP devem ocorrer em estados intermediários seguros, com idempotência e compensação.

### FI-12 — Toda mutação financeira é idempotente
Repetir a mesma operação autorizada com mesma chave/evento não pode alterar resultado financeiro após a primeira execução.

### FI-13 — Ordem de eventos não pode corromper estado
Eventos fora de ordem devem ser ignorados, enfileirados, reconciliados ou tratados por state machine; nunca regredir estado financeiro final sem regra explícita.

### FI-14 — Fees são determinísticas e auditáveis
Taxas devem ser calculadas por configuração versionada, com snapshot no momento da transação. Mudança futura de taxa não altera transações passadas.

### FI-15 — Settlement é sempre reconciliável
Cada settlement deve ter origem em charges elegíveis, deduções, reservas, taxas, payouts e confirmação externa rastreáveis.

### FI-16 — Multi-gateway balance fecha por provider
Se há saldo por gateway/provider, soma por provider deve fechar com saldo global e ledger segmentado.

### FI-17 — Sandbox não mistura com produção
Eventos, credenciais, charges e ledger de sandbox nunca podem afetar saldo de produção.

### FI-18 — Admin não cria dinheiro informalmente
Ajustes manuais exigem permissão específica, justificativa, dual control quando aplicável, ledger entries compensatórias e audit log.

### FI-19 — Dados monetários usam precisão adequada
Valores monetários não devem depender de float para cálculo crítico. Usar decimal/integer minor units ou biblioteca Money. Arredondamento deve ser definido por moeda e registrado.

### FI-20 — Reconciliação é obrigatória
Divergências entre PSP, charges, ledger, wallet e settlement devem ser detectadas por jobs e alertas. Divergência crítica bloqueia operações afetadas.

## Invariantes por fluxo
### Charge criada
- Deve ter merchant, ambiente, moeda, valor, método, status inicial e referência idempotente.
- Valor não pode ser alterado após exposição ao cliente/PSP sem cancelar e recriar.
- Charge expirada não pode ser paga sem regra explícita de PSP/reconciliação.

### Charge paga
- Deve haver prova: webhook validado, consulta PSP ou reconciliação.
- Deve gerar exatamente uma transição para paid/succeeded.
- Deve gerar ledger entries correspondentes exatamente uma vez.

### Refund
- Não pode exceder valor capturado líquido de refunds anteriores.
- Deve gerar ledger reversal/adjustment.
- Deve atualizar charge/refund state e settlement eligibility.

### Withdrawal/Payout
- Deve reservar/debitar antes de solicitar PSP conforme state machine.
- Falha PSP deve liberar reserva ou criar compensação conforme estado.
- Sucesso PSP deve fechar ledger e settlement.

### Chargeback/Dispute
- Deve bloquear/reservar fundos quando necessário.
- Evidências e prazos são auditáveis.
- Resultado deve gerar reversão, liberação ou perda reconhecida.

### Settlement
- Deve selecionar somente transações elegíveis.
- Deve descontar fees, reserves, refunds, disputes e chargebacks.
- Deve possuir relatório rastreável por item.

## Controles obrigatórios de implementação
- `DB::transaction` em mudanças locais atômicas.
- `lockForUpdate` ou lock distribuído onde há concorrência em saldo.
- Unique indexes para event id, idempotency key e referências externas críticas.
- State machines com transições permitidas.
- Audit log para toda ação manual.
- Testes concorrentes para cada mutação financeira.

## Critério de aceite para qualquer mudança financeira
1. Invariantes afetadas listadas no PR/plano.
2. Testes unitários e feature cobrindo sucesso e falha.
3. Teste de concorrência quando houver saldo/evento externo.
4. Teste idempotente/replay quando houver API/webhook/job.
5. Reconciliation impact documentado.
6. Observabilidade e alertas atualizados.
7. Nenhum crítico/alto de segurança aberto no fluxo.
