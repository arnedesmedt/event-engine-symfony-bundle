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
    public function __construct(protected readonly ContainerInterface $container)
    {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (! $value instanceof ValidationMessage) {
            return;
        }

        $this->generalValidate($value);
        $this->messageValidate($value);
    }

    private function generalValidate(ValidationMessage $value): void
    {
        if (! method_exists($this, 'specifications')) {
            return;
        }

        $neededServices = $this->convertClassesToServices($this->generalServices());
        $this->specifications($value, ...$neededServices);
    }

    private function messageValidate(ValidationMessage $value): void
    {
        if (! method_exists($value, 'specifications')) {
            return;
        }

        $neededServices = $this->convertClassesToServices($value->specificationServices());
        $value->specifications(...$neededServices);
    }

    /**
     * @return array<class-string>
     */
    protected function generalServices(): array
    {
        return [];
    }

    /**
     * @param array<class-string> $neededServiceClasses
     *
     * @return mixed[]
     */
    protected function convertClassesToServices(array $neededServiceClasses): array
    {
        return array_map(
            fn (string $class) => $this->convertClassToService($class),
            $neededServiceClasses
        );
    }

    /**
     * @param class-string $class
     */
    protected function convertClassToService(string $class): mixed
    {
        return $this->container->get($class);
    }
}
