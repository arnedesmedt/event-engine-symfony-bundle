<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Exception;

use ADS\Bundle\EventEngineBundle\Util\StringUtil;
use EventEngine\Aggregate\Exception\AggregateNotFound;
use EventEngine\JsonSchema\Exception\JsonValidationError;
use Prooph\EventStore\Exception\ConcurrencyException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

use function json_decode;
use function json_encode;
use function preg_match;
use function sprintf;
use function str_replace;

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

        switch (true) {
            case $exception instanceof ConcurrencyException:
                $event->setThrowable(
                    new ConflictHttpException(
                        'Resource already exists.',
                        $exception
                    )
                );
                break;
            case $exception instanceof AggregateNotFound:
                $event->setThrowable(
                    new NotFoundHttpException(
                        $exception->getMessage(),
                        $exception
                    )
                );
                break;
            case $exception instanceof JsonValidationError:
                $message = $exception->getMessage();

                if (preg_match('/field "(.+)" \[(.+)\] ([.\S\s]+)$/m', $message, $matches)) {
                    /** @var string $encodedJson */
                    $encodedJson = json_encode(json_decode($matches[3], true));
                    $message = sprintf(
                        'Payload validation error: field \'%s\' [%s] %s',
                        StringUtil::decamilize($matches[1]),
                        $matches[2],
                        str_replace('"', '\'', $encodedJson)
                    );
                }

                $event->setThrowable(
                    new BadRequestHttpException(
                        $message,
                        $exception
                    )
                );
        }
    }
}
