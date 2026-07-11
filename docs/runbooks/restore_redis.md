# Restore Redis (Cache & Sessions)

**RTO Alvo:** < 15 Minutos

## Pré-requisitos
- Redis instalado no servidor.
- O Redis deve estar parado antes de sobrescrever o arquivo `dump.rdb`.
- O servidor possuir cópia do arquivo `dump.rdb` ou `appendonly.aof` copiado do servidor destruído.

## Passo a Passo

1. **Parar o Serviço:**
   ```bash
   sudo systemctl stop redis
   ```

2. **Copiar o Backup para a pasta do Redis:**
   ```bash
   sudo cp /caminho/do/backup/dump.rdb /var/lib/redis/dump.rdb
   sudo chown redis:redis /var/lib/redis/dump.rdb
   ```
   *Caso esteja usando AOF:*
   ```bash
   sudo cp /caminho/do/backup/appendonly.aof /var/lib/redis/appendonly.aof
   sudo chown redis:redis /var/lib/redis/appendonly.aof
   ```

3. **Iniciar o Serviço:**
   ```bash
   sudo systemctl start redis
   ```

## Validações Pós-Restore
- Rode o `redis-cli ping` e garanta que receba `PONG`.
- Rode `php artisan horizon:status` para ver se a fila reconheceu o estado anterior e não expurgou as configurações de Supervisor.
- Certifique-se que o painel administrativo de `/admin/system/health` acusa a volta do Redis à normalidade.
