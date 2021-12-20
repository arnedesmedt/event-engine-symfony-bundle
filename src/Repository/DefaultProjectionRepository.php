<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Repository;

use EventEngine\JsonSchema\JsonSchemaAwareRecord;

use function strval;

abstract class DefaultProjectionRepository extends DefaultStateRepository implements ProjectionRepository
{
    private const STATE_KEY = 'state';

    public function upsertState(mixed $docId, JsonSchemaAwareRecord $state): void
    {
        $docId = strval($docId);

        $this->documentStore->upsertDoc(
            $this->documentStoreName,
            $docId,
            [
                self::STATE_KEY => $state->toArray(),
            ]
        );
    }

    public function deleteDoc(mixed $docId): void
    {
        $docId = strval($docId);

        $this->documentStore->deleteDoc(
            $this->documentStoreName,
            $docId
        );
    }
}
