<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Validator;

use ADS\Bundle\EventEngineBundle\Event\Listener;
use Symfony\Component\Validator\Constraints\Valid;
use Symfony\Component\Validator\ConstraintValidatorInterface;

abstract class ListenerWithValidatedMessage implements Listener
{
    public function __construct(protected ConstraintValidatorInterface $specificationValidator)
    {
    }

    protected function validate(mixed $message): mixed
    {
        $this->specificationValidator->validate($message, new Valid());

        return $message;
    }
}
