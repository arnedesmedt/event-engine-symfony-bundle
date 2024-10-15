<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Exception;

use EventEngine\Aggregate\Exception\AggregateNotFound;
use EventEngine\JsonSchema\Exception\JsonValidationError;
use Prooph\EventStore\Exception\ConcurrencyException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use TeamBlue\Util\StringUtil;
use Throwable;

use function json_decode;
use function json_encode;
use function preg_match;
use function sprintf;
use function str_replace;

use const JSON_THROW_ON_ERROR;

final class Handler implements EventSubscriberInterface
{
    /** @return array<string, string> */
    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::EXCEPTION => 'onKernelException'];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        $throwable = match (true) {
            $exception instanceof ConcurrencyException => new ConflictHttpException(
                'Resource already exists.',
                $exception,
            ),
            $exception instanceof AggregateNotFound => new NotFoundHttpException(
                $exception->getMessage(),
                $exception,
            ),
            $exception instanceof JsonValidationError => $this->jsonValidationError($exception),
            default => $exception,
        };

        $event->setThrowable($throwable);
    }

    private function jsonValidationError(JsonValidationError $exception): BadRequestHttpException
    {
        $message = $exception->getMessage();

        if (preg_match('/field "(.+)" \[(.+)\] ([.\S\s]+)$/m', $message, $matches)) {
            try {
                /** @var string $encodedJson */
                $encodedJson = json_encode(
                    json_decode($matches[3], true, 512, JSON_THROW_ON_ERROR),
                    JSON_THROW_ON_ERROR,
                );
            } catch (Throwable) {
                /** @var string $encodedJson */
                $encodedJson = $matches[3];
            }

            $message = sprintf(
                "Payload validation error: field '%s' [%s] %s",
                StringUtil::decamelize($matches[1]),
                $matches[2],
                str_replace('"', "'", $encodedJson),
            );
        }

        return new BadRequestHttpException(
            $message,
            $exception,
        );
    }
}
