<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\MetadataExtractor;

use ADS\Bundle\EventEngineBundle\Attribute\Queueable as QueueableAttribute;
use ADS\Bundle\EventEngineBundle\Messenger\Queueable;
use ReflectionClass;

class QueueableExtractor
{
    public function __construct(
        private readonly MetadataExtractor $metadataExtractor,
    ) {
    }

    /** @param ReflectionClass<object> $reflectionClass */
    public function queueFromReflectionClass(ReflectionClass $reflectionClass): bool|null
    {
        /** @var bool|null $queue */
        $queue = $this->metadataExtractor->needMetadataFromReflectionClass(
            $reflectionClass,
            [
                /** @param class-string<Queueable> $class */
                Queueable::class => static fn (string $class) => $class::__queue(),
                QueueableAttribute::class => static fn (QueueableAttribute $attribute) => $attribute->queue(),
            ],
        );

        return $queue;
    }

    /** @param ReflectionClass<object> $reflectionClass */
    public function maxRetriesFromReflectionClass(ReflectionClass $reflectionClass): int
    {
        /** @var int $maxRetries */
        $maxRetries = $this->metadataExtractor->needMetadataFromReflectionClass(
            $reflectionClass,
            [
                /** @param class-string<Queueable> $class */
                Queueable::class => static fn (string $class) => $class::__maxRetries(),
                QueueableAttribute::class => static fn (QueueableAttribute $attribute) => $attribute->maxRetries(),
            ],
        );

        return $maxRetries;
    }

    /** @param ReflectionClass<object> $reflectionClass */
    public function delayInMillisecondsFromReflectionClass(ReflectionClass $reflectionClass): int
    {
        /** @var int $delayInMilliseconds */
        $delayInMilliseconds = $this->metadataExtractor->needMetadataFromReflectionClass(
            $reflectionClass,
            [
                /** @param class-string<Queueable> $class */
                Queueable::class => static fn (string $class) => $class::__delayInMilliseconds(),
                QueueableAttribute::class => static fn (
                    QueueableAttribute $attribute,
                ) => $attribute->delayInMilliseconds(),
            ],
        );

        return $delayInMilliseconds;
    }

    /** @param ReflectionClass<object> $reflectionClass */
    public function multiplierFromReflectionClass(ReflectionClass $reflectionClass): int
    {
        /** @var int $multiplier */
        $multiplier = $this->metadataExtractor->needMetadataFromReflectionClass(
            $reflectionClass,
            [
                /** @param class-string<Queueable> $class */
                Queueable::class => static fn (string $class) => $class::__multiplier(),
                QueueableAttribute::class => static fn (QueueableAttribute $attribute) => $attribute->multiplier(),
            ],
        );

        return $multiplier;
    }

    /** @param ReflectionClass<object> $reflectionClass */
    public function maxDelayInMillisecondsFromReflectionClass(ReflectionClass $reflectionClass): int
    {
        /** @var int $maxDelayInMilliseconds */
        $maxDelayInMilliseconds = $this->metadataExtractor->needMetadataFromReflectionClass(
            $reflectionClass,
            [
                /** @param class-string<Queueable> $class */
                Queueable::class => static fn (string $class) => $class::__maxDelayInMilliseconds(),
                QueueableAttribute::class => static fn (
                    QueueableAttribute $attribute,
                ) => $attribute->maxDelayInMilliseconds(),
            ],
        );

        return $maxDelayInMilliseconds;
    }

    /** @param ReflectionClass<object> $reflectionClass */
    public function sendToLinkedFailureTransportFromReflectionClass(ReflectionClass $reflectionClass): bool
    {
        /** @var bool $sendToLinkedFailureTransport */
        $sendToLinkedFailureTransport = $this->metadataExtractor->needMetadataFromReflectionClass(
            $reflectionClass,
            [
                /** @param class-string<Queueable> $class */
                Queueable::class => static fn (string $class) => $class::__sendToLinkedFailureTransport(),
                QueueableAttribute::class => static fn (
                    QueueableAttribute $attribute,
                ) => $attribute->sendToLinkedFailureTransport(),
            ],
        );

        return $sendToLinkedFailureTransport;
    }
}
