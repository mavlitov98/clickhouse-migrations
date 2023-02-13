<h3>Installation</h3>
<br>

`composer require mavlitov98/clickhouse-migrations`

<h3>Setup</h3>
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
<h3>Commands</h3>

- `clickhouse-migrations:generate` - generate new migration for ClickHouse.
- `clickhouse-migrations:migrate` - apply all generated migrations to ClickHouse.
- `clickhouse-migrations:execute [VersionXXXXXXXXXXXXXX] [up|down]` - apply or rollback ClickHouse migration.

