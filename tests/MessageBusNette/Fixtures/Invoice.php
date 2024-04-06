<?php
declare(strict_types = 1);

namespace NepadaTests\MessageBusNette\Fixtures;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Nepada\MessageBusDoctrine\Events\ContainsRecordedEvents;
use Nepada\MessageBusDoctrine\Events\PrivateEventRecorder;
use NepadaTests\MessageBusNette\Fixtures\Base\InvoiceCreatedEvent;

#[Entity]
class Invoice implements ContainsRecordedEvents
{

    use PrivateEventRecorder;

    #[Id]
    #[Column(type: 'integer')]
    #[GeneratedValue(strategy: 'IDENTITY')]
    private ?int $id = null;

    public static function create(): self
    {
        $invoice = new self();
        $invoice->record(new InvoiceCreatedEvent());
        return $invoice;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

}
