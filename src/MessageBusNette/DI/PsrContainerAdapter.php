<?php
declare(strict_types = 1);

namespace Nepada\MessageBusNette\DI;

use Nette\DI\Container;
use Psr\Container\ContainerInterface;

final class PsrContainerAdapter implements ContainerInterface
{

    public function __construct(
        private Container $container,
    )
    {
    }

    public function get(string $id): object
    {
        return $this->container->getService($id);
    }

    public function has(string $id): bool
    {
        return $this->container->hasService($id);
    }

}
