<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\MessageDispatcher;

use Chrisguitarguy\RequestId\RequestIdStorage;
use EventEngine\JsonSchema\JsonSchemaAwareRecord;
use EventEngine\Messaging\MessageDispatcher;

class FixedMessageUuidDispatcher implements MessageDispatcher
{
    public function __construct(
        private readonly MessageDispatcher $messageDispatcher,
        private readonly RequestIdStorage $requestIdStorage,
    ) {
    }

    /**
     * @param class-string<JsonSchemaAwareRecord> $messageOrName
     * @param array<string, mixed> $payload
     * @param array<string, mixed> $metadata
     */
    public function dispatch($messageOrName, array $payload = [], array $metadata = []): mixed
    {
        if ($this->requestIdStorage->getRequestId() && ! isset($metadata['uuid'])) {
            $metadata['messageUuid'] = $this->requestIdStorage->getRequestId();
        }

        return $this->messageDispatcher->dispatch($messageOrName, $payload, $metadata);
    }
}
