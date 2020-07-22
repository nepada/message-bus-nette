<?php
declare(strict_types = 1);

namespace NepadaTests\MessageBusNette\Fixtures;

use Nepada\MessageBus\Events\Event;

/**
 * Event with no subscribers.
 */
final class InvoiceCreatedEvent implements Event
{

}
