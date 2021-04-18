Message Bus integration into Nette Framework
============================================

[![Build Status](https://github.com/nepada/message-bus-nette/workflows/CI/badge.svg)](https://github.com/nepada/message-bus-nette/actions?query=workflow%3ACI+branch%3Amaster)
[![Coverage Status](https://coveralls.io/repos/github/nepada/message-bus-nette/badge.svg?branch=master)](https://coveralls.io/github/nepada/message-bus-nette?branch=master)
[![Downloads this Month](https://img.shields.io/packagist/dm/nepada/message-bus-nette.svg)](https://packagist.org/packages/nepada/message-bus-nette)
[![Latest stable](https://img.shields.io/packagist/v/nepada/message-bus-nette.svg)](https://packagist.org/packages/nepada/message-bus-nette)


Installation
------------

Via Composer:

```sh
$ composer require nepada/message-bus-nette
```

Register the extension in `config.neon`:
```yaml
extensions:
    messageBus: Nepada\MessageBusNette\DI\MessageBusExtension
```


Usage
-----

See [nepada/message-bus](https://github.com/nepada/message-bus) for the documentation of the core library.

### Logging

The extension tries to autowire PSR compatible logger into logging middleware. If this doesn't work for you, specify the logger service in configuration explicitly:
```yaml
messageBus:
    logger: @myLoggerService
```

### Nested command handling

Nested command handling is not allowed by default, this can be changed in configuration:
```yaml
messageBus:
    allowNestedCommandHandling: true
```

### Doctrine

[Doctrine ORM](https://github.com/doctrine/orm) specific features for message bus are provided by [nepada/message-bus-doctrine](https://github.com/nepada/message-bus-doctrine) package.
Follow the link for more detailed documentation.
Once you install the package, it gets detected by DI extension and all necessary services are set up.

With Doctrine integration:
- all commands are handled in a transaction and all changes are automatically flushed and commited after the handler successfully finishes, or rolled back on error,
- database transactions started outside of command bus are forbidden,
- entity manager is (optionally) cleared before and after the handling of every command.

Change or completely disable the default entity manager clearing logic:
```yaml
messageBus:
    clearEntityManager
        onStart: false
        onSuccess: false
        onError: false
``` 

You can record your domain events inside entities implementing `Nepada\Bridges\MessageBusDoctrine\Events\ContainsRecordedEvents` and they will be automatically collected and dispatched on flush.

### Tip: use SearchExtension to auto-register command handlers and event subscribers

```yaml
search:
    messageBusHandlers:
        in: %appDir%
        implements:
            - Nepada\MessageBus\Commands\CommandHandler
            - Nepada\MessageBus\Events\EventSubscriber
```
