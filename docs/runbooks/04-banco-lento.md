# Runbook 04: Banco Lento

## Sintomas
- Latência de API subindo drasticamente (> 2000ms).
- O alerta "Banco de dados OFFLINE" ou "Lento" no Slack/Telegram.
- Jobs de Webhooks caindo na DLQ por Lock Timeout.

## Diagnóstico
- Olhar métricas do RDS/MySQL.
- Verificar locks rodando: `SHOW FULL PROCESSLIST`.

## Mitigação
1. Ativar `EmergencyReadOnlyMode` imediatamente. Bloqueia criação de novas transações pesadas, isolando o banco.
2. Horizon vai enfileirar e pausar a execução ou rodar lento sem derrubar o banco, pois as filas seguram carga.

## Rollback
- Otimizar query causadora ou escalar DB (Scale up).
- Desativar Read Only Mode.
- Os jobs que caíram na DLQ devem ser reprocessados pelo admin.

## Escalação
- Equipe DBA
- SRE
