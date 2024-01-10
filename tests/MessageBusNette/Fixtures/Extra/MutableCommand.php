<?php
declare(strict_types = 1);

namespace NepadaTests\MessageBusNette\Fixtures\Extra;

use Nepada\MessageBus\Commands\Command;

final class MutableCommand implements Command
{

    public function __construct(
        public bool $shouldFail = false,
    )
    {
    }

}
