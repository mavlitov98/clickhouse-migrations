<?php

declare(strict_types=1);

namespace ClickhouseMigrations;

use ClickHouseDB\Client;

abstract class AbstractClickhouseMigration
{
    abstract public function up(Client $client): void;

    public function down(Client $client): void
    {
    }
}
