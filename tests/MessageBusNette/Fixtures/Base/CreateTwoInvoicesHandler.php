<?php
declare(strict_types = 1);

namespace NepadaTests\MessageBusNette\Fixtures\Base;

use Nepada\MessageBus\Commands\CommandBus;
use Nepada\MessageBus\Commands\CommandHandler;

final class CreateTwoInvoicesHandler implements CommandHandler
{

    public function __construct(
        private CommandBus $commandBus,
    )
    {
    }

    public function __invoke(CreateTwoInvoicesCommand $command): void
    {
        $this->commandBus->handle(new CreateInvoiceCommand());
        $this->commandBus->handle(new CreateInvoiceCommand($command->shouldSecondFail()));
    }

}
