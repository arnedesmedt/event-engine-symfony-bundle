<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Repository;

use EventEngine\JsonSchema\JsonSchemaAwareRecord;

abstract class DefaultProjectionRepository extends DefaultStateRepository implements ProjectionRepository
{
    private const STATE_KEY = 'state';

    /**
     * @param mixed $docId
     */
    public function upsertState($docId, JsonSchemaAwareRecord $state): void
    {
        $docId = (string) $docId;

        $this->documentStore->upsertDoc(
            $this->documentStoreName,
            $docId,
            [
                self::STATE_KEY => $state->toArray(),
            ]
        );
    }

    /**
     * @param mixed $docId
     */
    public function deleteDoc($docId): void
    {
        $docId = (string) $docId;

        $this->documentStore->deleteDoc(
            $this->documentStoreName,
            $docId
        );
    }
}
