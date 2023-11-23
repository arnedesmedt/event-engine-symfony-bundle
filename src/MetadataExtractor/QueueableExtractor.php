<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\MetadataExtractor;

use ADS\Bundle\EventEngineBundle\Attribute\Queueable as QueueableAttribute;
use ADS\Bundle\EventEngineBundle\Messenger\Queueable;
use ReflectionClass;

class QueueableExtractor
{
    use ClassOrAttributeExtractor;

    /** @param ReflectionClass<object> $reflectionClass */
    public function queueFromReflectionClass(ReflectionClass $reflectionClass): bool
    {
        $classOrAttributeInstance = $this->needClassOrAttributeInstanceFromReflectionClass(
            $reflectionClass,
            Queueable::class,
            QueueableAttribute::class,
        );

        return $classOrAttributeInstance instanceof QueueableAttribute
            ? $classOrAttributeInstance->queue()
            : $classOrAttributeInstance::__queue();
    }

    /** @param ReflectionClass<object> $reflectionClass */
    public function maxRetriesFromReflectionClass(ReflectionClass $reflectionClass): int
    {
        $classOrAttributeInstance = $this->needClassOrAttributeInstanceFromReflectionClass(
            $reflectionClass,
            Queueable::class,
            QueueableAttribute::class,
        );

        return $classOrAttributeInstance instanceof QueueableAttribute
            ? $classOrAttributeInstance->maxRetries()
            : $classOrAttributeInstance::__maxRetries();
    }

    /** @param ReflectionClass<object> $reflectionClass */
    public function delayInMillisecondsFromReflectionClass(ReflectionClass $reflectionClass): int
    {
        $classOrAttributeInstance = $this->needClassOrAttributeInstanceFromReflectionClass(
            $reflectionClass,
            Queueable::class,
            QueueableAttribute::class,
        );

        return $classOrAttributeInstance instanceof QueueableAttribute
            ? $classOrAttributeInstance->delayInMilliseconds()
            : $classOrAttributeInstance::__delayInMilliseconds();
    }

    /** @param ReflectionClass<object> $reflectionClass */
    public function multiplierFromReflectionClass(ReflectionClass $reflectionClass): int
    {
        $classOrAttributeInstance = $this->needClassOrAttributeInstanceFromReflectionClass(
            $reflectionClass,
            Queueable::class,
            QueueableAttribute::class,
        );

        return $classOrAttributeInstance instanceof QueueableAttribute
            ? $classOrAttributeInstance->multiplier()
            : $classOrAttributeInstance::__multiplier();
    }

    /** @param ReflectionClass<object> $reflectionClass */
    public function maxDelayInMillisecondsFromReflectionClass(ReflectionClass $reflectionClass): int
    {
        $classOrAttributeInstance = $this->needClassOrAttributeInstanceFromReflectionClass(
            $reflectionClass,
            Queueable::class,
            QueueableAttribute::class,
        );

        return $classOrAttributeInstance instanceof QueueableAttribute
            ? $classOrAttributeInstance->maxDelayInMilliseconds()
            : $classOrAttributeInstance::__maxDelayInMilliseconds();
    }

    /** @param ReflectionClass<object> $reflectionClass */
    public function sendToLinkedFailureTransportFromReflectionClass(ReflectionClass $reflectionClass): bool
    {
        $classOrAttributeInstance = $this->needClassOrAttributeInstanceFromReflectionClass(
            $reflectionClass,
            Queueable::class,
            QueueableAttribute::class,
        );

        return $classOrAttributeInstance instanceof QueueableAttribute
            ? $classOrAttributeInstance->sendToLinkedFailureTransport()
            : $classOrAttributeInstance::__sendToLinkedFailureTransport();
    }
}
