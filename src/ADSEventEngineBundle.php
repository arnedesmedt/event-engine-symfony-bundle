<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle;

use ADS\Bundle\EventEngineBundle\DependencyInjection\Compiler\EventEnginePass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class ADSEventEngineBundle extends Bundle
{
    public function build(ContainerBuilder $container) : void
    {
        parent::build($container);

        $container->addCompilerPass(new EventEnginePass());
    }
}
