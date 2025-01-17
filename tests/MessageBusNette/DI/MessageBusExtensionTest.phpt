<?php
declare(strict_types = 1);

namespace NepadaTests\MessageBusNette\DI;

use Nepada\MessageBus\Commands\CommandBus;
use Nepada\MessageBus\Commands\CommandHandlerLocator;
use Nepada\MessageBus\Commands\MessengerCommandBus;
use Nepada\MessageBus\Events\EventDispatcher;
use Nepada\MessageBus\Events\EventSubscribersLocator;
use Nepada\MessageBus\Events\MessengerEventDispatcher;
use NepadaTests\MessageBusNette\Fixtures\Base\CreateInvoiceOnOrderPlaced;
use NepadaTests\MessageBusNette\Fixtures\Base\NotifyCustomerOnOrderPlaced;
use NepadaTests\MessageBusNette\Fixtures\Base\OrderPlacedEvent;
use NepadaTests\MessageBusNette\Fixtures\Base\PlaceOrderCommand;
use NepadaTests\MessageBusNette\Fixtures\Base\PlaceOrderHandler;
use NepadaTests\TestCase;
use Nette;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Handler\HandlerDescriptor;
use Tester\Assert;

require_once __DIR__ . '/../../bootstrap.php';


/**
 * @testCase
 */
class MessageBusExtensionTest extends TestCase
{

    /**
     * @dataProvider bleedingEdgeProvider
     */
    public function testBusses(bool $bleedingEdge): void
    {
        $container = $this->createContainer($bleedingEdge);
        Assert::type(MessengerCommandBus::class, $container->getByType(CommandBus::class));
        Assert::type(MessengerEventDispatcher::class, $container->getByType(EventDispatcher::class));
    }

    /**
     * @dataProvider bleedingEdgeProvider
     */
    public function testCommandHandlerLocator(bool $bleedingEdge): void
    {
        $container = $this->createContainer($bleedingEdge);
        /** @var CommandHandlerLocator $locator */
        $locator = $container->getService('messageBus.commands.handlerLocator');
        Assert::type(CommandHandlerLocator::class, $locator);

        $expectedHandlerTypes = [PlaceOrderHandler::class];
        $handlerTypes = $this->extractNormalizedHandlerTypes($locator->getHandlers(new Envelope(new PlaceOrderCommand())));
        Assert::same($expectedHandlerTypes, $handlerTypes);
    }

    /**
     * @dataProvider bleedingEdgeProvider
     */
    public function testEventSubscribersLocator(bool $bleedingEdge): void
    {
        $container = $this->createContainer($bleedingEdge);
        /** @var EventSubscribersLocator $locator */
        $locator = $container->getService('messageBus.events.handlerLocator');
        Assert::type(EventSubscribersLocator::class, $locator);

        $expectedHandlerTypes = [CreateInvoiceOnOrderPlaced::class, NotifyCustomerOnOrderPlaced::class];
        $handlerTypes = $this->extractNormalizedHandlerTypes($locator->getHandlers(new Envelope(new OrderPlacedEvent())));
        Assert::same($expectedHandlerTypes, $handlerTypes);
    }

    /**
     * @param iterable<HandlerDescriptor> $handlerDescriptors
     * @return list<string>
     */
    private function extractNormalizedHandlerTypes(iterable $handlerDescriptors): array
    {
        $handlerTypes = [];
        /** @var HandlerDescriptor $handlerDescriptor */
        foreach ($handlerDescriptors as $handlerDescriptor) {
            $handlerName = $handlerDescriptor->getName();
            $handlerType = Nette\Utils\Strings::replace($handlerName, '~::__invoke~', '');
            Assert::true(class_exists($handlerType));
            $handlerTypes[] = $handlerType;
        }
        sort($handlerTypes);
        return $handlerTypes;
    }

    /**
     * @return list<mixed[]>
     */
    protected function bleedingEdgeProvider(): array
    {
        return [
            [true],
            [false],
        ];
    }

    private function createContainer(bool $bleedingEdge): Nette\DI\Container
    {
        return (new ConfiguratorFactory())->create('config.neon', $bleedingEdge)->createContainer();
    }

}


(new MessageBusExtensionTest())->run();
