<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Validator;

use EventEngine\Messaging\MessageBag;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class Validator implements ValidatorInterface
{
    public function __construct(private readonly ValidatorInterface $validator)
    {
    }

    /**
     * @inheritDoc
     */
    public function getMetadataFor($value)
    {
        return $this->validator->getMetadataFor($value);
    }

    /**
     * @inheritDoc
     */
    public function hasMetadataFor($value)
    {
        return $this->validator->hasMetadataFor($value);
    }

    /**
     * @return ConstraintViolationListInterface<mixed>
     *
     * @inheritDoc
     */
    public function validate($value, $constraints = null, $groups = null)
    {
        if ($value instanceof MessageBag) {
            $value = $value->get(MessageBag::MESSAGE);
        }

        return $this->validator->validate($value, $constraints, $groups);
    }

    /**
     * @return ConstraintViolationListInterface<mixed>
     *
     * @inheritDoc
     */
    public function validateProperty(object $object, string $propertyName, $groups = null)
    {
        return $this->validator->validateProperty($object, $propertyName, $groups);
    }

    /**
     * @return ConstraintViolationListInterface<mixed>
     *
     * @inheritDoc
     */
    public function validatePropertyValue($objectOrClass, string $propertyName, $value, $groups = null)
    {
        return $this->validator->validatePropertyValue($objectOrClass, $propertyName, $value, $groups);
    }

    /**
     * @inheritDoc
     */
    public function startContext()
    {
        return $this->validator->startContext();
    }

    /**
     * @inheritDoc
     */
    public function inContext(ExecutionContextInterface $context)
    {
        return $this->validator->inContext($context);
    }
}
