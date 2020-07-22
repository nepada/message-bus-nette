<?php
declare(strict_types = 1);

namespace NepadaTests\MessageBusNette\DI;

use NepadaTests\Environment;
use Nette\Configurator;
use Nette\Utils\Random;

final class ConfiguratorFactory
{

    public function create(): Configurator
    {
        $configurator = new Configurator();
        $configurator->setTempDirectory(Environment::getTempDir());
        $configurator->setDebugMode(true);
        $configurator->addParameters(['appDir' => __DIR__ . '/../Fixtures', 'databaseFile' => Environment::getTempDir() . '/' . Random::generate() . '.sqlite']);
        $configurator->addConfig(__DIR__ . '/../Fixtures/config.neon');
        return $configurator;
    }

}
