<?php

declare(strict_types=1);

namespace ClickhouseMigrations\Command;

use ClickHouseDB\Client;
use Fp\Collections\ArrayList;
use Fp\Functional\Either\Either;
use ReflectionClass;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use ClickhouseMigrations\AbstractClickhouseMigration;

use function Fp\Collection\sequenceEitherT;
use function Fp\Evidence\proveFalse;
use function Fp\Evidence\proveNonEmptyString;
use function Fp\Evidence\proveTrue;

#[AsCommand('clickhouse-migrations:execute')]
final class ClickhouseExecuteMigrationCommand extends Command
{
    /**
     * @param list<AbstractClickhouseMigration> $migrations
     */
    public function __construct(
        private readonly Client $client,
        private readonly array  $migrations,
        private readonly string $migrationsVersionTable,
    ) {
        parent::__construct();
    }

    public function configure(): void
    {
        $this->setDescription('Apply or rollback Clickhouse migration')
            ->addArgument(
                name: 'version',
                mode: InputOption::VALUE_REQUIRED,
            )
            ->addArgument(
                name: 'action',
                mode: InputOption::VALUE_REQUIRED,
                description: 'up/down',
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        return Either::right($input)
            ->flatMap(fn(InputInterface $i) => sequenceEitherT(
                proveNonEmptyString($i->getArgument('version'))
                    ->toRight(fn() => 'Migration version is required.'),
                proveNonEmptyString($i->getArgument('action'))
                    ->filter(fn($action) => in_array($action, ['up', 'down']))
                    ->toRight(fn() => 'Action must be "up" or "down".'),
            ))
            ->flatMapN(
                fn(string $version, string $action) => ArrayList::collect($this->migrations)
                    ->first(fn(AbstractClickhouseMigration $m) => $version === (new ReflectionClass($m))->getShortName())
                    ->toRight(fn() => "{$version} not found.")
                    ->flatMap(fn(AbstractClickhouseMigration $m) => match ($action) {
                        'up' => $this->up($m, $version),
                        'down' => $this->down($m, $version),
                    }),
            )
            ->tap(fn(string $message): mixed => (new SymfonyStyle($input, $output))->success($message))
            ->tapLeft(fn(string $message): mixed => (new SymfonyStyle($input, $output))->error($message))
            ->fold(fn() => self::INVALID, fn() => self::SUCCESS);
    }

    /**
     * @return Either<string, string>
     */
    private function up(AbstractClickhouseMigration $migration, string $version): Either
    {
        $executed = ClickhouseMigrationHelper::getExecutedMigrations(
            client: $this->client,
            versionTable: $this->migrationsVersionTable,
        );

        return proveFalse(in_array($version, $executed))
            ->tap(fn() => $migration->up($this->client))
            ->tap(fn() => ClickhouseMigrationHelper::writeExecutedMigration(
                client: $this->client,
                versionTable: $this->migrationsVersionTable,
                version: $version,
            ))
            ->toRight(fn() => "{$version} has already been applied.")
            ->map(fn() => "{$version} successfully applied.");
    }

    /**
     * @return Either<string, string>
     */
    private function down(AbstractClickhouseMigration $migration, string $version): Either
    {
        $executed = ClickhouseMigrationHelper::getExecutedMigrations(
            client: $this->client,
            versionTable: $this->migrationsVersionTable,
        );

        return proveTrue(in_array($version, $executed))
            ->tap(fn() => $migration->down($this->client))
            ->tap(fn() => ClickhouseMigrationHelper::deleteCancelledMigration(
                client: $this->client,
                versionTable: $this->migrationsVersionTable,
                version: $version,
            ))
            ->toRight(fn() => 'Only applied migrations can be rolled back.')
            ->map(fn() => "{$version} successfully rolled back.");
    }
}
