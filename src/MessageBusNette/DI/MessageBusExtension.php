<?php
declare(strict_types = 1);

namespace Nepada\MessageBusNette\DI;

use Nepada\MessageBus\Commands\CommandBus;
use Nepada\MessageBus\Commands\CommandHandler;
use Nepada\MessageBus\Commands\CommandHandlerLocator;
use Nepada\MessageBus\Commands\MessengerCommandBus;
use Nepada\MessageBus\Events\EventDispatcher;
use Nepada\MessageBus\Events\EventSubscriber;
use Nepada\MessageBus\Events\EventSubscribersLocator;
use Nepada\MessageBus\Events\MessengerEventDispatcher;
use Nepada\MessageBus\Logging\LogMessageResolver;
use Nepada\MessageBus\Logging\MessageContextResolver;
use Nepada\MessageBus\Logging\PrivateClassPropertiesExtractor;
use Nepada\MessageBus\Middleware\LoggingMiddleware;
use Nepada\MessageBus\Middleware\PreventNestedHandlingMiddleware;
use Nepada\MessageBus\StaticAnalysis\ConfigurableHandlerValidator;
use Nepada\MessageBus\StaticAnalysis\HandlerType;
use Nepada\MessageBus\StaticAnalysis\MessageHandlerValidationConfiguration;
use Nepada\MessageBus\StaticAnalysis\MessageHandlerValidator;
use Nepada\MessageBus\StaticAnalysis\MessageTypeExtractor;
use Nepada\MessageBus\StaticAnalysis\StaticAnalysisFailedException;
use Nepada\MessageBusDoctrine\Events\DispatchRecordedEventsFromEntities;
use Nepada\MessageBusDoctrine\Middleware\ClearEntityManagerMiddleware;
use Nepada\MessageBusDoctrine\Middleware\PreventOuterTransactionMiddleware;
use Nepada\MessageBusDoctrine\Middleware\TransactionMiddleware;
use Nette;
use Nette\DI\CompilerExtension;
use Nette\DI\Definitions\ServiceDefinition;
use Nette\Schema\Expect;
use Nette\Utils\Strings;
use Psr\Container\ContainerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Messenger\MessageBus;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Middleware\DispatchAfterCurrentBusMiddleware;
use Symfony\Component\Messenger\Middleware\HandleMessageMiddleware;

/**
 * @property \stdClass $config
 */
class MessageBusExtension extends CompilerExtension
{

    private const PSR_CONTAINER_SERVICE = 'serviceLocator';
    private const COMMAND_HANDLER_LOCATOR_SERVICE = 'commands.handlerLocator';
    private const EVENT_SUBSCRIBER_LOCATOR_SERVICE = 'events.handlerLocator';

    public function getConfigSchema(): Nette\Schema\Schema
    {
        $serviceDefinition = Expect::structure([
            'class' => Expect::string()->required(),
            'arguments' => Expect::array(),
        ])
            ->castTo('array')
            ->assert(fn (array $definition): bool => class_exists($definition['class']), 'Class must exist');
        $serviceReference = Expect::string()
            ->assert(fn (string $service): bool => Strings::startsWith($service, '@'));
        $service = Expect::anyOf($serviceDefinition, $serviceReference)
            ->before(function ($type): mixed {
                if (is_string($type) && ! Strings::startsWith($type, '@')) {
                    return ['class' => $type];
                }
                return $type;
            });
        return Expect::structure([
            'logger' => $service,
            'allowNestedCommandHandling' => Expect::bool(false),
            'clearEntityManager' => Expect::structure([
                'onStart' => Expect::bool(true),
                'onSuccess' => Expect::bool(true),
                'onError' => Expect::bool(true),
            ]),
            'bleedingEdge' => Expect::bool(false),
        ]);
    }

    public function loadConfiguration(): void
    {
        $this->getContainerBuilder()->addDefinition($this->prefix(self::PSR_CONTAINER_SERVICE))
            ->setType(ContainerInterface::class)
            ->setFactory(PsrContainerAdapter::class)
            ->setAutowired(false);
        if ($this->isDoctrineSupportAvailable()) {
            $this->setupDoctrine();
        }
        $this->setupMiddlewares();
        $this->setupCommandBus();
        $this->setupEventBus();
    }

    protected function setupDoctrine(): void
    {
        $container = $this->getContainerBuilder();
        $container->addDefinition($this->prefix('doctrine.dispatchRecordedEventsFromEntities'))
            ->setType(DispatchRecordedEventsFromEntities::class);
        $container->addDefinition($this->prefix('middleware.preventOuterTransaction'))
            ->setType(PreventOuterTransactionMiddleware::class);
        $container->addDefinition($this->prefix('middleware.transaction'))
            ->setType(TransactionMiddleware::class);
        $container->addDefinition($this->prefix('middleware.clearEntityManager'))
            ->setType(ClearEntityManagerMiddleware::class)
            ->setArguments([
                'clearOnStart' => $this->config->clearEntityManager->onStart,
                'clearOnSuccess' => $this->config->clearEntityManager->onSuccess,
                'clearOnError' => $this->config->clearEntityManager->onError,
            ]);
    }

    protected function setupMiddlewares(): void
    {
        $container = $this->getContainerBuilder();

        $container->addDefinition($this->prefix('middleware.dispatchAfterCurrentBus'))
            ->setType(DispatchAfterCurrentBusMiddleware::class);

        $container->addDefinition($this->prefix('middleware.preventNestedHandling'))
            ->setType(PreventNestedHandlingMiddleware::class);

        $container->addDefinition($this->prefix('logging.privateClassPropertiesExtractor'))
            ->setType(PrivateClassPropertiesExtractor::class);
        $container->addDefinition($this->prefix('logging.logMessageResolver'))
            ->setType(LogMessageResolver::class);
        $container->addDefinition($this->prefix('logging.messageContextResolver'))
            ->setType(MessageContextResolver::class);
        $loggingMiddleware = $container->addDefinition($this->prefix('middleware.logging'))
            ->setType(LoggingMiddleware::class);
        if (isset($this->config->logger)) {
            $loggingMiddleware->setArgument('logger', $this->resolveLoggerService($this->config->logger));
        }
    }

    /**
     * @param string|array{class: string, arguments: array<mixed>|null} $definition
     */
    protected function resolveLoggerService(string|array $definition): string
    {
        if (is_string($definition)) {
            return $definition;
        }

        $serviceName = $this->prefix('logging.logger');
        $this->getContainerBuilder()->addDefinition($serviceName)
            ->setType($definition['class'])
            ->setArguments($definition['arguments'] ?? [])
            ->setAutowired(false);

        return "@{$serviceName}";
    }

    protected function setupCommandBus(): void
    {
        $container = $this->getContainerBuilder();

        $handlerLocator = $container->addDefinition($this->prefix(self::COMMAND_HANDLER_LOCATOR_SERVICE))
            ->setType(CommandHandlerLocator::class)
            ->setArguments(['container' => $this->prefix('@' . self::PSR_CONTAINER_SERVICE)]);

        $middlewares = [
            $this->prefix('@middleware.dispatchAfterCurrentBus'),
            $this->prefix('@middleware.logging'),
        ];
        if (! $this->config->allowNestedCommandHandling) {
            $middlewares[] = $this->prefix('@middleware.preventNestedHandling');
        }
        if ($this->isDoctrineSupportAvailable()) {
            $middlewares[] = $this->prefix('@middleware.preventOuterTransaction');
            $middlewares[] = $this->prefix('@middleware.clearEntityManager');
            $middlewares[] = $this->prefix('@middleware.transaction');
        }
        $middlewares[] = $container->addDefinition($this->prefix('commands.handleMessageMiddleware'))
            ->setFactory(HandleMessageMiddleware::class, [$handlerLocator, false]);

        $messageBus = $container->addDefinition($this->prefix('commands.messageBus'))
            ->setType(MessageBusInterface::class)
            ->setFactory(MessageBus::class, [$middlewares]);

        $container->addDefinition($this->prefix('commands.commandBus'))
            ->setType(CommandBus::class)
            ->setFactory(MessengerCommandBus::class, ['messageBus' => $messageBus]);
    }

    protected function setupEventBus(): void
    {
        $container = $this->getContainerBuilder();

        $handlerLocator = $container->addDefinition($this->prefix(self::EVENT_SUBSCRIBER_LOCATOR_SERVICE))
            ->setType(EventSubscribersLocator::class)
            ->setArguments(['container' => $this->prefix('@' . self::PSR_CONTAINER_SERVICE)]);

        $middlewares = [
            $this->prefix('@middleware.dispatchAfterCurrentBus'),
            $this->prefix('@middleware.logging'),
        ];
        $middlewares[] = $container->addDefinition($this->prefix('events.handleMessageMiddleware'))
            ->setFactory(HandleMessageMiddleware::class, [$handlerLocator, true]);

        $messageBus = $container->addDefinition($this->prefix('events.messageBus'))
            ->setType(MessageBusInterface::class)
            ->setFactory(MessageBus::class, [$middlewares]);

        $container->addDefinition($this->prefix('events.eventDispatcher'))
            ->setType(EventDispatcher::class)
            ->setFactory(MessengerEventDispatcher::class, ['messageBus' => $messageBus]);
    }

    /**
     * @throws StaticAnalysisFailedException
     */
    public function beforeCompile(): void
    {
        $container = $this->getContainerBuilder();

        $definition = $container->getDefinition($this->prefix(self::COMMAND_HANDLER_LOCATOR_SERVICE));
        assert($definition instanceof ServiceDefinition);
        $definition->setArgument('serviceNameByMessageType', $this->findAndValidateCommandHandlers());

        $definition = $container->getDefinition($this->prefix(self::EVENT_SUBSCRIBER_LOCATOR_SERVICE));
        assert($definition instanceof ServiceDefinition);
        $definition->setArgument('serviceNamesByMessageType', $this->findAndValidateEventSubscribers());
    }

    /**
     * @return string[]
     * @throws StaticAnalysisFailedException
     */
    protected function findAndValidateCommandHandlers(): array
    {
        $handlerValidator = new ConfigurableHandlerValidator(MessageHandlerValidationConfiguration::command($this->config->bleedingEdge));

        $serviceNameByMessageType = [];
        foreach ($this->findAndValidateMessageHandlers($handlerValidator, CommandHandler::class) as $messageType => $serviceNames) {
            if (count($serviceNames) !== 1) {
                throw new \LogicException(sprintf('Multiple command handler services (%s) for command %s.', implode(', ', $serviceNames), $messageType));
            }
            $serviceNameByMessageType[$messageType] = reset($serviceNames);
        }

        return $serviceNameByMessageType;
    }

    /**
     * @return string[][]
     * @throws StaticAnalysisFailedException
     */
    protected function findAndValidateEventSubscribers(): array
    {
        $subscriberValidator = new ConfigurableHandlerValidator(MessageHandlerValidationConfiguration::event($this->config->bleedingEdge));
        return $this->findAndValidateMessageHandlers($subscriberValidator, EventSubscriber::class);
    }

    /**
     * @param class-string<MessageHandlerInterface> $handlerType
     * @return string[][]
     * @throws StaticAnalysisFailedException
     */
    protected function findAndValidateMessageHandlers(MessageHandlerValidator $messageHandlerValidator, string $handlerType): array
    {
        $containerBuilder = $this->getContainerBuilder();
        $messageTypeExtractor = new MessageTypeExtractor();

        $serviceNamesByMessageType = [];
        foreach ($containerBuilder->findByType($handlerType) as $serviceName => $serviceDefinition) {
            /** @var class-string<MessageHandlerInterface>|null $handlerTypeString allow-narrowing */
            $handlerTypeString = $serviceDefinition->getType();
            if ($handlerTypeString === null) {
                throw new \LogicException('Type of handler service type must be defined in this context.');
            }
            $handlerType = HandlerType::fromString($handlerTypeString);
            $messageHandlerValidator->validate($handlerType);

            $messageType = $messageTypeExtractor->extract($handlerType);
            $serviceNamesByMessageType[$messageType->toString()][] = (string) $serviceName;
        }

        return $serviceNamesByMessageType;
    }

    private function isDoctrineSupportAvailable(): bool
    {
        return class_exists(TransactionMiddleware::class);
    }

}
