<?php
declare(strict_types = 1);

namespace NepadaTests\MessageBusNette\Fixtures\Base;

use Nepada\MessageBus\Commands\Command;

final readonly class CreateInvoiceCommand implements Command
{

    private bool $shouldFail;

    public function __construct(bool $shouldFail = false)
    {
        $this->shouldFail = $shouldFail;
    }

    public function shouldFail(): bool
    {
        return $this->shouldFail;
    }

}
