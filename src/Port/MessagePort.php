<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Port;

use ADS\Bundle\EventEngineBundle\Exception\JsonException;
use ADS\Bundle\EventEngineBundle\Message\AggregateCommand;
use ADS\Bundle\EventEngineBundle\Message\Query;
use EventEngine\Data\ImmutableRecord;
use EventEngine\JsonSchema\JsonSchemaAwareRecord;
use EventEngine\Messaging\Message;
use EventEngine\Messaging\MessageBag;
use EventEngine\Runtime\Functional\Port;
use Opis\JsonSchema\Schema;
use Opis\JsonSchema\Validator;
use ReflectionClass;
use RuntimeException;

use function get_class;
use function gettype;
use function is_array;
use function is_object;
use function json_decode;
use function json_encode;
use function sprintf;

final class MessagePort implements Port
{
    private Validator $validator;

    public function __construct(Validator $validator)
    {
        $this->validator = $validator;
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
            $encodedData = json_encode($data);

            if ($encodedData === false) {
                throw JsonException::couldNotEncode($data);
            }

            $schemaArray = $messageType::__schema()->toArray();
            $schema = json_encode($schemaArray);

            if ($schema === false) {
                throw JsonException::couldNotEncode($schemaArray);
            }

            $data = json_decode($encodedData);
            $this->validator->schemaValidation($data, Schema::fromJsonString($schema));
        }

        return $messageType::fromArray((array) $data);
    }

    /**
     * @param mixed $customMessage
     *
     * @return array<mixed>
     */
    public function serializePayload($customMessage): array
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
                is_object($customMessage) ? get_class($customMessage) : gettype($customMessage)
            )
        );
    }

    /**
     * @inheritDoc
     */
    public function decorateCommand($customCommand): MessageBag
    {
        return new MessageBag(
            get_class($customCommand),
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
            get_class($customEvent),
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
                get_class($command)
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

    /**
     * @param Query|Message $customQuery
     * @param mixed $resolver
     *
     * @return mixed
     */
    public function callResolver($customQuery, $resolver)
    {
        return $resolver($customQuery);
    }
}
