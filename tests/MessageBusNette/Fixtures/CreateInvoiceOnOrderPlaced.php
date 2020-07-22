<?php
declare(strict_types = 1);

namespace NepadaTests\MessageBusNette\Fixtures;

use Nepada\MessageBus\Commands\CommandBus;
use Nepada\MessageBus\Events\EventSubscriber;

/**
 * Subscriber triggering another command.
 */
final class CreateInvoiceOnOrderPlaced implements EventSubscriber
{

    private CommandBus $commandBus;

    public function __construct(CommandBus $commandBus)
    {
        $this->commandBus = $commandBus;
    }

    public function __invoke(OrderPlacedEvent $event): void
    {
        $this->commandBus->handle(new CreateInvoiceCommand());
    }

}
