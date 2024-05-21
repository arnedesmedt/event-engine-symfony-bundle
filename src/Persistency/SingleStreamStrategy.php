<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Persistency;

use Iterator;
use Prooph\Common\Messaging\Message;
use Prooph\Common\Messaging\MessageConverter;
use Prooph\EventStore\Pdo\DefaultMessageConverter;
use Prooph\EventStore\Pdo\PersistenceStrategy\PostgresPersistenceStrategy;
use Prooph\EventStore\Pdo\Util\Json;
use Prooph\EventStore\Pdo\Util\PostgresHelper;
use Prooph\EventStore\StreamName;

use function array_merge;
use function assert;
use function sprintf;

final class SingleStreamStrategy implements PostgresPersistenceStrategy
{
    use PostgresHelper;

    public function __construct(private MessageConverter|null $messageConverter = null)
    {
        $this->messageConverter = $messageConverter ?? new DefaultMessageConverter();
    }

    /** @inheritDoc */
    public function createSchema(string $tableName): array
    {
        $tableName = $this->quoteIdent($tableName);

        $statement = <<<EOT
CREATE TABLE {$tableName} (
    no BIGSERIAL,
    event_id UUID NOT NULL,
    event_name VARCHAR(255) NOT NULL,
    payload JSON NOT NULL,
    metadata JSONB NOT NULL,
    created_at TIMESTAMP(6) NOT NULL,
    PRIMARY KEY (no),
    CONSTRAINT aggregate_version_not_null CHECK ((metadata->>'_aggregate_version') IS NOT NULL),
    CONSTRAINT aggregate_type_not_null CHECK ((metadata->>'_aggregate_type') IS NOT NULL),
    CONSTRAINT aggregate_id_not_null CHECK ((metadata->>'_aggregate_id') IS NOT NULL),
    UNIQUE (event_id)
);
EOT;

        $index1 = <<<EOT
CREATE UNIQUE INDEX ON {$tableName}
((metadata->>'_aggregate_type'), (metadata->>'_aggregate_id'), (metadata->>'_aggregate_version'));
EOT;

        $index2 = <<<EOT
CREATE INDEX ON {$tableName}
((metadata->>'_aggregate_type'), (metadata->>'_aggregate_id'), no);
EOT;

        return array_merge(
            $this->getSchemaCreationSchema($tableName),
            [
                $statement,
                $index1,
                $index2,
            ],
        );
    }

    /** @return array<string> */
    public function columnNames(): array
    {
        return [
            'event_id',
            'event_name',
            'payload',
            'metadata',
            'created_at',
        ];
    }

    /**
     * @param Iterator<Message> $streamEvents
     *
     * @return array<mixed>
     */
    public function prepareData(Iterator $streamEvents): array
    {
        $data = [];

        foreach ($streamEvents as $event) {
            assert($this->messageConverter instanceof MessageConverter);
            $eventData = $this->messageConverter->convertToArray($event);

            $data[] = $eventData['uuid'];
            $data[] = $eventData['message_name'];
            $data[] = Json::encode($eventData['payload']);
            $data[] = Json::encode($eventData['metadata']);
            $data[] = $eventData['created_at']->format('Y-m-d\TH:i:s.u');
        }

        return $data;
    }

    public function generateTableName(StreamName $streamName): string
    {
        $table = $streamName->toString();
        $schema = $this->extractSchema($table);

        if ($schema) {
            return $schema . '.' . $table;
        }

        return $table;
    }

    /** @return array<string> */
    private function getSchemaCreationSchema(string $tableName): array
    {
        $schemaName = $this->extractSchema($tableName);

        if (! $schemaName) {
            return [];
        }

        return [
            sprintf(
                'CREATE SCHEMA IF NOT EXISTS %s',
                $schemaName,
            ),
        ];
    }
}
