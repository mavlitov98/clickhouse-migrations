<?php

declare(strict_types=1);

namespace ClickhouseMigrations\Command;

use ClickHouseDB\Client;
use DateTimeImmutable;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'clickhouse-migrations:generate')]
final class ClickhouseGenerateMigrationCommand extends Command
{
    public function __construct(
        private readonly string $migrationsPath,
        private readonly string $migrationsNamespace,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('Generate new migration for Clickhouse');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $now = (new DateTimeImmutable())->format('YmdHis');
        $className = "Version{$now}";

        $newMigration = <<<PHP
        <?php
        
        declare(strict_types=1);
        
        namespace {$this->migrationsNamespace};
        
        use ClickHouseDB\Client;
        use ClickhouseMigrations\AbstractClickhouseMigration;

        final class {$className} extends AbstractClickhouseMigration
        {
            public function up(Client \$client): void
            {
                \$client->write(
                    <<<CLICKHOUSE
                    
                    CLICKHOUSE,
                );
            }
        }

        PHP;

        $success = function () use ($input, $output, $className): int {
            (new SymfonyStyle($input, $output))->success(
                "Migration {$className} successfully generated.
                \nUse 'clickhouse-migrations:execute {$className} up' to apply.",
            );

            return self::SUCCESS;
        };

        if (!is_dir($this->migrationsPath)) {
            mkdir($this->migrationsPath, recursive: true);
        }

        return file_put_contents("{$this->migrationsPath}/{$className}.php", $newMigration)
            ? $success()
            : self::FAILURE;
    }
}
