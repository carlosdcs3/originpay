# Restore Total (Destruição Completa do Servidor)

**RTO Alvo:** < 90 Minutos
**RPO Alvo:** < 24 Horas

## Cenário
A VPS ou Servidor Dedicado inteiro queimou, o disco foi corrompido sem conserto ou a CloudProvider bloqueou a conta por engano.

## Pré-requisitos
1. Uma máquina nova instalada com Ubuntu 22.04 / 24.04.
2. Repositório do GitHub do DigiKash disponível para clone.
3. Acesso à AWS S3/Cloudflare R2 para baixar o `.sql.gz` remotamente guardado.
4. O arquivo `.env` salvo no cofre de senhas corporativo.

## Passo a Passo

1. **Subir Infraestrutura Base:**
   Instalar Nginx, PHP-FPM, MySQL e Redis. 
   *(Opcional: Subir tudo usando Docker/Sail caso o time opere assim em prod).*

2. **Clone do Código Fonte:**
   ```bash
   git clone git@github.com:empresa/digikash.git /var/www/html
   cd /var/www/html/core
   composer install --no-dev --optimize-autoloader
   ```

3. **Restaurar Variáveis (Env):**
   Cole o conteúdo do cofre de senhas no `/var/www/html/core/.env`.

4. **Baixar e Restaurar o Banco de Dados:**
   ```bash
   aws s3 cp s3://meubucket/backups/database/db_backup_X.sql.gz storage/backups/
   aws s3 cp s3://meubucket/backups/database/db_backup_X.sql.gz.sha256 storage/backups/
   
   ./scripts/restore-database.sh storage/backups/db_backup_X.sql.gz
   ```

5. **Ligar Supervisor (Horizon):**
   Mova o arquivo `horizon-supervisor.conf.example` criado na Fase 5.1 para a pasta `/etc/supervisor/conf.d/horizon.conf`.
   ```bash
   sudo supervisorctl reread
   sudo supervisorctl update
   sudo supervisorctl start horizon
   ```

6. **Redirecionar DNS:**
   Aponte o Cloudflare para o IP da nova máquina.

## Validações Pós-Restore
- Garantir que o certificado SSL (Certbot) foi renovado e os Webhooks externos do parceiro financeiro voltaram a ser entregues em nosso `/webhook`.
- O Dashboard do Heath Score deverá sair de `Critical (0/100)` para `Healthy (100/100)` após as filas normalizarem.
