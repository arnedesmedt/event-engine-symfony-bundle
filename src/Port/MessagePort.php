<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Port;

use ADS\Bundle\EventEngineBundle\MetadataExtractor\AggregateCommandExtractor;
use ADS\Bundle\EventEngineBundle\Query\Query;
use Chrisguitarguy\RequestId\RequestIdStorage;
use Closure;
use EventEngine\Data\ImmutableRecord;
use EventEngine\JsonSchema\JsonSchemaAwareRecord;
use EventEngine\Messaging\CommandDispatchResult;
use EventEngine\Messaging\Message;
use EventEngine\Messaging\MessageBag;
use EventEngine\Runtime\Functional\Port;
use Opis\JsonSchema\Validator;
use Ramsey\Uuid\Uuid;
use ReflectionClass;
use RuntimeException;
use stdClass;

use function array_map;
use function get_debug_type;
use function is_array;
use function json_decode;
use function json_encode;
use function sprintf;

use const JSON_THROW_ON_ERROR;

final class MessagePort implements Port
{
    public function __construct(
        private readonly Validator $validator,
        private readonly AggregateCommandExtractor $aggregateCommandExtractor,
        private readonly RequestIdStorage $requestIdStorage,
    ) {
    }

    public function deserialize(Message $message): mixed
    {
        /** @var class-string $messageType */
        $messageType = $message->messageName();
        $data = $message->payload();

        $reflectionClass = new ReflectionClass($messageType);

        if ($reflectionClass->implementsInterface(JsonSchemaAwareRecord::class)) {
            $encodedData = json_encode($data, JSON_THROW_ON_ERROR);
            $schemaArray = $messageType::__schema()->toArray();
            $schema = json_encode($schemaArray, JSON_THROW_ON_ERROR);
            $data = json_decode($encodedData, null, 512, JSON_THROW_ON_ERROR);

            if ($data === []) {
                $data = new stdClass();
            }

            $this->validator->validate($data, $schema);
        }

        $encodedData = json_encode($data, JSON_THROW_ON_ERROR);
        $data = json_decode($encodedData, true, 512, JSON_THROW_ON_ERROR);

        return $messageType::fromArray((array) $data);
    }

    /** @return array<mixed> */
    public function serializePayload(mixed $customMessage): array
    {
        if (is_array($customMessage)) {
            return $customMessage;
        }

        if ($customMessage instanceof ImmutableRecord) {
            return $customMessage->toArray();
        }

        throw new RuntimeException(
            sprintf(
                "Invalid message passed to '%s'. This should be an immutable record but got '%s' instead.",
                __METHOD__,
                get_debug_type($customMessage),
            ),
        );
    }

    /**
     * @param object $customCommand
     *
     * @inheritDoc
     */
    public function decorateCommand($customCommand): MessageBag
    {
        if (
            $customCommand instanceof MessageBag
            && $customCommand->messageType() === MessageBag::TYPE_COMMAND
        ) {
            return $customCommand;
        }

        $messageUuid = $this->requestIdStorage->getRequestId();

        if ($messageUuid !== null) {
            $messageUuid = Uuid::fromString($messageUuid);
        }

        return new MessageBag(
            $customCommand::class,
            MessageBag::TYPE_COMMAND,
            $customCommand,
            [],
            $messageUuid,
        );
    }

    /**
     * @param object $customEvent
     *
     * @inheritDoc
     */
    public function decorateEvent($customEvent): MessageBag
    {
        if (
            $customEvent instanceof MessageBag
            && $customEvent->messageType() === MessageBag::TYPE_EVENT
        ) {
            return $customEvent;
        }

        return new MessageBag(
            $customEvent::class,
            MessageBag::TYPE_EVENT,
            $customEvent,
        );
    }

    /**
     * @param JsonSchemaAwareRecord $command
     *
     * @inheritDoc
     */
    public function getAggregateIdFromCommand(string $aggregateIdPayloadKey, $command): string
    {
        $aggregateId = $this->aggregateCommandExtractor->aggregateIdFromAggregateCommand($command);

        if ($aggregateId) {
            return $aggregateId;
        }

        throw new RuntimeException(
            sprintf(
                "Unknown command. Cannot get the aggregate id from command '%s'.",
                $command::class,
            ),
        );
    }

    /**
     * @param Closure $preProcessor
     *
     * @inheritDoc
     */
    public function callCommandPreProcessor($customCommand, $preProcessor): mixed
    {
        return $preProcessor($customCommand);
    }

    /**
     * @param Closure $controller
     *
     * @inheritDoc
     */
    public function callCommandController($customCommand, $controller): array|CommandDispatchResult|null
    {
        /** @var array<array<mixed>|MessageBag|ImmutableRecord>|CommandDispatchResult $result */
        $result = $controller($customCommand);

        if (is_array($result)) {
            return array_map(
                fn (array|MessageBag|ImmutableRecord $messageOrArray): MessageBag|array => is_array($messageOrArray)
                    ? $messageOrArray
                    : $this->decorateCommand($messageOrArray),
                $result,
            );
        }

        return $result;
    }

    /**
     * @param Closure $contextProvider
     *
     * @inheritDoc
     */
    public function callContextProvider($customCommand, $contextProvider): mixed
    {
        return $contextProvider($customCommand);
    }

    /**
     * @param Query|Message $customQuery
     * @param Closure $resolver
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
     */
    public function callResolver($customQuery, $resolver): mixed
    {
        return $resolver($customQuery);
    }
}
