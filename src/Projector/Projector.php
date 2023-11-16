<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Projector;

use EventEngine\JsonSchema\JsonSchemaAwareCollection;
use EventEngine\JsonSchema\JsonSchemaAwareRecord;
use EventEngine\Projecting\CustomEventProjector;

interface Projector extends CustomEventProjector
{
    /** @return array<int, class-string<JsonSchemaAwareRecord>> */
    public static function events(): array;

    public static function projectionName(): string;

    public static function version(): string;

    public static function generateOwnCollectionName(): string;

    /** @return class-string<JsonSchemaAwareRecord> */
    public static function stateClass(): string;

    /** @return class-string<JsonSchemaAwareCollection> */
    public static function statesClass(): string;
}
