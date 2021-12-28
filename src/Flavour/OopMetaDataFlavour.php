<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Flavour;

use Closure;
use EventEngine\Messaging\CommandDispatchResult;
use EventEngine\Messaging\Message;
use EventEngine\Messaging\MessageFactory;
use EventEngine\Messaging\MessageFactoryAware;
use EventEngine\Runtime\Flavour;
use EventEngine\Runtime\OopFlavour;
use Generator;

final class OopMetaDataFlavour implements Flavour, MessageFactoryAware
{
    public function __construct(
        private OopFlavour $oopFlavour,
        private FunctionalMetaDataFlavour $functionalMetaDataFlavour
    ) {
    }

    /**
     * @inheritDoc
     */
    public function callCommandPreProcessor($preProcessor, Message $command)
    {
        FunctionalMetaDataFlavour::addMessageUuid($preProcessor, $command);

        return $this->oopFlavour->callCommandPreProcessor($preProcessor, $command);
    }

    /**
     * @return array<array<mixed>|Message>|CommandDispatchResult
     *
     * @inheritDoc
     */
    public function callCommandController($controller, Message $command): array|CommandDispatchResult
    {
        FunctionalMetaDataFlavour::addMessageUuid($controller, $command);

        return $this->oopFlavour->callCommandController($controller, $command);
    }

    public function getAggregateIdFromCommand(string $aggregateIdPayloadKey, Message $command): string
    {
        return $this->oopFlavour->getAggregateIdFromCommand($aggregateIdPayloadKey, $command);
    }

    /**
     * @inheritDoc
     */
    public function callContextProvider($contextProvider, Message $command)
    {
        return $this->oopFlavour->callContextProvider($contextProvider, $command);
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
            FunctionalMetaDataFlavour::addMessageUuid($contextService, $command);
        }

        return $this->oopFlavour->callAggregateFactory(
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
            FunctionalMetaDataFlavour::addMessageUuid($contextService, $command);
        }

        return $this->oopFlavour->callSubsequentAggregateFunction(
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
        return $this->oopFlavour->callApplyFirstEvent($applyFunction, $event);
    }

    /**
     * @inheritDoc
     */
    public function callApplySubsequentEvent(callable $applyFunction, $aggregateState, Message $event)
    {
        return $this->oopFlavour->callApplySubsequentEvent($applyFunction, $aggregateState, $event);
    }

    public function prepareNetworkTransmission(Message $message): Message
    {
        return $this->oopFlavour->prepareNetworkTransmission($message);
    }

    /**
     * @inheritDoc
     */
    public function convertMessageReceivedFromNetwork(Message $message, $aggregateEvent = false): Message
    {
        return $this->oopFlavour->convertMessageReceivedFromNetwork($message, $aggregateEvent);
    }

    /**
     * @inheritDoc
     */
    public function callProjector($projector, string $projectionVersion, string $projectionName, Message $event): void
    {
        $this->oopFlavour->callProjector($projector, $projectionVersion, $projectionName, $event);
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
        return $this->oopFlavour->convertAggregateStateToArray($aggregateType, $aggregateState);
    }

    public function canProvideAggregateMetadata(string $aggregateType): bool
    {
        return $this->oopFlavour->canProvideAggregateMetadata($aggregateType);
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
        return $this->oopFlavour->provideAggregateMetadata($aggregateType, $version, $aggregateState);
    }

    public function canBuildAggregateState(string $aggregateType): bool
    {
        return $this->oopFlavour->canBuildAggregateState($aggregateType);
    }

    /**
     * @param array<mixed> $state
     *
     * @inheritDoc
     */
    public function buildAggregateState(string $aggregateType, array $state, int $version)
    {
        return $this->oopFlavour->buildAggregateState($aggregateType, $state, $version);
    }

    /**
     * @return array<mixed>|Message|null
     *
     * @inheritDoc
     */
    public function callEventListener(callable $listener, Message $event)
    {
        return $this->oopFlavour->callEventListener($listener, $event);
    }

    /**
     * @param Closure $resolver
     *
     * @return mixed
     *
     * @inheritDoc
     */
    public function callQueryResolver($resolver, Message $query)
    {
        return $this->functionalMetaDataFlavour->callQueryResolver($resolver, $query);
    }

    public function setMessageFactory(MessageFactory $messageFactory): void
    {
        $this->oopFlavour->setMessageFactory($messageFactory);
    }
}
