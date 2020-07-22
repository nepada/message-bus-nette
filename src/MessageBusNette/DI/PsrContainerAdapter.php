<?php
declare(strict_types = 1);

namespace Nepada\MessageBusNette\DI;

use Nette\DI\Container;
use Psr\Container\ContainerInterface;

final class PsrContainerAdapter implements ContainerInterface
{

    private Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
     * @param string $id
     * @return object
     */
    public function get($id): object
    {
        return $this->container->getService($id);
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
     * @param string $id
     * @return bool
     */
    public function has($id): bool
    {
        return $this->container->hasService($id);
    }

}
