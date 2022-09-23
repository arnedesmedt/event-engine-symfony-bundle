<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Validator;

use ADS\Bundle\EventEngineBundle\Message\ValidationMessage;
use Psr\Container\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

use function array_map;
use function method_exists;

class SpecificationValidator extends ConstraintValidator
{
    public function __construct(private readonly ContainerInterface $container)
    {
    }

    /**
     * @param mixed $value
     *
     * @inheritDoc
     */
    public function validate($value, Constraint $constraint): void
    {
        if (! $value instanceof ValidationMessage) {
            return;
        }

        $neededServiceClasses = $value->specificationServices();
        $neededServices = $this->changeServices(
            array_map(
                fn (string $class) => $this->changeService($this->container->get($class)),
                $neededServiceClasses
            )
        );

        if (! method_exists($value, 'specifications')) {
            return;
        }

        $value->specifications(...$neededServices);
    }

    /**
     * @param array<mixed> $services
     *
     * @return array<mixed>
     */
    protected function changeServices(array $services): array
    {
        return $services;
    }

    protected function changeService(mixed $service): mixed
    {
        return $service;
    }
}
