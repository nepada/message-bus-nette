<?php
declare(strict_types = 1);

namespace NepadaTests\MessageBusNette\DI;

use Doctrine\ORM\Configuration;
use NepadaTests\Environment;
use Nette\Bootstrap\Configurator;
use Nette\Utils\Random;
use function method_exists;

final class ConfiguratorFactory
{

    public static function configureDoctrineOrmProxies(Configuration $configuration): void
    {
        if (PHP_VERSION_ID >= 8_04_00 && method_exists($configuration, 'enableNativeLazyObjects')) {
            $configuration->enableNativeLazyObjects(true);
        }
    }

    public function create(string $configFile = 'config.neon', ?bool $bleedingEdge = null): Configurator
    {
        $configurator = new Configurator();
        $configurator->setTempDirectory(Environment::getTempDir());
        $configurator->setDebugMode(true);
        $configurator->addStaticParameters([
            'appDir' => __DIR__ . '/../Fixtures',
            'databaseFile' => Environment::getTempDir() . '/' . Random::generate() . '.sqlite',
        ]);
        $configurator->addConfig(__DIR__ . "/../Fixtures/config/{$configFile}");
        if ($bleedingEdge !== null) {
            $bleedingEdgeConfigName = $bleedingEdge ? 'enable' : 'disable';
            $configurator->addConfig(__DIR__ . "/../Fixtures/config/{$bleedingEdgeConfigName}BleedingEdge.neon");
        }
        return $configurator;
    }

}
