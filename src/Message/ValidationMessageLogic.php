<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Message;

use ADS\Bundle\EventEngineBundle\Validator\SpecificationValidation;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Mapping\ClassMetadata;

trait ValidationMessageLogic
{
    /**
     * @return array<class-string>
     */
    public function specificationServices(): array
    {
        return [];
    }

    public static function loadValidatorMetadata(ClassMetadata $metadata): void
    {
        $metadata->addConstraint((new SpecificationValidation())->setCustomValidator(static::customValidator()));
    }

    /**
     * @return class-string<ConstraintValidator>|null
     */
    protected static function customValidator(): ?string
    {
        return null;
    }
}
