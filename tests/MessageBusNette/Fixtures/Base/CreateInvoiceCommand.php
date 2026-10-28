<?php
declare(strict_types = 1);

namespace NepadaTests\MessageBusNette\Fixtures\Base;

use Nepada\MessageBus\Commands\Command;

final readonly class CreateInvoiceCommand implements Command
{

    public function __construct(
        private bool $shouldFail = false,
    )
    {
    }

    public function shouldFail(): bool
    {
        return $this->shouldFail;
    }

}
