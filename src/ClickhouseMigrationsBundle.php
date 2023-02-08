<?php

declare(strict_types=1);

namespace ClickhouseMigrations;

use ClickHouseDB\Client;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use ClickhouseMigrations\DependencyInjection\ClickhouseMigrationCommandCompilerPass;

final class ClickhouseMigrationsBundle extends AbstractBundle
{
    public const CLICKHOUSE_MIGRATION_CLIENT_SERVICE_ID = 'clickhouse_migration_client';

    public const MIGRATIONS_VERSION_TABLE_PARAM = 'clickhouse_migrations.migrations_version_table';
    public const MIGRATIONS_PATH_PARAM = 'clickhouse_migrations.path';
    public const MIGRATIONS_NAMESPACE_PARAM = 'clickhouse_migrations.namespace';

    public const MIGRATION_TAG = 'clickhouse_migrations.migration';

    protected string $extensionAlias = 'clickhouse_migrations';

    public function configure(DefinitionConfigurator $definition): void
    {
        /** @psalm-suppress PossiblyUndefinedMethod, MixedMethodCall */
        $definition->rootNode()
            ->children()
                ->arrayNode('connection')
                    ->children()
                        ->scalarNode('port')
                            ->defaultValue(8123)
                        ->end()
                        ->scalarNode('host')
                            ->defaultValue('localhost')
                        ->end()
                        ->scalarNode('username')
                            ->defaultValue('default')
                        ->end()
                        ->scalarNode('database')
                            ->defaultValue('default')
                        ->end()
                        ->scalarNode('password')
                            ->defaultValue('')
                        ->end()
                    ->end()
                ->end()
                ->scalarNode('clickhouse_migrations_version_table')
                    ->defaultValue('clickhouse_migrations_version')
                ->end()
                ->scalarNode('clickhouse_migrations_path')
                    ->defaultValue('%kernel.project_dir%/src/Migrations/Clickhouse')
                ->end()
                ->scalarNode('clickhouse_migrations_namespace')
                    ->defaultValue('App\\Migrations\\Clickhouse')
                ->end()
            ->end();
    }

    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $builder->setParameter(
            name: self::MIGRATIONS_VERSION_TABLE_PARAM,
            value: (string) $config['clickhouse_migrations_version_table'],
        );

        $builder->setParameter(
            name: self::MIGRATIONS_PATH_PARAM,
            value: (string) $config['clickhouse_migrations_path'],
        );

        $builder->setParameter(
            name: self::MIGRATIONS_NAMESPACE_PARAM,
            value: (string) $config['clickhouse_migrations_namespace'],
        );

        $container->services()
            ->defaults()
            ->autoconfigure()
            ->load(
                namespace: ((string) $config['clickhouse_migrations_namespace']) . '\\',
                resource: ((string) $config['clickhouse_migrations_path']) . '/*',
            );

        /** @var array{port: int, host: string, username: string, password: string} $connection */
        $connection = $config['connection'] ?? [
            'port' => 8123,
            'host' => 'localhost',
            'username' => 'default',
            'password' => '',
        ];

        $container->services()
            ->set(self::CLICKHOUSE_MIGRATION_CLIENT_SERVICE_ID)
            ->class(Client::class)
            ->arg('$connectParams', $connection)
            ->call('database', [
                '$db' => $config['connection']['database'] ?? 'default',
            ]);
    }

    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new ClickhouseMigrationCommandCompilerPass());

        $container
            ->registerForAutoconfiguration(AbstractClickhouseMigration::class)
            ->addTag(self::MIGRATION_TAG);
    }
}
