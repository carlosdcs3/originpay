# Runbook 08: Financial Maintenance

## Sintomas
- Migrações complexas de banco.
- Descoberta de Bug crítico na fórmula de cálculo de conversão (Ledger/Taxas).

## Diagnóstico
- O sistema não pode processar Novas intenções Financeiras até a correção, mas o trânsito bancário que está acontecendo nas costas (PIX recebido na instituição de pagamentos) DEVE ser processado.

## Mitigação
- Ativar o Financial Maintenance Mode: impede novos depósitos, saques manuais e estornos pela tela. 
- O Horizon, Webhook Inbound e Replay continuam ativos compensando transações antigas de forma segura.

## Rollback
- Após correção da taxa, desativar o Maintenance Mode.
- A fila não precisará represar milhares de requests acumulados pois apenas as saídas (Outbound) e novas criações foram congeladas.

## Escalação
- Apenas SRE ou Nível 3 para liberação do sistema.
