<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Port;

use ADS\Bundle\EventEngineBundle\Message\AggregateCommand;
use ADS\Bundle\EventEngineBundle\Message\Query;
use EventEngine\Data\ImmutableRecord;
use EventEngine\Messaging\Message;
use EventEngine\Messaging\MessageBag;
use EventEngine\Runtime\Functional\Port;
use RuntimeException;
use function get_class;
use function gettype;
use function is_array;
use function is_object;
use function sprintf;

final class MessagePort implements Port
{
    /**
     * @inheritDoc
     */
    public function deserialize(Message $message)
    {
        /** @var ImmutableRecord $messageType */
        $messageType = $message->messageName();

        return $messageType::fromArray($message->payload());
    }

    /**
     * @param mixed $customMessage
     *
     * @return array<mixed>
     */
    public function serializePayload($customMessage) : array
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
    public function decorateCommand($customCommand) : MessageBag
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
    public function decorateEvent($customEvent) : MessageBag
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
    public function getAggregateIdFromCommand(string $aggregateIdPayloadKey, $command) : string
    {
        if ($command instanceof AggregateCommand) {
            return $command->aggregateId();
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
        return $resolver->resolve($customQuery);
    }
}
