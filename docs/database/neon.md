# Neon PostgreSQL

Este projeto usa PostgreSQL via Neon mantendo a arquitetura Laravel atual. Nenhuma credencial deve ser versionada; todas as informações sensíveis ficam exclusivamente no `.env`.

## Criar um projeto Neon

1. Acesse o painel do Neon e crie um novo projeto.
2. Escolha a região mais próxima da aplicação.
3. Crie ou selecione o banco principal do projeto.
4. Crie uma role própria para a aplicação, evitando usar credenciais pessoais.

## Obter a connection string

No painel do Neon, abra o widget de conexão do projeto, selecione:

- Branch desejada.
- Database da aplicação.
- Role da aplicação.
- Driver PostgreSQL/libpq.

Copie a connection string. Ela terá formato semelhante a:

```text
postgresql://USER:PASSWORD@HOST.neon.tech/DBNAME?sslmode=require
```

O Neon exige conexão criptografada. Use `sslmode=require` no `.env` ou na query string.

## Configurar o `.env`

Use o `.env.example` como base:

```dotenv
DATABASE_CONNECTION=pgsql
DATABASE_URL="postgresql://USER:PASSWORD@HOST.neon.tech/DBNAME?sslmode=require"
DB_CONNECTION="${DATABASE_CONNECTION}"
DB_HOST=HOST.neon.tech
DB_PORT=5432
DB_DATABASE=DBNAME
DB_USERNAME=USER
DB_PASSWORD=PASSWORD
DB_SSLMODE=require
```

Quando `DATABASE_URL` estiver preenchido, ele será a fonte principal da conexão. As variáveis `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME` e `DB_PASSWORD` continuam disponíveis para ambientes que preferem configurar os campos separadamente.

## Migrar banco existente

Para migração de MySQL/MariaDB para Neon, faça primeiro um backup completo do banco atual.

Fluxo recomendado:

1. Criar banco/branch no Neon.
2. Configurar `.env` apontando para o Neon.
3. Rodar migrations em um ambiente de staging:

```bash
php artisan migrate
```

4. Migrar os dados usando uma ferramenta apropriada para conversão MySQL -> PostgreSQL, como `pgloader`, exportação intermediária CSV ou um job ETL validado.
5. Comparar contagens por tabela, totais financeiros e amostras de registros críticos.
6. Executar a suíte de testes e smoke tests de fluxos principais.
7. Fazer o corte de produção em janela controlada.

## Restaurar backup PostgreSQL

Para gerar backup de um banco PostgreSQL:

```bash
pg_dump "$DATABASE_URL" --format=custom --file=backup.dump
```

Para restaurar:

```bash
pg_restore --clean --if-exists --no-owner --dbname="$DATABASE_URL" backup.dump
```

Em produção, restaure primeiro em uma branch Neon temporária e valide a aplicação antes de promover a branch ou trocar a connection string.

## Utilizar branches do Neon

Branches do Neon permitem criar cópias isoladas do banco para desenvolvimento, homologação, testes de migrations e recuperação.

Boas práticas:

- Use uma branch por ambiente (`production`, `staging`, `development`).
- Teste `php artisan migrate` em branch temporária antes de rodar em produção.
- Use branches para validar restaurações de backup.
- Remova branches antigas para reduzir custo e confusão operacional.

## Boas práticas de produção

- Nunca commitar credenciais reais.
- Usar `DB_SSLMODE=require` ou `sslmode=require` na `DATABASE_URL`.
- Preferir roles separadas por ambiente.
- Usar connection pooling do Neon quando o volume de conexões justificar.
- Monitorar limites de conexão, latência e consumo.
- Rodar `php artisan config:cache` somente depois de conferir o `.env`.
- Executar migrations em staging antes de produção.
- Manter backups testados e procedimento de restauração documentado.
- Validar consultas pesadas com `EXPLAIN ANALYZE` no PostgreSQL.
- Evitar SQL específico de um fornecedor; preferir Query Builder/Eloquent.

## Checklist de validação

```bash
php artisan optimize:clear
php artisan migrate
php artisan migrate:fresh --seed
php artisan config:cache
php artisan route:cache
php artisan test
```

Se qualquer comando falhar, corrija a incompatibilidade na branch de staging antes de apontar produção para o Neon.
