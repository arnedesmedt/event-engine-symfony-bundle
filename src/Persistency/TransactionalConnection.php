<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Persistency;

use PDO;

final class TransactionalConnection implements \EventEngine\Persistence\TransactionalConnection
{
    private PDO $connection;

    public function __construct(PDO $connection)
    {
        $this->connection = $connection;
    }

    public function beginTransaction() : void
    {
        $this->connection->beginTransaction();
    }

    public function commit() : void
    {
        $this->connection->commit();
    }

    public function rollBack() : void
    {
        $this->connection->rollBack();
    }
}
