<?php

declare(strict_types=1);

namespace ClickhouseMigrations\Command;

use ClickHouseDB\Client;
use DateTimeImmutable;
use Fp\Collections\ArrayList;

final class ClickhouseMigrationHelper
{
    /**
     * @return list<string>
     */
    public static function getExecutedMigrations(Client $client, string $versionTable): array
    {
        $client->write(
            <<<CLICKHOUSE
            CREATE TABLE IF NOT EXISTS {$versionTable} (
                version String,
                executed_at DateTime
            )
            ENGINE = MergeTree
            PRIMARY KEY (version);
            CLICKHOUSE,
        );

        /** @var list<array{version: string, executed_at: string}> $dataFromTable */
        $dataFromTable = $client
            ->select("SELECT * FROM {$versionTable};")
            ->rows();

        return ArrayList::collect($dataFromTable)
            ->map(fn(array $row) => $row['version'])
            ->toList();
    }

    public static function writeExecutedMigration(Client $client, string $versionTable, string $version): void
    {
        $now = (new DateTimeImmutable())->format('Y-m-d H:i:s');

        $client->write(
            <<<CLICKHOUSE
            INSERT INTO {$versionTable} VALUES ('{$version}', '{$now}');
            CLICKHOUSE,
        );
    }

    public static function deleteCancelledMigration(Client $client, string $versionTable, string $version): void
    {
        $client->write(
            <<<CLICKHOUSE
            ALTER TABLE {$versionTable} DELETE WHERE version = '{$version}';
            CLICKHOUSE,
        );
    }
}
