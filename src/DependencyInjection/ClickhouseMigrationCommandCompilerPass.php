<?php

declare(strict_types=1);

namespace ClickhouseMigrations\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\PriorityTaggedServiceTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use ClickhouseMigrations\ClickhouseMigrationsBundle;
use ClickhouseMigrations\Command\ClickhouseExecuteMigrationCommand;
use ClickhouseMigrations\Command\ClickhouseGenerateMigrationCommand;
use ClickhouseMigrations\Command\ClickhouseMigrateMigrationCommand;

final class ClickhouseMigrationCommandCompilerPass implements CompilerPassInterface
{
    use PriorityTaggedServiceTrait;

    public function process(ContainerBuilder $container): void
    {
        $registerCommand = fn(string $command): Definition => $container
            ->register($command, $command)
            ->setAutowired(true)
            ->setAutoconfigured(true)
            ->addTag('console.command');

        $registerCommand(ClickhouseMigrateMigrationCommand::class)
            ->setArguments([
                '$migrations' => $this->findAndSortTaggedServices(
                    tagName: ClickhouseMigrationsBundle::MIGRATION_TAG,
                    container: $container,
                ),
                '$client' => new Reference(ClickhouseMigrationsBundle::CLICKHOUSE_MIGRATION_CLIENT_SERVICE_ID),
                '$migrationsVersionTable' => $container->getParameter(ClickhouseMigrationsBundle::MIGRATIONS_VERSION_TABLE_PARAM),
            ]);

        $registerCommand(ClickhouseExecuteMigrationCommand::class)
            ->setArguments([
                '$migrations' => $this->findAndSortTaggedServices(
                    tagName: ClickhouseMigrationsBundle::MIGRATION_TAG,
                    container: $container,
                ),
                '$client' => new Reference(ClickhouseMigrationsBundle::CLICKHOUSE_MIGRATION_CLIENT_SERVICE_ID),
                '$migrationsVersionTable' => $container->getParameter(ClickhouseMigrationsBundle::MIGRATIONS_VERSION_TABLE_PARAM),
            ]);

        $registerCommand(ClickhouseGenerateMigrationCommand::class)
            ->setArguments([
                '$migrationsPath' => $container->getParameter(ClickhouseMigrationsBundle::MIGRATIONS_PATH_PARAM),
                '$migrationsNamespace' => $container->getParameter(ClickhouseMigrationsBundle::MIGRATIONS_NAMESPACE_PARAM),
            ]);
    }
}
