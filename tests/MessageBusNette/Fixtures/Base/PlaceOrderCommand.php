<?php
declare(strict_types = 1);

namespace NepadaTests\MessageBusNette\Fixtures\Base;

use Nepada\MessageBus\Commands\Command;

final readonly class PlaceOrderCommand implements Command
{

    public function __construct(
        public bool $shouldFail = false,
    )
    {
    }

}
