<?php
declare(strict_types = 1);

namespace NepadaTests\MessageBusNette\Fixtures\Base;

use Nepada\MessageBus\Events\Event;

/**
 * Event with no subscribers.
 */
final readonly class InvoiceCreatedEvent implements Event
{

}
