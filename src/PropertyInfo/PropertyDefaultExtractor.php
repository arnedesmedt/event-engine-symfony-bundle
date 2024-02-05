<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\PropertyInfo;

use ADS\Util\ScalarUtil;

use function method_exists;

class PropertyDefaultExtractor
{
    /** @param class-string $class */
    public function fromClassAndProperty(string $class, string $property): mixed
    {
        if (! method_exists($class, '__defaultProperties')) {
            return null;
        }

        $metadataDefaultProperties = $class::__defaultProperties();

        if (isset($metadataDefaultProperties[$property])) {
            return ScalarUtil::toScalar($metadataDefaultProperties[$property]);
        }

        return null;
    }
}
