# R4 — Settlement e Reconciliação

Data: 2026-07-10  
Escopo: settlement, reconciliation, rolling reserve, withdrawals, ledger entries, wallet transactions, platform fees, gateway fees, jobs de liberação e conciliação de provedor.

## 1. Documentação base lida

Conforme R3.2, a documentação canônica está em `../docs` quando a sessão está dentro de `core`.

Arquivos lidos antes da implementação:

- `../docs/audit/R3.2-docs-canonicalization.md`
- `../docs/audit/R3-wallet-ledger-financial-invariants.md`
- `../docs/audit/R2-webhooks-dlq-idempotency.md`
- `../docs/audit/R2.5-gateway-architecture.md`
- `../docs/production/02-financial-invariants.md`
- `../docs/production/05-high-availability.md`
- `../docs/production/08-coding-standards.md`
- `../docs/production/09-gateway-architecture.md`

Regras aplicadas:

- documentação nova gerada em `../docs`;
- settlement idempotente;
- alterações financeiras com `DB::transaction` e locks;
- não quebrar compatibilidade legada sem plano;
- providers/gateway não alteram saldo diretamente;
- ledger/wallet transactions devem ser reconciliáveis.

## 2. Fluxo settlement/reconciliation atual mapeado

### 2.1 Settlement administrativo legado

Arquivo:

- `app/Services/SettlementActionService.php`

Fluxo antes do R4:

1. Recebia `Settlement`.
2. Validava status fora de lock.
3. Usava `walletId = 1` fixo.
4. Chamava `WalletBalanceService::debitGateway()`.
5. Usava `Str::uuid()` como `idempotency_key`.
6. Marcava settlement como `settled`.

Riscos antes do R4:

- idempotência semântica inexistente, pois cada execução usava UUID novo;
- race condition entre validação de status e gravação;
- risco de debitar wallet errada por `walletId = 1`;
- dupla liquidação possível em chamadas concorrentes/replay.

### 2.2 Settlement financeiro legado

Arquivo:

- `app/Services/Finance/SettlementActionService.php`

Fluxo antes do R4:

1. Validava status fora de lock.
2. Abria transação.
3. Buscava wallet sem `lockForUpdate` inicialmente.
4. Chamava `Wallet::debitGateway()` diretamente.
5. Criava `Transaction` legado.
6. Marcava settlement como `SUCCEEDED`.

Riscos antes do R4:

- bypass de `WalletBalanceService` e `WalletTransaction`;
- sem chave idempotente determinística;
- sem lock na linha de settlement antes da decisão de liquidar;
- criação de `Transaction` legada duplicável em reexecução.

### 2.3 Rolling reserve

Arquivo:

- `app/Jobs/Treasury/ReleaseRollingReserveJob.php`

Fluxo atual:

1. Busca reservas `HELD` com `release_at <= now()`.
2. Para cada reserva, abre `DB::transaction`.
3. Recarrega reserva com `lockForUpdate()`.
4. Se status não for `HELD`, retorna idempotentemente.
5. Trava wallet com `lockForUpdate()`.
6. Valida `rolling_reserve_balance >= amount`.
7. Move saldo de `rolling_reserve_balance` para `available_balance`.
8. Marca reserva como `RELEASED`.
9. Cria duas `LedgerEntry` para release.

Diagnóstico:

- O job já possui proteção básica contra dupla liberação via lock e status.
- Ainda há alteração direta de saldos dentro do job, mas dentro de transação/lock.
- A reconciliação deve considerar `rolling_reserve_balance`, pois `ReconcileWalletReservesCommand` atualmente verifica apenas `balance = available_balance + reserved_balance`.

### 2.4 Reconciliation

Arquivos relevantes:

- `app/Services/Financial/FinancialReconciliationService.php`
- `app/Services/Finance/ReconciliationService.php`
- `app/Console/Commands/Reconciliation/ReconcileWalletReservesCommand.php`
- `app/Console/Commands/Reconciliation/ReconcileEfiSettlementCommand.php`
- `app/Console/Commands/RunFinancialReconciliation.php`

Diagnóstico:

- `FinancialReconciliationService` compara charges pagas com `WalletTransaction`, mas usa tipo `charge` enquanto o fluxo R3 usa `charge_payment`, criando risco de falso negativo.
- `Finance\ReconciliationService` compara `LedgerEntry` com `Wallet`, mas o sistema ainda tem `WalletTransaction` como trilha operacional paralela.
- `ReconcileEfiSettlementCommand` é mock/stub e não reconcilia extrato real do provedor.
- `ReconcileWalletReservesCommand` é read-only e auditável via anomalies/CSV, mas sua equação precisa evoluir para incluir reserved/rolling reserve/blocked conforme modelo final.

## 3. Alterações diretas de saldo identificadas

Pontos relevantes para R4:

- `app/Services/Finance/SettlementActionService.php` chamava `Wallet::debitGateway()` diretamente antes do R4.
- `app/Jobs/Treasury/ReleaseRollingReserveJob.php` altera `rolling_reserve_balance` e `available_balance` diretamente, porém com transação/lock e status idempotente.
- `app/Models/Wallet.php::creditGateway()` e `Wallet::debitGateway()` continuam como caminhos legados de alteração direta.
- `app/Listeners/CreditMerchantWalletOnChargeSuccess.php` ainda altera `wallet.balance` e `wallet.available_balance` diretamente, já apontado no R3.
- `WalletBalanceService::rebuildBalance()` altera saldo de forma administrativa/reconstrutiva.

Correção mínima aplicada no R4:

- Settlement financeiro deixou de usar `Wallet::debitGateway()` e passou a usar `WalletBalanceService::debitGateway()` com chave idempotente determinística.

## 4. Riscos de dupla liquidação identificados

Antes do R4:

- `SettlementActionService::forceSettle()` usava `Str::uuid()` como idempotency key.
- `Finance\SettlementActionService::pay()` não tinha idempotency key de wallet transaction.
- Ambos validavam settlement sem travar a linha antes da decisão de liquidar.

Após R4:

- Ambos os serviços usam chave determinística:

```text
settlement:{settlement_id}:payout
```

- Ambos travam a linha de `settlements` com `lockForUpdate()` antes de decidir.
- Reexecução de settlement já liquidado retorna sem novo débito.
- O débito passa por `WalletBalanceService`, que reaproveita `WalletTransaction` existente pela idempotency key.

Risco restante:

- A migration de índice único em `wallet_transactions.idempotency_key` criada no R3 ainda aparece como pending em `migrate:status` até ser aplicada no ambiente alvo.

## 5. Riscos de race condition identificados

Riscos existentes:

- Settlement concorrente sem lock podia passar duas vezes pela checagem de status.
- Callers legados ainda podem chamar `Wallet::debitGateway()` diretamente.
- `WalletTransaction::creating()` ainda encadeia hash buscando última transação sem lock explícito da cadeia.
- Reconciliation e scan jobs podem ler snapshots enquanto mutações ocorrem; como são read-only, devem ser tratados como detecção eventual e não como correção automática.

Mitigação aplicada:

- `SettlementActionService` e `Finance\SettlementActionService` agora usam `DB::transaction()` e `lockForUpdate()` em settlement.
- Wallet usada no settlement é buscada por `user_id` real do settlement e travada antes do débito.
- `WalletBalanceService` continua travando wallet e wallet balance no débito.

## 6. Pontos sem lock/transação

Pontos observados:

- Antes do R4, validações de settlement eram feitas fora de lock.
- `FinancialReconciliationService` é read-only e não precisa lock para mutação, mas deve registrar divergências de forma auditável.
- `ReconcileEfiSettlementCommand` é stub e não possui persistência/auditoria real de divergências.
- `CreditMerchantWalletOnChargeSuccess` ainda verifica duplicidade antes da transação e altera saldo diretamente.

Correção R4:

- Settlement administrativo e settlement financeiro passam a executar decisão + débito + status dentro da transação.

## 7. Testes existentes listados

Testes relacionados encontrados:

- `tests/Feature/WalletBalanceRuntimeTest.php`
- `tests/Feature/GatewayLedgerTest.php`
- `tests/Feature/LegacyPaymentWithdrawalTest.php`
- `tests/Feature/WalletIntegrityTest.php`
- `tests/Feature/KycAndOperationalLimitsTest.php` — cobre `ReleaseRollingReserveJob`.
- `tests/Feature/ChargeServicePlatformFeeTest.php`
- `tests/Feature/FeesPilotActionTest.php`
- `tests/Feature/PlatformFee*Test.php`

Comandos/serviços de reconciliação existentes:

- `reconcile:wallet-reserves`
- `reconcile:efi-settlement`
- `finance:reconcile`
- `fee:reconcile`
- `ledger:reconcile`

## 8. Correção mínima compatível aplicada

Arquivos modificados:

### `app/Services/SettlementActionService.php`

Mudanças:

- adiciona transação envolvendo todo o settlement;
- trava `settlements` com `lockForUpdate()`;
- permite replay idempotente se status já for `settled`;
- remove `walletId = 1` e usa wallet do `user_id` do settlement;
- usa chave determinística `settlement:{id}:payout`;
- passa metadata auditável para `WalletTransaction`;
- grava idempotency key e admin id em `settlement.metadata`.

### `app/Services/Finance/SettlementActionService.php`

Mudanças:

- injeta `WalletBalanceService`;
- trava settlement com `lockForUpdate()`;
- usa `WalletBalanceService::debitGateway()` em vez de `Wallet::debitGateway()`;
- usa chave determinística `settlement:{id}:payout`;
- evita criar `Transaction` legada duplicada buscando por `trx_reference`;
- grava `wallet_transaction_id` em `settlement.metadata`;
- mantém compatibilidade com status legado `pending`, `settled` e enum/value financeiro.

### `tests/Feature/SettlementReconciliationRuntimeTest.php`

Novo teste feature/runtime para settlement.

Cobertura:

- settlement forçado é idempotente e debita apenas uma vez;
- settlement com saldo insuficiente falha sem alteração parcial de status e sem criar wallet transaction.

## 9. Testes executados com PHP absoluto do Laragon

PHP usado:

```text
E:/laragon/bin/php/php-8.3.30-Win32-vs16-x64/php.exe
```

### 9.1 Syntax check

Comando:

```bash
"E:/laragon/bin/php/php-8.3.30-Win32-vs16-x64/php.exe" -l app/Services/SettlementActionService.php && \
"E:/laragon/bin/php/php-8.3.30-Win32-vs16-x64/php.exe" -l app/Services/Finance/SettlementActionService.php && \
"E:/laragon/bin/php/php-8.3.30-Win32-vs16-x64/php.exe" -l tests/Feature/SettlementReconciliationRuntimeTest.php
```

Resultado:

```text
No syntax errors detected in app/Services/SettlementActionService.php
No syntax errors detected in app/Services/Finance/SettlementActionService.php
No syntax errors detected in tests/Feature/SettlementReconciliationRuntimeTest.php
```

Status: **PASS**.

### 9.2 Pest — R3 + R4 runtime

Comando:

```bash
"E:/laragon/bin/php/php-8.3.30-Win32-vs16-x64/php.exe" ./vendor/bin/pest tests/Feature/WalletBalanceRuntimeTest.php tests/Feature/SettlementReconciliationRuntimeTest.php
```

Resultado:

```text
PASS  Tests\Feature\WalletBalanceRuntimeTest
✓ wallet balances table exists after migration contract
✓ wallet balance query by gateway works without sqlstate
✓ wallet debit gateway fails controlled when gateway balance is insufficient
✓ wallet debit gateway reduces available when balance is sufficient
✓ wallet balance service uses available pending blocked schema
✓ wallet balance service is idempotent for replayed financial event
✓ wallet balance service rejects negative or zero amounts

PASS  Tests\Feature\SettlementReconciliationRuntimeTest
✓ force settle is idempotent and debits once
✓ force settle rejects insufficient available balance without partial status change

Tests: 9 passed (34 assertions)
Duration: 0.66s
```

Status: **PASS**.

## 10. Validação financeira

Validações cobertas por teste:

- Settlement replayado não debita duas vezes.
- `WalletTransaction` para settlement é única por `reference_type + reference_id` no teste e por idempotency key no serviço.
- `wallet.balance`, `wallet.available_balance` e `wallet_balances.available` reduzem uma única vez.
- Settlement com saldo insuficiente não muda para liquidado.
- Settlement com saldo insuficiente não cria `WalletTransaction`.

Invariantes atendidas:

- FI-05: saldo disponível não fica negativo no caminho testado.
- FI-07: payout/settlement não excede saldo disponível no caminho testado.
- FI-12: operação financeira idempotente por chave determinística.
- FI-15: settlement passa a registrar referência reconciliável em metadata/wallet transaction.

## 11. Validação de concorrência

Medidas aplicadas:

- `lockForUpdate()` em settlement antes de validar status.
- `lockForUpdate()` em wallet antes de chamar o serviço financeiro.
- `WalletBalanceService` mantém locks de wallet e wallet balance.
- idempotency key determinística permite replay seguro.

Observação:

- O teste implementado valida replay sequencial/idempotente. Teste concorrente real com múltiplos processos/conexões ainda deve ser adicionado em etapa posterior, pois a suíte atual usa feature tests runtime e não harness multi-processo.

## 12. Riscos restantes

1. `Wallet::creditGateway()` e `Wallet::debitGateway()` continuam disponíveis como caminhos legados.
2. `CreditMerchantWalletOnChargeSuccess` ainda altera saldo diretamente.
3. `ReleaseRollingReserveJob` altera subsaldos diretamente, ainda que com lock/transação; idealmente deve passar por serviço financeiro específico de reserve.
4. `ReconcileEfiSettlementCommand` ainda é stub/mock e não reconcilia extrato real EFI.
5. Fees de plataforma/gateway ainda precisam de trilha ledger explícita e snapshot versionado por transação.
6. A reconciliação ainda está dividida entre `LedgerEntry`, `WalletTransaction` e `Transaction` legado.
7. A migration `2026_07_10_000002_harden_financial_idempotency` deve ser aplicada em ambiente controlado para proteção estrutural da idempotência.
8. `WalletTransaction` hash chain ainda pode sofrer inconsistência lógica em concorrência extrema sem lock de cadeia por wallet.
9. Reconciliation jobs detectam divergência, mas não bloqueiam automaticamente operações afetadas.

## 13. Próximos passos recomendados

1. Migrar `CreditMerchantWalletOnChargeSuccess` para `WalletBalanceService` com `charge:{id}:paid`.
2. Depreciar ou encapsular `Wallet::creditGateway()`/`Wallet::debitGateway()`.
3. Criar serviço específico para rolling reserve release com idempotency key `rolling-reserve:{id}:release` e wallet transaction/audit unificados.
4. Implementar reconciliação real de settlement de provedor, começando por EFI.
5. Unificar relatório de reconciliação para cruzar `settlements`, `wallet_transactions`, `ledger_entries`, `transactions`, `fee_records` e extrato PSP.
6. Aplicar migrations pendentes em ambiente apropriado.
7. Adicionar teste concorrente real para settlement com duas tentativas simultâneas.

## 14. Documentação atualizada

Documento gerado na árvore canônica:

```text
../docs/audit/R4-settlement-reconciliation.md
```

## 15. Conclusão

O R4 endureceu o caminho mínimo crítico de settlement, removendo idempotência baseada em UUID, adicionando lock transacional na decisão de liquidação, eliminando o uso de wallet fixa e migrando o settlement financeiro para `WalletBalanceService`. Os testes R3/R4 passam com PHP absoluto do Laragon. O sistema fica mais seguro contra dupla liquidação e saldo negativo no fluxo de settlement coberto, mas ainda há riscos legados em crédito direto, rolling reserve, reconciliação de provedor e unificação ledger/wallet transactions.
