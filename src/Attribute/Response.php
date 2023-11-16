<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Attribute;

use Attribute;
use EventEngine\JsonSchema\JsonSchemaAwareRecord;

#[Attribute(Attribute::TARGET_CLASS)]
class Response
{
    /** @param array<int, class-string<JsonSchemaAwareRecord>> $responseClassesPerStatusCode */
    public function __construct(
        private readonly int|null $defaultStatusCode = null,
        private readonly array $responseClassesPerStatusCode = [],
    ) {
    }

    public function defaultStatusCode(): int|null
    {
        return $this->defaultStatusCode;
    }

    /** @return array<int, class-string<JsonSchemaAwareRecord>> */
    public function responseClassesPerStatusCode(): array
    {
        return $this->responseClassesPerStatusCode;
    }
}
