<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Validator;

use ReflectionClass;
use RuntimeException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidatorInterface;

use function sprintf;

final class SpecificationValidation extends Constraint
{
    private ?string $customValidatorClass = null;

    /**
     * @return string|array<string>
     */
    public function getTargets(): string|array
    {
        return self::CLASS_CONSTRAINT;
    }

    public function validatedBy(): string
    {
        return $this->customValidatorClass ?? SpecificationValidator::class;
    }

    /**
     * @param class-string|null $customValidator
     */
    public function setCustomValidator(?string $customValidator): self
    {
        if (
            $customValidator !== null
            && ! (new ReflectionClass($customValidator))->implementsInterface(ConstraintValidatorInterface::class)
        ) {
            throw new RuntimeException(
                sprintf(
                    'Custom validator \'%s\', doesn\'t implement \'%s\'.',
                    $customValidator,
                    ConstraintValidatorInterface::class
                )
            );
        }

        $this->customValidatorClass = $customValidator;

        return $this;
    }
}
