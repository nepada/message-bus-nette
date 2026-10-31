<?php
declare(strict_types = 1);

namespace NepadaTests\MessageBusNette\DI;

use Doctrine\Common\EventManager;
use Doctrine\Common\EventSubscriber;
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

    public static function addEventSubscriber(
        EventManager $eventManager,
        EventSubscriber $subscriber,
    ): void
    {
        // BC with doctrine/event-manager <2.1: it registers listeners by spl_object_hash(), deprecated since PHP 8.6.
        // Once the lowest installable doctrine/event-manager is 2.1 or newer, drop this helper and call
        // addEventSubscriber() directly from the setup of the EventManager service in base.neon.
        @$eventManager->addEventSubscriber($subscriber);
    }

    public function create(
        string $configFile = 'config.neon',
        ?bool $bleedingEdge = null,
    ): Configurator
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
