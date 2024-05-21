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

        $validationClass = $value->validationClass();
        if ($validationClass !== null) {
            /** @var ValidationProcessor $validationObject */
            $validationObject = $this->container->get($validationClass);
            $validationObject($value);

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

        $neededServices = $this->convertClassesToServices($value, $this->generalServices());
        $this->specifications($value, ...$neededServices);
    }

    private function messageValidate(ValidationMessage $value): void
    {
        if (! method_exists($value, 'specifications')) {
            return;
        }

        $neededServices = $this->convertClassesToServices($value, $value->specificationServices());
        $value->specifications(...$neededServices);
    }

    /** @return array<class-string> */
    protected function generalServices(): array
    {
        return [];
    }

    /**
     * @param array<class-string> $neededServiceClasses
     *
     * @return mixed[]
     */
    protected function convertClassesToServices(ValidationMessage $value, array $neededServiceClasses): array
    {
        return array_map(
            fn (string $class): mixed => $this->convertClassToService($value, $class),
            $neededServiceClasses,
        );
    }

    /** @param class-string $class */
    protected function convertClassToService(ValidationMessage $value, string $class): mixed
    {
        return $this->container->get($class);
    }
}
