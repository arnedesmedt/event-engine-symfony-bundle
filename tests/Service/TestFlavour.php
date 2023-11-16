<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Tests\Service;

use ADS\Bundle\EventEngineBundle\Aggregate\AggregateRoot;
use ADS\Bundle\EventEngineBundle\Tests\Object\Aggregate\TestAggregate;
use ADS\Bundle\EventEngineBundle\Tests\Object\Event\TestAttributeEvent;
use ADS\Bundle\EventEngineBundle\Tests\Object\Event\TestInterfaceEvent;
use EventEngine\JsonSchema\JsonSchemaAwareRecord;
use EventEngine\Messaging\CommandDispatchResult;
use EventEngine\Messaging\Message;
use EventEngine\Messaging\MessageBag;
use EventEngine\Runtime\Flavour;
use Generator;

/** @SuppressWarnings(PHPMD) */
class TestFlavour implements Flavour
{
    /** @param class-string<callable> $preProcessor */
    public function callCommandPreProcessor($preProcessor, Message $command): Message
    {
        $commandName = $command->messageName();
        $payload = $command->payload();

        $newCommand =  $preProcessor($commandName::fromArray($payload));

        $messageBag = new MessageBag(
            $newCommand::class,
            MessageBag::TYPE_COMMAND,
            $newCommand,
        );

        return $messageBag->withValidatedPayload($payload);
    }

    /** @param class-string<callable> $controller */
    public function callCommandController($controller, Message $command): CommandDispatchResult
    {
        $commandName = $command->messageName();
        $payload = $command->payload();

        $controller($commandName::fromArray($payload));

        return CommandDispatchResult::forCommandHandledByController($command);
    }

    public function getAggregateIdFromCommand(string $aggregateIdPayloadKey, Message $command): string
    {
        $payload = $command->payload();

        return $payload[$aggregateIdPayloadKey];
    }

    /** @param class-string<callable> $contextProvider */
    public function callContextProvider($contextProvider, Message $command): mixed
    {
        // TODO: Implement callContextProvider() method.
    }

    /**
     * @param object ...$contextServices
     *
     * @return array<string, mixed>
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
     */
    public function callAggregateFactory(
        string $aggregateType,
        callable $aggregateFunction,
        Message $command,
        ...$contextServices,
    ): Generator {
        $events = [
            TestAttributeEvent::fromArray(['test' => 'test']),
        ];

        foreach ($events as $event) {
            $eventMessage = new MessageBag(
                $event::class,
                MessageBag::TYPE_EVENT,
                $event,
            );

            yield $eventMessage;
        }
    }

    /**
     * @param AggregateRoot<JsonSchemaAwareRecord> $aggregateState
     * @param object ...$contextServices
     *
     * @return array<string, mixed>
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
     */
    public function callSubsequentAggregateFunction(
        string $aggregateType,
        callable $aggregateFunction,
        $aggregateState,
        Message $command,
        ...$contextServices,
    ): Generator {
        $events = [
            TestInterfaceEvent::fromArray(['test' => 'test']),
        ];

        foreach ($events as $event) {
            $eventMessage = new MessageBag(
                $event::class,
                MessageBag::TYPE_EVENT,
                $event,
            );

            yield $eventMessage;
        }
    }

    /** @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingAnyTypeHint */
    public function callApplyFirstEvent(callable $applyFunction, Message $event)
    {
        $aggregateState = TestAggregate::reconstituteFromHistory();

        $aggregateState->apply($event->get(MessageBag::MESSAGE));

        return $aggregateState;
    }

    /**
     * @param AggregateRoot<JsonSchemaAwareRecord> $aggregateState
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingAnyTypeHint
     */
    public function callApplySubsequentEvent(callable $applyFunction, $aggregateState, Message $event)
    {
        $aggregateState->apply($event->get(MessageBag::MESSAGE));

        return $aggregateState;
    }

    public function prepareNetworkTransmission(Message $message): Message
    {
        return $message;
    }

    /**
     * @param bool $aggregateEvent
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
     */
    public function convertMessageReceivedFromNetwork(Message $message, $aggregateEvent = false): Message
    {
        return $message;
    }

    /** @param class-string<callable> $projector */
    public function callProjector($projector, string $projectionVersion, string $projectionName, Message $event): void
    {
    }

    /**
     * @param AggregateRoot<JsonSchemaAwareRecord> $aggregateState
     *
     * @return array<string, mixed>
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
     */
    public function convertAggregateStateToArray(string $aggregateType, $aggregateState): array
    {
        return [];
    }

    public function canProvideAggregateMetadata(string $aggregateType): bool
    {
        return true;
    }

    /**
     * @param AggregateRoot<JsonSchemaAwareRecord> $aggregateState
     *
     * @return array<string, mixed>
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
     */
    public function provideAggregateMetadata(string $aggregateType, int $version, $aggregateState): array
    {
        return [];
    }

    public function canBuildAggregateState(string $aggregateType): bool
    {
        return true;
    }

    /**
     * @param array<string, mixed> $state
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingAnyTypeHint
     */
    public function buildAggregateState(string $aggregateType, array $state, int $version)
    {
        return TestAggregate::reconstituteFromStateArray(['test' => 'test']);
    }

    public function callEventListener(callable $listener, Message $event): mixed
    {
        $eventName = $event->messageName();
        $payload = $event->payload();

        return $listener($eventName::fromArray($payload));
    }

    /** @param class-string<callable> $resolver */
    public function callQueryResolver($resolver, Message $query): mixed
    {
        $queryName = $query->messageName();
        $payload = $query->payload();

        return $resolver($queryName::fromArray($payload));
    }
}
