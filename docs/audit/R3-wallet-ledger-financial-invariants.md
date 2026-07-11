# R3 — Wallet, Ledger e Invariantes Financeiras

Data: 2026-07-10  
Escopo: Wallet, Ledger, Withdraw, Settlement, Reconciliation, platform fees, gateway fees, locks/transações, concorrência e idempotência financeira.

## Nota de leitura obrigatória

Arquivos solicitados e encontrados/lidos:

- `docs/audit/R2.5-gateway-architecture.md`
- `docs/production/09-gateway-architecture.md`

Arquivos solicitados mas ausentes no workspace atual:

- `docs/audit/01-current-state.md`
- `docs/audit/02-security.md`
- `docs/audit/03-testing.md`
- `docs/audit/04-performance-resilience.md`
- `docs/production/02-financial-invariants.md`
- `docs/production/03-testing-strategy.md`
- `docs/production/05-high-availability.md`
- `docs/production/08-coding-standards.md`

A análise seguiu as regras disponíveis em `R2.5` e `09-gateway-architecture`: `app/Gateway` é a fronteira oficial PSP, providers não alteram saldo/ledger, e toda mutação financeira deve passar pelo domínio financeiro transacional/idempotente.

## 1. Fluxo financeiro mapeado

### 1.1 Cobrança paga → ledger → saldo

Fluxos atuais relevantes:

1. Webhook/gateway normaliza evento de cobrança paga.
2. `ProcessGatewayWebhookJob`/serviços de charge chegam ao domínio.
3. `App\Services\ChargeActionService::reprocessWebhook()` usa `WalletBalanceService::creditGateway()`.
4. `App\Listeners\CreditMerchantWalletOnChargeSuccess` ainda credita `wallet.balance` e `wallet.available_balance` diretamente e cria `Transaction` legado.
5. `WalletBalanceService::creditGateway()` atualiza:
   - `wallet_balances.available`
   - `wallet.balance`
   - `wallet.available_balance` após este R3
   - `wallet_transactions` append-only com `idempotency_key`.

Diagnóstico: há dois caminhos coexistentes para crédito de cobrança. O caminho via `WalletBalanceService` é o destino mínimo endurecido; o listener direto permanece risco legado.

### 1.2 Saldo → saque

Fluxos atuais relevantes:

- `WalletBalanceService::reserveWithdrawalFunds()` reserva saque movendo `wallet.available_balance` para `wallet.reserved_balance` e `wallet_balances.available` para `wallet_balances.blocked` sem reduzir `wallet.balance`.
- `WalletBalanceService::settleWithdrawalFunds()` liquida a reserva reduzindo `wallet.balance`, `wallet.reserved_balance` e `wallet_balances.blocked`, e aumentando `wallet.withdrawn_balance`.
- `WalletBalanceService::releaseWithdrawalFunds()` desfaz reserva.

Diagnóstico: o fluxo de reserva/liquidação é o mais aderente a invariantes de saque. O método legado `Wallet::debitGateway()` e serviços de settlement que chamam o model diretamente ainda contornam parte da trilha de `wallet_transactions`.

### 1.3 Saque → settlement → reconciliação

Fluxos atuais relevantes:

- `App\Services\SettlementActionService::forceSettle()` usa `WalletBalanceService::debitGateway()` e marca settlement como `settled`.
- `App\Services\Finance\SettlementActionService::pay()` chama `Wallet::debitGateway()` diretamente, cria `Transaction` legado e marca settlement como `SUCCEEDED`.
- `ReconcileWalletReservesCommand` existe para verificar reservas.
- `ReconcileLedgerCommand`/`VerifyLedgerIntegrity*` existem para auditoria de ledger.

Diagnóstico: settlement ainda tem caminhos duplicados. Um caminho usa serviço transacional; outro usa método do model e não cria `WalletTransaction`, dificultando reconciliação baseada no ledger/wallet_transactions.

### 1.4 Fees de plataforma/gateway

Evidências:

- `CreditMerchantWalletOnChargeSuccess` calcula fee fallback `1.5% + 0.30` e credita apenas net amount diretamente.
- `ChargeActionService` usa `$charge->net_amount`.
- `VirtualCardManager` calcula fee e cria dados transacionais próprios.

Diagnóstico: fees não estão uniformemente modeladas como lançamentos financeiros explícitos e reconciliáveis. Há risco de fee implícita sem ledger separado por receita, gateway cost e net merchant.

## 2. Invariantes financeiras atuais

Invariantes existentes/pretendidas observadas:

1. `WalletTransaction` é append-only via eventos Eloquent: update/delete lançam exceção.
2. `LedgerEntry` é append-only via eventos Eloquent: update/delete lançam exceção.
3. `WalletBalanceService` usa `DB::transaction()` e `lockForUpdate()` em wallet e wallet balance.
4. `LedgerService::debit()` valida `available_balance >= amount`.
5. `LedgerService` bloqueia uso novo de `SYSTEM_GENERAL` sem `legacy_call`.
6. `Wallet::fill()` remove campos de saldo em updates mass-assignment de modelos existentes.
7. `wallet_balances` possui unicidade por `(wallet_id, gateway_id)` nos testes/contrato runtime.
8. `webhook_events`/idempotência do R2 bloqueiam replay na entrada do webhook.

Invariantes endurecidas neste R3:

1. `WalletBalanceService` rejeita valores `<= 0` nos métodos financeiros principais/reserva/liquidação.
2. `WalletBalanceService::creditGateway()` tornou-se idempotente por `idempotency_key` e atualiza também `wallet.available_balance`.
3. `WalletBalanceService::debitGateway()`, `blockFunds()` e `releaseFunds()` passaram a consultar idempotência antes de mutar estado.
4. `debitGateway()` valida simultaneamente saldo disponível por gateway e saldo disponível/total da wallet.
5. Migração adicionada para unicidade de `wallet_transactions.idempotency_key`.

## 3. Pontos onde saldo pode ser alterado diretamente

Pontos encontrados:

- `app/Listeners/CreditMerchantWalletOnChargeSuccess.php`: altera `wallet.balance` e `wallet.available_balance` diretamente.
- `app/Models/Wallet.php`: métodos `creditGateway()` e `debitGateway()` alteram saldos diretamente no model, sem criar `wallet_transactions`.
- `app/Services/Finance/SettlementActionService.php`: chama `Wallet::debitGateway()` diretamente.
- `app/Jobs/Treasury/ReleaseRollingReserveJob.php`: altera `rolling_reserve_balance` e `available_balance` diretamente.
- `app/Services/ChargeService.php`: atribui `wallet.balance` com base em `available_balance` em trecho encontrado por busca.
- `WalletBalanceService::rebuildBalance()` recalcula `wallet.balance` por `wallet_transactions` e altera saldo; é operação administrativa/reconstrutiva, deve permanecer controlada.

Risco: estes pontos impedem afirmar que ledger/wallet_transactions é source of truth absoluto hoje.

## 4. Risco de race condition

Riscos identificados:

- `WalletBalance::firstOrCreate()` seguido de lock pode competir em criação concorrente se não houver unique real no banco para `(wallet_id, gateway_id)` em todos os ambientes.
- `WalletTransaction::creating()` busca último hash sem lock explícito da cadeia de transações; em criação concorrente para mesma wallet, a cadeia de hash pode bifurcar logicamente.
- `CreditMerchantWalletOnChargeSuccess` faz checagem de existência antes da transação e sem lock/índice único explícito em `transactions.trx_reference + trx_type`.
- `SettlementActionService::forceSettle()` valida status fora de lock do settlement; duas chamadas concorrentes podem tentar liquidar o mesmo settlement se a idempotência não for determinística.

Mitigações existentes/endurecidas:

- `WalletBalanceService` trava wallet e wallet balance com `lockForUpdate()`.
- Chaves idempotentes agora são consultadas em mais métodos e protegidas por índice único novo em `wallet_transactions`.

## 5. Risco de saldo negativo

Antes do R3:

- `WalletBalanceService::debitGateway()` validava apenas `wallet_balances.available`, não `wallet.available_balance`/`wallet.balance`.
- `blockFunds()` não validava saldo suficiente antes de subtrair.
- `releaseFunds()` não validava `blocked >= amount`.
- Métodos aceitavam valores `<= 0`, possibilitando inversão semântica.

Após R3:

- Débito valida gateway available, wallet available e wallet balance.
- Block valida gateway available e wallet available.
- Release valida gateway blocked.
- Métodos principais rejeitam `amount <= 0`.

Risco restante: caminhos diretos fora de `WalletBalanceService` ainda podem criar inconsistência.

## 6. Risco de dupla liquidação

Riscos:

- `SettlementActionService::forceSettle()` usa `Str::uuid()` como idempotency key; reexecução do mesmo settlement gera chave diferente.
- `ChargeActionService::reprocessWebhook()` também usa `Str::uuid()`; reprocessamento manual permitido em condição errada poderia duplicar crédito.
- `Finance\SettlementActionService::pay()` usa model direto sem `WalletTransaction` idempotente.

Correção mínima feita: camada financeira (`WalletBalanceService`) agora respeita idempotency key quando recebida e o banco terá índice único em `wallet_transactions.idempotency_key`. Correção pendente: callers devem usar chaves determinísticas, por exemplo `settlement:{id}:payout`, `charge:{id}:paid`, `withdrawal:{id}:settle`.

## 7. Testes existentes

Testes localizados:

- `tests/Feature/WalletBalanceRuntimeTest.php`
- `tests/Feature/GatewayLedgerTest.php`
- `tests/Feature/GatewayWebhookIdempotencyTest.php`
- comandos de reconciliação/verificação: `VerifyLedgerIntegrity*`, `ReconcileLedgerCommand`, `ReconcileWalletReservesCommand`.

Cobertura existente antes do R3:

- existência/contrato de `wallet_balances`.
- débito por gateway falha com saldo insuficiente.
- débito por gateway reduz available/balance.
- `WalletBalanceService` usa schema `available/pending/blocked`.

Testes adicionados/ajustados neste R3:

- Assert de sincronização de `wallet.available_balance` em crédito/débito via `WalletBalanceService`.
- `test_wallet_balance_service_is_idempotent_for_replayed_financial_event`.
- `test_wallet_balance_service_rejects_negative_or_zero_amounts`.

## 8. Correção mínima aplicada

Arquivos modificados:

- `app/Services/Financial/WalletBalanceService.php`
  - validação de amount positivo;
  - idempotência para credit/debit/block/release;
  - validação de saldo disponível/total no débito;
  - validação de saldo no bloqueio/liberação;
  - atualização de `wallet.available_balance` em crédito/débito/bloqueio/liberação.

- `database/migrations/2026_07_10_000002_harden_financial_idempotency.php`
  - adiciona unique index em `wallet_transactions.idempotency_key`.

- `tests/Feature/WalletBalanceRuntimeTest.php`
  - adiciona validações de available balance;
  - adiciona teste de replay/idempotência;
  - adiciona teste de rejeição de amount inválido.

- `docs/audit/R3-wallet-ledger-financial-invariants.md`
  - este relatório.

## 9. Testes executados

Comando tentado:

```bash
./vendor/bin/pest tests/Feature/WalletBalanceRuntimeTest.php
```

Resultado:

```text
/usr/bin/env: ‘php’: No such file or directory
```

Também foi tentado `php -l`, com mesmo bloqueio: `php` não está disponível no PATH do ambiente atual.

Status: testes não puderam ser executados neste ambiente por ausência de PHP.

## 10. Validação financeira

Validações esperadas após R3:

- Crédito replayado com a mesma `idempotency_key` retorna a transação existente e não incrementa saldo duas vezes.
- Débito só ocorre quando há saldo suficiente no recorte por gateway e na wallet agregada.
- Amount zero/negativo é rejeitado antes de abrir mutação financeira.
- `wallet.available_balance` acompanha crédito/débito gateway no serviço endurecido.
- `wallet_transactions.idempotency_key` passa a ter proteção estrutural contra duplicidade.

Limitação: como PHP/testes não executaram, a validação é estática e deve ser confirmada em CI/local com PHP.

## 11. Validação de concorrência

Garantias atuais:

- Mutação financeira em `WalletBalanceService` roda em `DB::transaction()`.
- Wallet e wallet balance são bloqueadas com `lockForUpdate()`.
- Índice único de idempotência reduz janela de corrida em replay financeiro.

Riscos restantes:

- Hash chain de `WalletTransaction` ainda usa busca do último registro sem lock por wallet.
- Callers com `Str::uuid()` como idempotency key não são idempotentes semanticamente.
- Fluxos diretos fora de `WalletBalanceService` ainda não têm lock/ledger uniforme.
- Settlement precisa travar a linha do settlement antes de validar status.

## 12. Riscos restantes e próximos passos obrigatórios

1. Migrar `CreditMerchantWalletOnChargeSuccess` para `WalletBalanceService::creditGateway()` com chave determinística `charge:{id}:paid` e gateway real.
2. Depreciar ou transformar `Wallet::creditGateway()`/`Wallet::debitGateway()` em wrappers seguros que criem `WalletTransaction`, ou remover callers.
3. Migrar `App\Services\Finance\SettlementActionService` para `WalletBalanceService` e chave `settlement:{id}:payout`.
4. Alterar `ChargeActionService` e `SettlementActionService` para chaves idempotentes determinísticas, não `Str::uuid()`.
5. Modelar platform fees/gateway fees como lançamentos explícitos no ledger/source of truth.
6. Adicionar índices únicos por referência financeira: charge paid, settlement payout, withdrawal reserve/settle/release.
7. Criar constraint/checks de banco para impedir saldos negativos onde o banco suportar.
8. Ajustar cadeia de hash de `WalletTransaction` para lock por wallet ou sequenciamento determinístico.
9. Unificar ledger: decidir formalmente se `ledger_entries` ou `wallet_transactions` é source of truth operacional; hoje ambos coexistem.

## 13. Conclusão

O núcleo financeiro foi endurecido no ponto de maior alavancagem (`WalletBalanceService`) com validação de valores, idempotência, proteção contra saldo negativo e índice único. A auditoria, porém, confirma que ainda há caminhos legados que alteram saldo diretamente. Portanto, o sistema está melhor protegido contra replay e saldo negativo nos fluxos que usam `WalletBalanceService`, mas ainda não cumpre integralmente a regra “ledger é source of truth financeiro” enquanto listeners/models/settlements diretos permanecerem ativos.
