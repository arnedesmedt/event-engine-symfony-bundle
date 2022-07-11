<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Type;

use EventEngine\JsonSchema\AnnotatedType;
use EventEngine\JsonSchema\Type\HasAnnotations;
use EventEngine\JsonSchema\Type\TypeRef;

class AnnotatedTypeRef extends TypeRef implements AnnotatedType
{
    use HasAnnotations;
}
