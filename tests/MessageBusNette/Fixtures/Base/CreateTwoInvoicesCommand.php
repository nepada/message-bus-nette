<?php
declare(strict_types = 1);

namespace NepadaTests\MessageBusNette\Fixtures\Base;

use Nepada\MessageBus\Commands\Command;

final readonly class CreateTwoInvoicesCommand implements Command
{

    public function __construct(
        private bool $shouldSecondFail = false,
    )
    {
    }

    public function shouldSecondFail(): bool
    {
        return $this->shouldSecondFail;
    }

}
