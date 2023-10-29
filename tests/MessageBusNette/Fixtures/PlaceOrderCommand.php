<?php
declare(strict_types = 1);

namespace NepadaTests\MessageBusNette\Fixtures;

use Nepada\MessageBus\Commands\Command;

final class PlaceOrderCommand implements Command
{

    public function __construct(
        public readonly bool $shouldFail = false,
    )
    {
    }

}
