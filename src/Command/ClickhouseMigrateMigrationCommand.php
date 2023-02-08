<?php

declare(strict_types=1);

namespace ClickhouseMigrations\Command;

use ClickHouseDB\Client;
use Fp\Collections\ArrayList;
use Fp\Functional\Option\Option;
use ReflectionClass;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use ClickhouseMigrations\AbstractClickhouseMigration;

#[AsCommand('clickhouse-migrations:migrate')]
final class ClickhouseMigrateMigrationCommand extends Command
{
    /**
     * @param list<AbstractClickhouseMigration> $migrations
     */
    public function __construct(
        private readonly Client $client,
        private readonly array $migrations,
        private readonly string $migrationsVersionTable,
    ) {
        parent::__construct();
    }

    public function configure(): void
    {
        $this->setDescription('Применяет все новые миграции ClickHouse');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $executedMigrations = ClickhouseMigrationHelper::getExecutedMigrations(
            client: $this->client,
            versionTable: $this->migrationsVersionTable,
        );

        $appliedMigrations = ArrayList::collect($this->migrations)
            ->filterMap(function (AbstractClickhouseMigration $migration) use ($executedMigrations) {
                $migrationVersion = (new ReflectionClass($migration))->getShortName();

                if (!in_array($migrationVersion, $executedMigrations)) {
                    $migration->up($this->client);

                    ClickhouseMigrationHelper::writeExecutedMigration(
                        client: $this->client,
                        versionTable: $this->migrationsVersionTable,
                        version: $migrationVersion,
                    );

                    return Option::some($migrationVersion);
                }

                return Option::none();
            });

        $styleOut = new SymfonyStyle($input, $output);

        $appliedMigrations->isEmpty()
            ? $styleOut->note('Nothing to migrate')
            : $styleOut->success(
                sprintf(
                    '%d migration(s) successfully applied: %s.',
                    $appliedMigrations->count(),
                    $appliedMigrations->mkString(sep: ', '),
                ),
            );

        return self::SUCCESS;
    }
}
