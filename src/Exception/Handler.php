<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Exception;

use Prooph\EventStore\Exception\ConcurrencyException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

final class Handler implements EventSubscriberInterface
{
    /**
     * @return array<string, string>
     */
    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::EXCEPTION => 'onKernelException'];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        if (! $exception instanceof ConcurrencyException) {
            return;
        }

        throw new ConflictHttpException(
            'Resource already exists.',
            $exception
        );
    }
}
