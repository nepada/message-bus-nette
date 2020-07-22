<?php
declare(strict_types = 1);

namespace NepadaTests\MessageBusNette\Fixtures;

use Nepada\MessageBus\Commands\Command;

final class CreateTwoInvoicesCommand implements Command
{

    private bool $shouldSecondFail;

    public function __construct(bool $shouldSecondFail = false)
    {
        $this->shouldSecondFail = $shouldSecondFail;
    }

    public function shouldSecondFail(): bool
    {
        return $this->shouldSecondFail;
    }

}
