<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Attribute;

use Attribute;
use EventEngine\JsonSchema\JsonSchemaAwareRecord;

#[Attribute(Attribute::TARGET_CLASS)]
class Query extends Response
{
    /**
     * @param class-string $resolver
     * @param array<class-string<JsonSchemaAwareRecord>> $responseClassesPerStatusCode
     */
    public function __construct(
        private readonly string $resolver,
        readonly int|null $defaultStatusCode = null,
        readonly array $responseClassesPerStatusCode = [],
    ) {
        parent::__construct($defaultStatusCode, $responseClassesPerStatusCode);
    }

    /** @return class-string */
    public function resolver(): string
    {
        return $this->resolver;
    }
}
