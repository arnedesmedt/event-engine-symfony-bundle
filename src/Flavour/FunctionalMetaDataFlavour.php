<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Flavour;

use ADS\Bundle\EventEngineBundle\Message\MessageUuidAware;
use ADS\Bundle\EventEngineBundle\Resolver\MetaDataResolver;
use Closure;
use EventEngine\Messaging\CommandDispatchResult;
use EventEngine\Messaging\Message;
use EventEngine\Messaging\MessageBag;
use EventEngine\Messaging\MessageFactory;
use EventEngine\Messaging\MessageFactoryAware;
use EventEngine\Runtime\Flavour;
use EventEngine\Runtime\FunctionalFlavour;
use Generator;
use RuntimeException;

class FunctionalMetaDataFlavour implements Flavour, MessageFactoryAware
{
    public function __construct(private FunctionalFlavour $functionalFlavour)
    {
    }

    public static function addMessageUuid(mixed $service, Message $message): void
    {
        if (! ($service instanceof MessageUuidAware)) {
            return;
        }

        $service->setMessageUuid($message->uuid());
    }

    /**
     * @inheritDoc
     */
    public function callCommandPreProcessor($preProcessor, Message $command)
    {
        self::addMessageUuid($preProcessor, $command);

        return $this->functionalFlavour->callCommandPreProcessor($preProcessor, $command);
    }

    /**
     * @return array<array<mixed>|Message>|CommandDispatchResult
     *
     * @inheritDoc
     */
    public function callCommandController($controller, Message $command): array|CommandDispatchResult
    {
        self::addMessageUuid($controller, $command);

        return $this->functionalFlavour->callCommandController($controller, $command);
    }

    public function getAggregateIdFromCommand(string $aggregateIdPayloadKey, Message $command): string
    {
        return $this->functionalFlavour->getAggregateIdFromCommand($aggregateIdPayloadKey, $command);
    }

    /**
     * @inheritDoc
     */
    public function callContextProvider($contextProvider, Message $command)
    {
        return $this->functionalFlavour->callContextProvider($contextProvider, $command);
    }

    /**
     * @param mixed ...$contextServices
     *
     * @return Generator<mixed>
     *
     * @inheritDoc
     */
    public function callAggregateFactory(
        string $aggregateType,
        callable $aggregateFunction,
        Message $command,
        ...$contextServices
    ): Generator {
        foreach ($contextServices as $contextService) {
            self::addMessageUuid($contextService, $command);
        }

        return $this->functionalFlavour->callAggregateFactory(
            $aggregateType,
            $aggregateFunction,
            $command,
            ...$contextServices
        );
    }

    /**
     * @param mixed ...$contextServices
     *
     * @return Generator<mixed>
     *
     * @inheritDoc
     */
    public function callSubsequentAggregateFunction(
        string $aggregateType,
        callable $aggregateFunction,
        $aggregateState,
        Message $command,
        ...$contextServices
    ): Generator {
        foreach ($contextServices as $contextService) {
            self::addMessageUuid($contextService, $command);
        }

        return $this->functionalFlavour->callSubsequentAggregateFunction(
            $aggregateType,
            $aggregateFunction,
            $aggregateState,
            $command,
            ...$contextServices
        );
    }

    /**
     * @inheritDoc
     */
    public function callApplyFirstEvent(callable $applyFunction, Message $event)
    {
        return $this->functionalFlavour->callApplyFirstEvent($applyFunction, $event);
    }

    /**
     * @inheritDoc
     */
    public function callApplySubsequentEvent(callable $applyFunction, $aggregateState, Message $event)
    {
        return $this->functionalFlavour->callApplySubsequentEvent($applyFunction, $aggregateState, $event);
    }

    public function prepareNetworkTransmission(Message $message): Message
    {
        return $this->functionalFlavour->prepareNetworkTransmission($message);
    }

    /**
     * @inheritDoc
     */
    public function convertMessageReceivedFromNetwork(Message $message, $aggregateEvent = false): Message
    {
        return $this->functionalFlavour->convertMessageReceivedFromNetwork($message, $aggregateEvent);
    }

    /**
     * @inheritDoc
     */
    public function callProjector($projector, string $projectionVersion, string $projectionName, Message $event): void
    {
        $this->functionalFlavour->callProjector($projector, $projectionVersion, $projectionName, $event);
    }

    /**
     * @param mixed $aggregateState
     *
     * @return array<mixed>
     *
     * @inheritDoc
     */
    public function convertAggregateStateToArray(string $aggregateType, $aggregateState): array
    {
        return $this->functionalFlavour->convertAggregateStateToArray($aggregateType, $aggregateState);
    }

    public function canProvideAggregateMetadata(string $aggregateType): bool
    {
        return $this->functionalFlavour->canProvideAggregateMetadata($aggregateType);
    }

    /**
     * @param mixed $aggregateState
     *
     * @return array<mixed>
     *
     * @inheritDoc
     */
    public function provideAggregateMetadata(string $aggregateType, int $version, $aggregateState): array
    {
        return $this->functionalFlavour->provideAggregateMetadata($aggregateType, $version, $aggregateState);
    }

    public function canBuildAggregateState(string $aggregateType): bool
    {
        return $this->functionalFlavour->canBuildAggregateState($aggregateType);
    }

    /**
     * @param array<mixed> $state
     *
     * @inheritDoc
     */
    public function buildAggregateState(string $aggregateType, array $state, int $version)
    {
        return $this->functionalFlavour->buildAggregateState($aggregateType, $state, $version);
    }

    /**
     * @return array<mixed>|Message|null
     *
     * @inheritDoc
     */
    public function callEventListener(callable $listener, Message $event)
    {
        return $this->functionalFlavour->callEventListener($listener, $event);
    }

    /**
     * @param Closure|MetaDataResolver $resolver
     *
     * @return mixed
     *
     * @inheritDoc
     */
    public function callQueryResolver($resolver, Message $query)
    {
        if (! $query instanceof MessageBag) {
            throw new RuntimeException(
                'Message passed to ' . __METHOD__ . ' should be of type ' . MessageBag::class
            );
        }

        $queryMessage = $query->get(MessageBag::MESSAGE);
        $metadata = $query->metadata();

        if ($resolver instanceof MetaDataResolver) {
            $resolver->setMetaData($metadata);
        }

        self::addMessageUuid($resolver, $query);

        return $resolver($queryMessage);
    }

    public function setMessageFactory(MessageFactory $messageFactory): void
    {
        $this->functionalFlavour->setMessageFactory($messageFactory);
    }
}
