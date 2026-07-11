# Restore Database (MySQL)

**RTO Alvo:** < 60 Minutos
**RPO Alvo:** < 24 Horas

## Pré-requisitos
- Ter acesso SSH ao servidor alvo.
- O MySQL estar em execução e a base de dados alvo devidamente mapeada no arquivo `.env`.
- Arquivo de backup (.sql.gz) e seu hash (.sha256) disponíveis em disco (Baixe do S3 caso o servidor local tenha morrido).

## Passo a Passo

1. **Ativar Emergency Read-Only Mode:** 
   Se o banco ainda existir, ative o `kill_switch:read_only_mode` via Artisan antes de corrompê-lo/dropá-lo para evitar que transações continuem chegando durante o restore.

2. **Simular Restore (Verificação a Seco):**
   ```bash
   php artisan disaster:verify-restore --file=storage/backups/db_backup_X.sql.gz
   ```
   *Se o arquivo não passar, baixe a versão remota do Cloudflare R2 ou AWS S3.*

3. **Executar Script de Restore:**
   ```bash
   cd core/scripts
   ./restore-database.sh ../storage/backups/db_backup_X.sql.gz
   ```

## Validações Pós-Restore
- Verifique se a quantidade de tabelas está correta (geralmente > 60).
- Rode `php artisan reconcile:ledger` para encontrar gaps na carteira que possam ter acontecido entre o último RPO e a queda.
- Rode `php artisan anomalies:scan` para verificar se o banco restaurado está responsivo.
- Se tudo estiver OK, remova o Kill Switch de Read-Only.
