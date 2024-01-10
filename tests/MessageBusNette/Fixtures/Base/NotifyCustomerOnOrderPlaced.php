<?php
declare(strict_types = 1);

namespace NepadaTests\MessageBusNette\Fixtures\Base;

use Nepada\MessageBus\Events\EventSubscriber;

final class NotifyCustomerOnOrderPlaced implements EventSubscriber
{

    public function __invoke(OrderPlacedEvent $event): void
    {
    }

}
