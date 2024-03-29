<?php
declare(strict_types = 1);

namespace NepadaTests\MessageBusNette\Fixtures\Base;

use Doctrine\ORM\EntityManagerInterface;
use Nepada\MessageBus\Commands\CommandHandler;
use NepadaTests\MessageBusNette\Fixtures\FailedToCreateInvoiceException;
use NepadaTests\MessageBusNette\Fixtures\Invoice;

final class CreateInvoiceHandler implements CommandHandler
{

    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function __invoke(CreateInvoiceCommand $command): void
    {
        $invoice = Invoice::create();
        $this->entityManager->persist($invoice);

        if ($command->shouldFail()) {
            throw new FailedToCreateInvoiceException('Failed to create invoice');
        }
    }

}
