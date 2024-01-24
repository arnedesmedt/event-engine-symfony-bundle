<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Attribute;

use Attribute;
use EventEngine\JsonSchema\JsonSchemaAwareCollection;
use EventEngine\JsonSchema\JsonSchemaAwareRecord;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

#[Attribute(Attribute::TARGET_CLASS)]
class Response
{
    /** @param class-string<JsonSchemaAwareRecord|JsonSchemaAwareCollection> $defaultResponseClass */
    public function __construct(
        private readonly string $defaultResponseClass,
        private readonly int $defaultStatusCode = SymfonyResponse::HTTP_OK,
    ) {
    }

    public function defaultStatusCode(): int
    {
        return $this->defaultStatusCode;
    }

    /** @return class-string<JsonSchemaAwareRecord|JsonSchemaAwareCollection> */
    public function defaultResponseClass(): string
    {
        return $this->defaultResponseClass;
    }
}
