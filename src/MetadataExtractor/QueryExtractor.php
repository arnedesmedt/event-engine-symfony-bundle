<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\MetadataExtractor;

use ADS\Bundle\EventEngineBundle\Attribute\Query as QueryAttribute;
use ADS\Bundle\EventEngineBundle\Query\Query;
use ReflectionClass;
use TeamBlue\Util\MetadataExtractor\MetadataExtractor;

class QueryExtractor
{
    public function __construct(
        private readonly MetadataExtractor $metadataExtractor,
    ) {
    }

    /** @param ReflectionClass<object> $reflectionClass */
    public function isQueryFromReflectionClass(ReflectionClass $reflectionClass): bool
    {
        return $this->metadataExtractor->hasAttributeOrClassFromReflectionClass(
            $reflectionClass,
            [
                QueryAttribute::class,
                Query::class,
            ],
        );
    }
}
