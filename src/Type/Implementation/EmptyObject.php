<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Type\Implementation;

use ADS\Bundle\EventEngineBundle\Type\Type;
use TeamBlue\JsonImmutableObjects\JsonSchemaAwareRecordLogic;

class EmptyObject implements Type
{
    use JsonSchemaAwareRecordLogic;
}
