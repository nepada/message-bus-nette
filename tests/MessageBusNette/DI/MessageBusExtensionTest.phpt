<?php
declare(strict_types = 1);

namespace NepadaTests\MessageBusNette\DI;

use Nepada\MessageBus\Commands\CommandBus;
use Nepada\MessageBus\Commands\CommandHandlerLocator;
use Nepada\MessageBus\Commands\MessengerCommandBus;
use Nepada\MessageBus\Events\EventDispatcher;
use Nepada\MessageBus\Events\EventSubscribersLocator;
use Nepada\MessageBus\Events\MessengerEventDispatcher;
use NepadaTests\MessageBusNette\Fixtures\CreateInvoiceOnOrderPlaced;
use NepadaTests\MessageBusNette\Fixtures\NotifyCustomerOnOrderPlaced;
use NepadaTests\MessageBusNette\Fixtures\OrderPlacedEvent;
use NepadaTests\MessageBusNette\Fixtures\PlaceOrderCommand;
use NepadaTests\MessageBusNette\Fixtures\PlaceOrderHandler;
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

    public function testBusses(): void
    {
        $container = $this->createContainer();
        Assert::type(MessengerCommandBus::class, $container->getByType(CommandBus::class));
        Assert::type(MessengerEventDispatcher::class, $container->getByType(EventDispatcher::class));
    }

    public function testCommandHandlerLocator(): void
    {
        $container = $this->createContainer();
        /** @var CommandHandlerLocator $locator */
        $locator = $container->getService('messageBus.commands.handlerLocator');
        Assert::type(CommandHandlerLocator::class, $locator);

        $expectedHandlerTypes = [PlaceOrderHandler::class];
        $handlerTypes = $this->extractNormalizedHandlerTypes($locator->getHandlers(new Envelope(new PlaceOrderCommand())));
        Assert::same($expectedHandlerTypes, $handlerTypes);
    }

    public function testEventSubscribersLocator(): void
    {
        $container = $this->createContainer();
        /** @var EventSubscribersLocator $locator */
        $locator = $container->getService('messageBus.events.handlerLocator');
        Assert::type(EventSubscribersLocator::class, $locator);

        $expectedHandlerTypes = [CreateInvoiceOnOrderPlaced::class, NotifyCustomerOnOrderPlaced::class];
        $handlerTypes = $this->extractNormalizedHandlerTypes($locator->getHandlers(new Envelope(new OrderPlacedEvent())));
        Assert::same($expectedHandlerTypes, $handlerTypes);
    }

    /**
     * @param iterable<HandlerDescriptor> $handlerDescriptors
     * @return string[]
     */
    private function extractNormalizedHandlerTypes(iterable $handlerDescriptors): array
    {
        $handlerTypes = [];
        /** @var HandlerDescriptor $handlerDescriptor */
        foreach ($handlerDescriptors as $handlerDescriptor) {
            $handler = $handlerDescriptor->getHandler();
            assert(is_object($handler));
            $handlerTypes[] = get_class($handler);
        }
        sort($handlerTypes);
        return $handlerTypes;
    }

    private function createContainer(): Nette\DI\Container
    {
        return (new ConfiguratorFactory())->create()->createContainer();
    }

}


(new MessageBusExtensionTest())->run();
