# OriginPay Enterprise: Finance Architecture

## 1. O Motor Financeiro (A Trindade)
A arquitetura financeira da OriginPay baseia-se na imutabilidade do saldo através do padro de Ledger (Livro-razão).
Nenhum subsistema pode alterar a \WalletBalance\ diretamente. Tudo deve orbitar a Trindade:
\Charge/Withdrawal (Entidade) -> WalletTransaction (Ledger) -> WalletBalance (Saldo Físico)\

### 1.1 Diagrama de Custódia

\\\mermaid
graph TD
    A[Charge / Webhook] -->|Valida e Aprova| B(WalletBalanceService)
    C[Chargeback] -->|Bloqueia Fundo| B
    D[Settlement] -->|Saca Fundo| B
    
    B -->|Grava Imutabilidade| E[(Ledger: WalletTransaction)]
    B -->|Muta Saldo| F[(WalletBalance: Gateway Segregado)]
    B -->|Muta Saldo| G[(Wallet: Consolidao Merchant)]
    
    E -.->|Auditoria| H[Dashboards / Reconciliao]
\\\

## 2. Modelagem do Domínio
- **Charges:** Rastreamento do Cash-In (PIX, Carto, Boleto).
- **Withdrawals:** Rastreamento do Cash-Out (Transferências externas).
- **Settlements:** Motor de Liquidao de repasses em lote para a conta bancária do Merchant.
- **Chargebacks:** Retenções de risco provindas de contestações.
- **FeeRecords:** Auditoria cruzada entre (Taxa OriginPay) vs (Custo Gateway) para gerao de Margem Líquida.

## 3. Padrão de Camadas (Service e DTO)

### DashboardServices
Todos os controllers administrativos injetam *DashboardServices* (ex: \ChargeDashboardService\). 
Sua nica finalidade  retornar um DTO otimizado e cacheado para a View (read-only).

### ActionServices
Orquestram a validação do negócio. Ex: \ChargebackActionService\.
Se decidem aprovar, bloqueiam ou cancelar uma entidade, eles validam o estado da aplicao e acionam o **WalletBalanceService**.

### WalletBalanceService
O nico serviço na plataforma autorizado a invocar a funo \->lockForUpdate()\ no banco de dados e modificar o saldo de um lojista. Responsável pelas ações de \creditGateway\, \debitGateway\, \lockFunds\, \eleaseFunds\.

## 4. Fluxo End-to-End

### Pagamento via Gateway (Cash-In)
1. Webhook chega com status de PAGO.
2. \Charge\ validada.
3. \WalletBalanceService::creditGateway()\ invocado.
4. \WalletTransaction\ criada com ID da transao.
5. Saldo livre do \WalletBalance\ correspondente ao Gateway acrescido.
6. Conciliao assíncrona valida divergências.
