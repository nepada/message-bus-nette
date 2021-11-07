<?php
declare(strict_types = 1);

namespace NepadaTests\MessageBusNette;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Nepada\MessageBus\Commands\CommandBus;
use NepadaTests\MessageBusNette\DI\ConfiguratorFactory;
use NepadaTests\MessageBusNette\Fixtures\CreateInvoiceCommand;
use NepadaTests\MessageBusNette\Fixtures\CreateInvoiceHandler;
use NepadaTests\MessageBusNette\Fixtures\CreateInvoiceOnOrderPlaced;
use NepadaTests\MessageBusNette\Fixtures\CreateTwoInvoicesCommand;
use NepadaTests\MessageBusNette\Fixtures\CreateTwoInvoicesHandler;
use NepadaTests\MessageBusNette\Fixtures\FailedToCreateInvoiceException;
use NepadaTests\MessageBusNette\Fixtures\FailedToPlaceOrderException;
use NepadaTests\MessageBusNette\Fixtures\InvoiceCreatedEvent;
use NepadaTests\MessageBusNette\Fixtures\NotifyCustomerOnOrderPlaced;
use NepadaTests\MessageBusNette\Fixtures\OrderPlacedEvent;
use NepadaTests\MessageBusNette\Fixtures\PlaceOrderCommand;
use NepadaTests\MessageBusNette\Fixtures\PlaceOrderHandler;
use NepadaTests\MessageBusNette\Fixtures\TestLogger;
use NepadaTests\TestCase;
use Nette;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';


/**
 * @testCase
 */
class IntegrationTest extends TestCase
{

    public function testSuccessfulCommandHandlingWithEvents(): void
    {
        $container = $this->createContainer();
        $this->setupDatabase($container);
        $commandBus = $container->getByType(CommandBus::class);

        $commandBus->handle(new PlaceOrderCommand());

        $logger = $container->getByType(TestLogger::class);
        Assert::same(
            [
                [
                    'level' => 'info',
                    'message' => 'Command handling started.',
                    'context' => [
                        'messageType' => PlaceOrderCommand::class,
                        'shouldFail' => false,
                    ],
                ],
                [
                    'level' => 'info',
                    'message' => 'Command handling ended successfully.',
                    'context' => [
                        'messageType' => PlaceOrderCommand::class,
                        'handlerType' => PlaceOrderHandler::class,
                        'shouldFail' => false,
                    ],
                ],
                [
                    'level' => 'info',
                    'message' => 'Event handling started.',
                    'context' => [
                        'messageType' => OrderPlacedEvent::class,
                    ],
                ],
                [
                    'level' => 'info',
                    'message' => 'Command handling started.',
                    'context' => [
                        'messageType' => CreateInvoiceCommand::class,
                        'shouldFail' => false,
                    ],
                ],
                [
                    'level' => 'info',
                    'message' => 'Command handling ended successfully.',
                    'context' => [
                        'messageType' => CreateInvoiceCommand::class,
                        'handlerType' => CreateInvoiceHandler::class,
                        'shouldFail' => false,
                    ],
                ],
                [
                    'level' => 'info',
                    'message' => 'Event handling ended successfully.',
                    'context' => [
                        'messageType' => OrderPlacedEvent::class,
                        'handlerType' => CreateInvoiceOnOrderPlaced::class,
                        'handlerType_2' => NotifyCustomerOnOrderPlaced::class,
                    ],
                ],
                [
                    'level' => 'info',
                    'message' => 'Event handling started.',
                    'context' => [
                        'messageType' => InvoiceCreatedEvent::class,
                    ],
                ],
                [
                    'level' => 'info',
                    'message' => 'Event handling ended successfully.',
                    'context' => [
                        'messageType' => InvoiceCreatedEvent::class,
                    ],
                ],
            ],
            $this->normalizeLogRecords($logger->records),
        );
    }

    public function testFailedCommandDoesNotDispatchEvents(): void
    {
        $container = $this->createContainer();
        $commandBus = $container->getByType(CommandBus::class);

        Assert::exception(
            function () use ($commandBus): void {
                $commandBus->handle(new PlaceOrderCommand(true));
            },
            FailedToPlaceOrderException::class,
            'Failed to place order',
        );

        $logger = $container->getByType(TestLogger::class);
        Assert::same(
            [
                [
                    'level' => 'info',
                    'message' => 'Command handling started.',
                    'context' => [
                        'messageType' => PlaceOrderCommand::class,
                        'shouldFail' => true,
                    ],
                ],
                [
                    'level' => 'warning',
                    'message' => 'Command handling ended with error: Failed to place order',
                    'context' => [
                        'messageType' => PlaceOrderCommand::class,
                        'exceptionType' => FailedToPlaceOrderException::class,
                        'exceptionMessage' => 'Failed to place order',
                        'shouldFail' => true,
                    ],
                ],
            ],
            $this->normalizeLogRecords($logger->records),
        );
    }

    public function testSuccessfulNestedCommandHandling(): void
    {
        $container = $this->createContainer(['allowNestedCommandHandling' => true]);
        $this->setupDatabase($container);
        $commandBus = $container->getByType(CommandBus::class);

        $commandBus->handle(new CreateTwoInvoicesCommand());

        $logger = $container->getByType(TestLogger::class);
        Assert::same(
            [
                [
                    'level' => 'info',
                    'message' => 'Command handling started.',
                    'context' => [
                        'messageType' => CreateTwoInvoicesCommand::class,
                        'shouldSecondFail' => false,
                    ],
                ],
                [
                    'level' => 'info',
                    'message' => 'Command handling started.',
                    'context' => [
                        'messageType' => CreateInvoiceCommand::class,
                        'shouldFail' => false,
                    ],
                ],
                [
                    'level' => 'info',
                    'message' => 'Command handling ended successfully.',
                    'context' => [
                        'messageType' => CreateInvoiceCommand::class,
                        'handlerType' => CreateInvoiceHandler::class,
                        'shouldFail' => false,
                    ],
                ],
                [
                    'level' => 'info',
                    'message' => 'Command handling started.',
                    'context' => [
                        'messageType' => CreateInvoiceCommand::class,
                        'shouldFail' => false,
                    ],
                ],
                [
                    'level' => 'info',
                    'message' => 'Command handling ended successfully.',
                    'context' => [
                        'messageType' => CreateInvoiceCommand::class,
                        'handlerType' => CreateInvoiceHandler::class,
                        'shouldFail' => false,
                    ],
                ],
                [
                    'level' => 'info',
                    'message' => 'Command handling ended successfully.',
                    'context' => [
                        'messageType' => CreateTwoInvoicesCommand::class,
                        'handlerType' => CreateTwoInvoicesHandler::class,
                        'shouldSecondFail' => false,
                    ],
                ],
                [
                    'level' => 'info',
                    'message' => 'Event handling started.',
                    'context' => [
                        'messageType' => InvoiceCreatedEvent::class,
                    ],
                ],
                [
                    'level' => 'info',
                    'message' => 'Event handling ended successfully.',
                    'context' => [
                        'messageType' => InvoiceCreatedEvent::class,
                    ],
                ],
                [
                    'level' => 'info',
                    'message' => 'Event handling started.',
                    'context' => [
                        'messageType' => InvoiceCreatedEvent::class,
                    ],
                ],
                [
                    'level' => 'info',
                    'message' => 'Event handling ended successfully.',
                    'context' => [
                        'messageType' => InvoiceCreatedEvent::class,
                    ],
                ],
            ],
            $this->normalizeLogRecords($logger->records),
        );
    }

    public function testNestedCommandHandlingFails(): void
    {
        $container = $this->createContainer(['allowNestedCommandHandling' => true]);
        $this->setupDatabase($container);
        $commandBus = $container->getByType(CommandBus::class);

        Assert::exception(
            function () use ($commandBus): void {
                $commandBus->handle(new CreateTwoInvoicesCommand(true));
            },
            FailedToCreateInvoiceException::class,
            'Failed to create invoice',
        );

        $logger = $container->getByType(TestLogger::class);
        Assert::same(
            [
                [
                    'level' => 'info',
                    'message' => 'Command handling started.',
                    'context' => [
                        'messageType' => CreateTwoInvoicesCommand::class,
                        'shouldSecondFail' => true,
                    ],
                ],
                [
                    'level' => 'info',
                    'message' => 'Command handling started.',
                    'context' => [
                        'messageType' => CreateInvoiceCommand::class,
                        'shouldFail' => false,
                    ],
                ],
                [
                    'level' => 'info',
                    'message' => 'Command handling ended successfully.',
                    'context' => [
                        'messageType' => CreateInvoiceCommand::class,
                        'handlerType' => CreateInvoiceHandler::class,
                        'shouldFail' => false,
                    ],
                ],
                [
                    'level' => 'info',
                    'message' => 'Command handling started.',
                    'context' => [
                        'messageType' => CreateInvoiceCommand::class,
                        'shouldFail' => true,
                    ],
                ],
                [
                    'level' => 'warning',
                    'message' => 'Command handling ended with error: Failed to create invoice',
                    'context' => [
                        'messageType' => CreateInvoiceCommand::class,
                        'exceptionType' => FailedToCreateInvoiceException::class,
                        'exceptionMessage' => 'Failed to create invoice',
                        'shouldFail' => true,
                    ],
                ],
                [
                    'level' => 'warning',
                    'message' => 'Command handling ended with error: Failed to create invoice',
                    'context' => [
                        'messageType' => CreateTwoInvoicesCommand::class,
                        'exceptionType' => FailedToCreateInvoiceException::class,
                        'exceptionMessage' => 'Failed to create invoice',
                        'shouldSecondFail' => true,
                    ],
                ],
            ],
            $this->normalizeLogRecords($logger->records),
        );
    }

    /**
     * @param array<int, array{level: mixed, message: string|\Stringable, context: array<string, mixed>}> $records
     * @return array<int, array{level: mixed, message: string|\Stringable, context: array<string, mixed>}>
     */
    private function normalizeLogRecords(array $records): array
    {
        return array_map(
            function (array $record): array {
                $context = &$record['context'];
                if (! isset($context['handlerType_2']) || ! isset($context['handlerType'])) {
                    return $record;
                }
                $handlerTypes = [$context['handlerType']];
                $counter = 2;
                while (isset($context["handlerType_{$counter}"])) {
                    $handlerTypes[] = $context["handlerType_{$counter}"];
                    $counter++;
                }
                sort($handlerTypes);
                for ($i = 1; $i <= count($handlerTypes); $i++) {
                    $suffix = $i === 1 ? '' : "_{$i}";
                    $context["handlerType{$suffix}"] = $handlerTypes[$i - 1];
                }
                return $record;
            },
            $records,
        );
    }

    private function setupDatabase(Nette\DI\Container $container): void
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->getByType(EntityManagerInterface::class);
        $connection = $entityManager->getConnection();
        $metadata = $entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool = new SchemaTool($entityManager);
        foreach ($schemaTool->getCreateSchemaSql($metadata) as $query) {
            $connection->executeQuery($query);
        }
    }

    /**
     * @param mixed[] $parameters
     * @return Nette\DI\Container
     */
    private function createContainer(array $parameters = []): Nette\DI\Container
    {
        $configurator = (new ConfiguratorFactory())->create();
        $configurator->addParameters($parameters);
        return $configurator->createContainer();
    }

}


(new IntegrationTest())->run();
