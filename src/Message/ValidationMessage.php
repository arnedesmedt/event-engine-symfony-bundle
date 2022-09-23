<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Message;

use Symfony\Component\Validator\Mapping\ClassMetadata;

interface ValidationMessage
{
    public static function loadValidatorMetadata(ClassMetadata $metadata): void;

    /**
     * @return string[]
     */
    public function specificationServices(): array;
}
