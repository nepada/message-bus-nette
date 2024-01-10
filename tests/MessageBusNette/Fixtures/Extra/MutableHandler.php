<?php
declare(strict_types = 1);

namespace NepadaTests\MessageBusNette\Fixtures\Extra;

use Nepada\MessageBus\Commands\CommandHandler;

final class MutableHandler implements CommandHandler
{

    public function __invoke(MutableCommand $command): void
    {
    }

}
