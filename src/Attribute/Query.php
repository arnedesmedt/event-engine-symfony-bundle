<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Attribute;

use Attribute;
use EventEngine\JsonSchema\JsonSchemaAwareRecord;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

#[Attribute(Attribute::TARGET_CLASS)]
class Query extends Response
{
    /**
     * @param class-string $resolver
     * @param class-string<JsonSchemaAwareRecord> $defaultResponseClass
     */
    public function __construct(
        private readonly string $resolver,
        readonly string $defaultResponseClass,
        readonly int $defaultStatusCode = SymfonyResponse::HTTP_OK,
    ) {
        parent::__construct($defaultResponseClass, $defaultStatusCode);
    }

    /** @return class-string */
    public function resolver(): string
    {
        return $this->resolver;
    }
}
