<?php
declare(strict_types = 1);

namespace NepadaTests\MessageBusNette\Fixtures\Base;

use Nepada\MessageBus\Commands\CommandBus;
use Nepada\MessageBus\Events\EventSubscriber;

/**
 * Subscriber triggering another command.
 */
final class CreateInvoiceOnOrderPlaced implements EventSubscriber
{

    public function __construct(
        private CommandBus $commandBus,
    )
    {
    }

    public function __invoke(OrderPlacedEvent $event): void
    {
        $this->commandBus->handle(new CreateInvoiceCommand());
    }

}
