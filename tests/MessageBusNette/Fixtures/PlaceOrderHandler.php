<?php
declare(strict_types = 1);

namespace NepadaTests\MessageBusNette\Fixtures;

use Nepada\MessageBus\Commands\CommandHandler;
use Nepada\MessageBus\Events\EventDispatcher;

/**
 * Handler dispatching event before possible failure.
 */
final class PlaceOrderHandler implements CommandHandler
{

    private EventDispatcher $eventDispatcher;

    public function __construct(EventDispatcher $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    public function __invoke(PlaceOrderCommand $command): void
    {
        $this->eventDispatcher->dispatch(new OrderPlacedEvent());

        if ($command->shouldFail) {
            throw new FailedToPlaceOrderException('Failed to place order');
        }
    }

}
