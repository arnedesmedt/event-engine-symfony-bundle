<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\MetadataExtractor;

use ADS\Bundle\EventEngineBundle\Attribute\Query as QueryAttribute;
use ADS\Bundle\EventEngineBundle\Query\Query;
use ReflectionClass;

class ResolverExtractor
{
    use ClassOrAttributeExtractor;

    /**
     * @param ReflectionClass<object> $reflectionClass
     *
     * @return class-string
     */
    public function fromReflectionClass(ReflectionClass $reflectionClass): string
    {
        $classOrAttributeInstance = $this->needClassOrAttributeInstanceFromReflectionClass(
            $reflectionClass,
            Query::class,
            QueryAttribute::class,
        );

        /** @var class-string $resolver */
        $resolver = $classOrAttributeInstance instanceof QueryAttribute
            ? $classOrAttributeInstance->resolver()
            : $classOrAttributeInstance::__resolver();

        return $resolver;
    }
}
