<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Projector;

use EventEngine\EventEngine;
use EventEngine\Messaging\GenericEvent;
use EventEngine\Prooph\V7\EventStore\GenericProophEvent;
use EventEngine\Util\VariableType;
use InvalidArgumentException;
use Prooph\Common\Messaging\Message;
use Prooph\EventStore\Projection\AbstractReadModel;

class ReadModelProxy extends AbstractReadModel
{
    private bool $initialized = false;

    public function __construct(private EventEngine $eventEngine)
    {
    }

    public function handle(string $streamName, Message $event): void
    {
        if (! $event instanceof GenericProophEvent) {
            throw new InvalidArgumentException(
                __METHOD__ . ' expects a ' . GenericProophEvent::class . '. Got ' . VariableType::determine($event)
            );
        }

        /** @var GenericEvent $genericEvent */
        $genericEvent = GenericEvent::fromArray($event->toArray());

        $this->eventEngine->runAllProjections($streamName, $genericEvent);
    }

    public function init(): void
    {
        $this->eventEngine->setUpAllProjections();
        $this->initialized = true;
    }

    public function isInitialized(): bool
    {
        return $this->initialized;
    }

    public function reset(): void
    {
        $this->delete();
    }

    public function delete(): void
    {
        if (! $this->isInitialized()) {
            $this->init();
        }

        $this->eventEngine->deleteAllProjections();

        $this->initialized = false;
    }
}
