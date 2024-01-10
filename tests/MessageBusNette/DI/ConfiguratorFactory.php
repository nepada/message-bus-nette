<?php
declare(strict_types = 1);

namespace NepadaTests\MessageBusNette\DI;

use NepadaTests\Environment;
use Nette\Bootstrap\Configurator;
use Nette\Utils\Random;

final class ConfiguratorFactory
{

    public function create(string $configFile = 'config.neon'): Configurator
    {
        $configurator = new Configurator();
        $configurator->setTempDirectory(Environment::getTempDir());
        $configurator->setDebugMode(true);
        $configurator->addStaticParameters(['appDir' => __DIR__ . '/../Fixtures', 'databaseFile' => Environment::getTempDir() . '/' . Random::generate() . '.sqlite']);
        $configurator->addConfig(__DIR__ . "/../Fixtures/{$configFile}");
        return $configurator;
    }

}
