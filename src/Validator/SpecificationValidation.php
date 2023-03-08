<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Validator;

use Symfony\Component\Validator\Constraint;

class SpecificationValidation extends Constraint
{
    /** @return string|array<string> */
    public function getTargets(): string|array
    {
        return self::CLASS_CONSTRAINT;
    }

    public function validatedBy(): string
    {
        return SpecificationValidator::class;
    }
}
