# Runbook 06: Backup & Restore

## Sintomas
- Corrupção severa de dados.
- Drop table acidental.

## Diagnóstico
- A plataforma está instável devido à falta de integridade de dados.

## Mitigação
1. Ativar `EmergencyReadOnlyMode` imediatamente para congelar a foto atual do banco de dados e proteger o Ledger restante.
2. Identificar o ponto de restauração mais próximo. Os backups automatizados ficam no S3 ou na rotina de dump local.

## Rollback (Restore Procedure)
- Para MySQL/PostgreSQL: `mysql -u root -p database_name < dump.sql`.
- Para restaurar integridade dos Webhooks que aconteceram DURANTE a queda: utilizar o `EmergencyReplayService` conectando direto na API do Gateway parceiro, puxando a timeline da última hora via Sync do Gateway para o nosso banco de dados.
- Rodar todas as reconciliações: `php artisan reconcile:ledger`.

## Escalação
- DBA Chief
- Diretor de Tecnologia (CTO)
