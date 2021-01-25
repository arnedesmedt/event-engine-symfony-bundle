<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Repository;

use EventEngine\JsonSchema\JsonSchemaAwareRecord;

abstract class DefaultProjectionRepository extends DefaultStateRepository implements ProjectionRepository
{
    private const STATE_KEY = 'state';

    public function upsertState(string $docId, JsonSchemaAwareRecord $state): void
    {
        $this->documentStore->upsertDoc(
            $this->documentStoreName,
            $docId,
            [
                self::STATE_KEY => $state->toArray(),
            ]
        );
    }

    public function deleteDoc(string $docId): void
    {
        $this->documentStore->deleteDoc(
            $this->documentStoreName,
            $docId
        );
    }
}
