<?php
declare(strict_types = 1);

namespace NepadaTests\MessageBusNette\Fixtures\Base;

use Nepada\MessageBus\Commands\Command;

final readonly class CreateTwoInvoicesCommand implements Command
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
