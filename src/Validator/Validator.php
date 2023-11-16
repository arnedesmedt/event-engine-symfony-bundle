<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Validator;

use EventEngine\Messaging\MessageBag;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\GroupSequence;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Mapping\MetadataInterface;
use Symfony\Component\Validator\Validator\ContextualValidatorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class Validator implements ValidatorInterface
{
    public function __construct(private readonly ValidatorInterface $validator)
    {
    }

    public function getMetadataFor(mixed $value): MetadataInterface
    {
        return $this->validator->getMetadataFor($value);
    }

    public function hasMetadataFor(mixed $value): bool
    {
        return $this->validator->hasMetadataFor($value);
    }

    public function validate(
        mixed $value,
        array|Constraint|null $constraints = null,
        array|GroupSequence|string|null $groups = null,
    ): ConstraintViolationListInterface {
        if ($value instanceof MessageBag) {
            $value = $value->get(MessageBag::MESSAGE);
        }

        return $this->validator->validate($value, $constraints, $groups);
    }

    public function validateProperty(
        object $object,
        string $propertyName,
        array|GroupSequence|string|null $groups = null,
    ): ConstraintViolationListInterface {
        return $this->validator->validateProperty($object, $propertyName, $groups);
    }

    public function validatePropertyValue(
        object|string $objectOrClass,
        string $propertyName,
        mixed $value,
        array|GroupSequence|string|null $groups = null,
    ): ConstraintViolationListInterface {
        return $this->validator->validatePropertyValue($objectOrClass, $propertyName, $value, $groups);
    }

    public function startContext(): ContextualValidatorInterface
    {
        return $this->validator->startContext();
    }

    public function inContext(ExecutionContextInterface $context): ContextualValidatorInterface
    {
        return $this->validator->inContext($context);
    }
}
