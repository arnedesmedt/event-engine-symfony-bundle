<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Port;

use ADS\Bundle\EventEngineBundle\Command\AggregateCommand;
use ADS\Bundle\EventEngineBundle\Exception\JsonException;
use ADS\Bundle\EventEngineBundle\Query\Query;
use EventEngine\Data\ImmutableRecord;
use EventEngine\JsonSchema\JsonSchemaAwareRecord;
use EventEngine\Messaging\Message;
use EventEngine\Messaging\MessageBag;
use EventEngine\Runtime\Functional\Port;
use Opis\JsonSchema\Schema;
use Opis\JsonSchema\Validator;
use ReflectionClass;
use RuntimeException;
use stdClass;

use function get_debug_type;
use function is_array;
use function json_decode;
use function json_encode;
use function sprintf;

use const JSON_THROW_ON_ERROR;

final class MessagePort implements Port
{
    public function __construct(private readonly Validator $validator)
    {
    }

    /**
     * @inheritDoc
     */
    public function deserialize(Message $message)
    {
        /** @var class-string $messageType */
        $messageType = $message->messageName();
        $data = $message->payload();

        $reflectionClass = new ReflectionClass($messageType);

        if ($reflectionClass->implementsInterface(JsonSchemaAwareRecord::class)) {
            $encodedData = json_encode($data, JSON_THROW_ON_ERROR);

            if ($encodedData === false) {
                throw JsonException::couldNotEncode($data);
            }

            $schemaArray = $messageType::__schema()->toArray();
            $schema = json_encode($schemaArray, JSON_THROW_ON_ERROR);

            if ($schema === false) {
                throw JsonException::couldNotEncode($schemaArray);
            }

            $data = json_decode($encodedData, null, 512, JSON_THROW_ON_ERROR);

            if ($data === []) {
                $data = new stdClass();
            }

            $this->validator->schemaValidation($data, (new Schema())->fromJsonString($schema));
        }

        $encodedData = json_encode($data, JSON_THROW_ON_ERROR);

        if ($encodedData === false) {
            throw JsonException::couldNotEncode($data);
        }

        $data = json_decode($encodedData, true, 512, JSON_THROW_ON_ERROR);

        return $messageType::fromArray((array) $data);
    }

    /**
     * @return array<mixed>
     */
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
                'Invalid message passed to \'%s\'. This should be an immutable record but got \'%s\' instead.',
                __METHOD__,
                get_debug_type($customMessage)
            )
        );
    }

    /**
     * @inheritDoc
     */
    public function decorateCommand($customCommand): MessageBag
    {
        return new MessageBag(
            $customCommand::class,
            MessageBag::TYPE_COMMAND,
            $customCommand
        );
    }

    /**
     * @inheritDoc
     */
    public function decorateEvent($customEvent): MessageBag
    {
        return new MessageBag(
            $customEvent::class,
            MessageBag::TYPE_EVENT,
            $customEvent
        );
    }

    /**
     * @inheritDoc
     */
    public function getAggregateIdFromCommand(string $aggregateIdPayloadKey, $command): string
    {
        if ($command instanceof AggregateCommand) {
            return $command->__aggregateId();
        }

        throw new RuntimeException(
            sprintf(
                'Unknown command. Cannot get the aggregate id from command \'%s\'.',
                $command::class
            )
        );
    }

    /**
     * @inheritDoc
     */
    public function callCommandPreProcessor($customCommand, $preProcessor)
    {
        return $preProcessor($customCommand);
    }

    /**
     * @inheritDoc
     */
    public function callCommandController($customCommand, $controller)
    {
        return $controller($customCommand);
    }

    /**
     * @inheritDoc
     */
    public function callContextProvider($customCommand, $contextProvider)
    {
        return $contextProvider($customCommand);
    }

    public function callResolver(Query|Message $customQuery, mixed $resolver): mixed
    {
        return $resolver($customQuery);
    }
}
