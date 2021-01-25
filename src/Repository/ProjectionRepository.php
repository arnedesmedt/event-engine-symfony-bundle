<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Repository;

use EventEngine\JsonSchema\JsonSchemaAwareRecord;

interface ProjectionRepository extends StateRepository
{
    public function upsertState(string $docId, JsonSchemaAwareRecord $state): void;

    public function deleteDoc(string $docId): void;
}
