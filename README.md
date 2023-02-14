### Installation
<br>

```bash
composer require mavlitov98/clickhouse-migrations
```

### Setup
<br>

Create configuration `clickhouse_migrations.yaml` in `config/packages` with the following content:

<br>

```yaml
clickhouse_migrations:
  connection: # connection configuration (required)
    host: '%env(CLICKHOUSE_HOST)%'
    port: '%env(CLICKHOUSE_PORT)%'
    username: '%env(CLICKHOUSE_USERNAME)%'
    password: '%env(CLICKHOUSE_PASSWORD)%'
  clickhouse_migrations_version_table: 'ch_migrations' # version table name (optional)
  clickhouse_migrations_path: '%kernel.project_dir%/src/Migrations/Clickhouse' # migration path (optional)
  clickhouse_migrations_namespace: 'App\\Migrations\\Clickhouse' # migration class namespace (optional)
```

<br>

### Commands

- `bin/console clickhouse-migrations:generate` - generate new migration for ClickHouse.
- `bin/console clickhouse-migrations:migrate` - apply all generated migrations to ClickHouse.
- `bin/console clickhouse-migrations:execute [VersionXXXXXXXXXXXXXX] [up|down]` - apply or rollback ClickHouse migration.

