<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Lock;

use RuntimeException;
use Symfony\Component\Lock\PersistingStoreInterface;
use Symfony\Component\Lock\Store\PostgreSqlStore;

use function preg_match;
use function sprintf;

class StoreFactory
{
    private const DSN_REGEX = '/^(?P<scheme>[^:]*):host=(?P<host>[^;]*);port=(?P<port>[^;]*);' .
    'dbname=(?P<database>[^;]*);user=(?P<user>[^;]*);password=(?P<password>[^;]*)$/';

    public function __invoke(string $dsn): PersistingStoreInterface
    {
        if (! preg_match(self::DSN_REGEX, $dsn, $matches)) {
            throw new RuntimeException(
                sprintf("DSN '%s' is not valid.", $dsn),
            );
        }

        return new PostgreSqlStore(
            $dsn,
            [
                'db_username' => $matches['user'],
                'db_password' => $matches['password'],
            ],
        );
    }
}
