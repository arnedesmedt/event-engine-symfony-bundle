<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\MetadataExtractor;

use ADS\Bundle\EventEngineBundle\Attribute\Query as QueryAttribute;
use ADS\Bundle\EventEngineBundle\Query\Query;
use ADS\Util\MetadataExtractor\MetadataExtractor;
use ReflectionClass;

class ResolverExtractor
{
    public function __construct(
        private readonly MetadataExtractor $metadataExtractor,
    ) {
    }

    /**
     * @param ReflectionClass<object> $reflectionClass
     *
     * @return class-string
     */
    public function fromReflectionClass(ReflectionClass $reflectionClass): string
    {
        /** @var class-string $resolver */
        $resolver = $this->metadataExtractor->needMetadataFromReflectionClass(
            $reflectionClass,
            [
                /** @param class-string<Query> $class */
                Query::class => static fn (string $class) => $class::__resolver(),
                QueryAttribute::class => static fn (QueryAttribute $attribute): string => $attribute->resolver(),
            ],
        );

        return $resolver;
    }
}
