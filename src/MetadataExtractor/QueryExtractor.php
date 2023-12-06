<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\MetadataExtractor;

use ADS\Bundle\EventEngineBundle\Query\Query;
use ADS\Bundle\EventEngineBundle\Query\Query as QueryAttribute;
use ADS\Util\MetadataExtractor\MetadataExtractorAware;
use ReflectionClass;

class QueryExtractor
{
    use MetadataExtractorAware;

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
