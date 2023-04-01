<?php
declare(strict_types = 1);

namespace NepadaTests\MessageBusNette\Fixtures;

use Doctrine\ORM\Mapping as ORM;
use Nepada\MessageBusDoctrine\Events\ContainsRecordedEvents;
use Nepada\MessageBusDoctrine\Events\PrivateEventRecorder;

/**
 * @ORM\Entity()
 */
class Invoice implements ContainsRecordedEvents
{

    use PrivateEventRecorder;

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
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
