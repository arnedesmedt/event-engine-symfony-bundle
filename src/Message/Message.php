<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Message;

use EventEngine\JsonSchema\JsonSchemaAwareRecord;

interface Message extends JsonSchemaAwareRecord
{
}
