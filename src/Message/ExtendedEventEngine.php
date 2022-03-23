<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Message;

use EventEngine\Messaging\Message;
use EventEngine\Messaging\MessageProducer;
use EventEngine\Runtime\Flavour;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Throwable;

use function reset;

final class ExtendedEventEngine implements MessageProducer
{
    public function __construct(
        private Flavour $flavour,
        private MessageBusInterface $commandBus,
        private MessageBusInterface $eventBus,
        private MessageBusInterface $queryBus,
    ) {
    }

    public function produce(Message $message): mixed
    {
        $transferableMessage = $this->flavour->prepareNetworkTransmission($message);

        try {
            /** @var Envelope $envelop */
            $envelop = match ($message->messageType()) {
                Message::TYPE_COMMAND => $this->commandBus->dispatch($transferableMessage),
                Message::TYPE_EVENT => $this->eventBus->dispatch($transferableMessage),
                default => $this->queryBus->dispatch($transferableMessage),
            };
        } catch (HandlerFailedException $exception) {
            while ($exception instanceof HandlerFailedException) {
                /** @var Throwable $exception */
                $exception = $exception->getPrevious();
            }

            throw $exception;
        }

        $handledStamps = $envelop->all(HandledStamp::class);

        if (empty($handledStamps)) {
            return $envelop;
        }

        /** @var HandledStamp $handledStamp */
        $handledStamp = reset($handledStamps);

        return $handledStamp->getResult();
    }
}
