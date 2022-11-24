<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Messenger\Middleware;

use ADS\Bundle\EventEngineBundle\Messenger\Message\MessageWrapper;
use ADS\Bundle\EventEngineBundle\Messenger\Queueable;
use EventEngine\Messaging\MessageBag;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Stamp\TransportNamesStamp;

class ChangeTransports implements MiddlewareInterface
{
    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        $message = self::message($envelope);
        if (! $message instanceof Queueable) {
            return $stack->next()->handle($envelope, $stack);
        }

        $newTransports = $message::__changeTransports($message);
        $transportNameStamp = $envelope->last(TransportNamesStamp::class);

        if (
            empty($newTransports)
            || $transportNameStamp !== null
        ) {
            return $stack->next()->handle($envelope, $stack);
        }

        return $stack->next()->handle($envelope->with(new TransportNamesStamp($newTransports)), $stack);
    }

    public static function message(Envelope $envelope): mixed
    {
        /** @var MessageWrapper|MessageBag $message */
        $message = $envelope->getMessage();

        if ($message instanceof MessageWrapper) {
            $message = $message->message();
        }

        if ($message instanceof MessageBag) {
            return $message->get('message');
        }

        return null;
    }
}
