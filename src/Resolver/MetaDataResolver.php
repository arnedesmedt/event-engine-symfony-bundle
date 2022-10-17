<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Resolver;

interface MetaDataResolver
{
    /**
     * @param array<string, mixed> $metaData
     */
    public function setMetaData(array $metaData): static;
}
