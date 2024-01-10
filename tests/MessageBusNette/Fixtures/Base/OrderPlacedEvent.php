<?php
declare(strict_types = 1);

namespace NepadaTests\MessageBusNette\Fixtures\Base;

use Nepada\MessageBus\Events\Event;

/**
 * Event with 2 subscribers.
 */
final class OrderPlacedEvent implements Event
{

}
