<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Projector;

use EventEngine\DocumentStore\DocumentStore;
use EventEngine\Projecting\AggregateProjector;
use LogicException;

use function is_string;
use function preg_replace;
use function strrpos;
use function strtolower;
use function substr;

abstract class DefaultProjector implements Projector
{
    protected DocumentStore $documentStore;

    public function __construct(DocumentStore $documentStore)
    {
        $this->documentStore = $documentStore;
    }

    public function prepareForRun(string $projectionVersion, string $projectionName): void
    {
        if ($this->documentStore->hasCollection(static::generateCollectionName($projectionVersion, $projectionName))) {
            return;
        }

        $this->documentStore->addCollection(static::generateCollectionName($projectionVersion, $projectionName));
    }

    public function deleteReadModel(string $projectionVersion, string $projectionName): void
    {
        $this->documentStore->dropCollection(static::generateCollectionName($projectionVersion, $projectionName));
    }

    public static function getProjectionName(): string
    {
        $className = static::class;

        $lastPartOfClassName = substr($className, strrpos($className, '\\') + 1);
        if (! is_string($lastPartOfClassName)) {
            throw new LogicException('Unable to get last part of class name from ' . $className);
        }

        $snakeCasedClassName = preg_replace('/(?<!^)[A-Z]/', '_$0', $lastPartOfClassName);
        if (! is_string($snakeCasedClassName)) {
            throw new LogicException('Unable to snake case the string: ' . $lastPartOfClassName);
        }

        return strtolower($snakeCasedClassName);
    }

    protected static function generateCollectionName(string $projectionVersion, string $projectionName): string
    {
        return AggregateProjector::generateCollectionName($projectionVersion, $projectionName);
    }
}
