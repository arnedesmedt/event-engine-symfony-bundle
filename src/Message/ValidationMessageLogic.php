<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Message;

use ADS\Bundle\EventEngineBundle\Validator\SpecificationValidation;
use Symfony\Component\Validator\Mapping\ClassMetadata;

use function class_exists;
use function str_replace;

trait ValidationMessageLogic
{
    /** @return array<class-string> */
    public function specificationServices(): array
    {
        return [];
    }

    public static function loadValidatorMetadata(ClassMetadata $metadata): void
    {
        $metadata->addConstraint(new SpecificationValidation());
    }

    /** @return class-string<object&callable>|null */
    public static function validationClass(): string|null
    {
        $validationClass = str_replace('\\Command\\', '\\Validation\\Command\\', static::class);
        $validationClass = str_replace('\\Query\\', '\\Validation\\Query\\', $validationClass);

        return class_exists($validationClass)
            ? $validationClass
            : null;
    }
}
