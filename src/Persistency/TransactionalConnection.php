<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Persistency;

use PDO;

final class TransactionalConnection implements \EventEngine\Persistence\TransactionalConnection
{
    public function __construct(private readonly PDO $connection)
    {
    }

    public function beginTransaction(): void
    {
        if ($_SERVER['APP_ENV'] === 'test') {
            return;
        }

        $this->connection->beginTransaction();
    }

    public function commit(): void
    {
        if ($_SERVER['APP_ENV'] === 'test') {
            return;
        }

        $this->connection->commit();
    }

    public function rollBack(): void
    {
        if ($_SERVER['APP_ENV'] === 'test') {
            return;
        }

        $this->connection->rollBack();
    }
}
