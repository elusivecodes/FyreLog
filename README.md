# FyreLog

**FyreLog** is a free, open-source logging library for *PHP*.


## Table Of Contents
- [Installation](#installation)
- [Basic Usage](#basic-usage)
- [Methods](#methods)
- [Loggers](#loggers)
    - [File](#file)
- [Logging](#logging)



## Installation

**Using Composer**

```
composer require fyre/log
```

In PHP:

```php
use Fyre\Log\LogManager;
```


## Basic Usage

- `$container` is a [*Container*](https://github.com/elusivecodes/FyreContainer).
- `$config` is a  [*Config*](https://github.com/elusivecodes/FyreConfig).

```php
$logManager = new LogManager($container);
```

Default configuration options will be resolved from the "*Log*" key in the [*Config*](https://github.com/elusivecodes/FyreConfig).

**Autoloading**

It is recommended to bind the *LogManager* to the [*Container*](https://github.com/elusivecodes/FyreContainer) as a singleton.

```php
$container->singleton(LogManager::class);
```

Any dependencies will be injected automatically when loading from the [*Container*](https://github.com/elusivecodes/FyreContainer).

```php
$logManager = $container->use(LogManager::class);
```


## Methods

**Build**

Build a [*Logger*](#loggers).

- `$config` is an array containing configuration options.

```php
$logger = $logManager->build($config);
```

[*Logger*](#loggers) dependencies will be resolved automatically from the [*Container*](https://github.com/elusivecodes/FyreContainer).

**Clear**

Clear instances and configs.

```php
$logManager->clear();
```

**Get Config**

Get a [*Logger*](#loggers) config.

- `$key` is a string representing the [*Logger*](#loggers) key.

```php
$config = $logManager->getConfig($key);
```

Alternatively, if the `$key` argument is omitted an array containing all configurations will be returned.

```php
$config = $logManager->getConfig();
```

**Handle**

- `$type` is a string representing the type of log.
- `$message` is a string representing the log message.
- `$data` is an array containing data to insert into the message string.

```php
$logManager->handle($type, $message, $data);
```

**Has Config**

Determine whether a [*Logger*](#loggers) config exists.

- `$key` is a string representing the [*Logger*](#loggers) key, and will default to `LogManager::DEFAULT`.

```php
$hasConfig = $logManager->hasConfig($key);
```

**Is Loaded**

Determine whether a [*Logger*](#loggers) instance is loaded.

- `$key` is a string representing the [*Logger*](#loggers) key, and will default to `LogManager::DEFAULT`.

```php
$isLoaded = $logManager->isLoaded($key);
```

**Set Config**

Set the [*Logger*](#loggers) config.

- `$key` is a string representing the [*Logger*](#loggers) key.
- `$options` is an array containing configuration options.

```php
$logManager->setConfig($key, $options);
```

**Unload**

Unload a [*Logger*](#loggers).

- `$key` is a string representing the [*Logger*](#loggers) key, and will default to `LogManager::DEFAULT`.

```php
$logManager->unload($key);
```

**Use**

Load a shared [*Logger*](#loggers) instance.

- `$key` is a string representing the [*Logger*](#loggers) key, and will default to `LogManager::DEFAULT`.

```php
$logger = $logManager->use($key);
```

[*Logger*](#loggers) dependencies will be resolved automatically from the [*Container*](https://github.com/elusivecodes/FyreContainer).


## Loggers

You can load a specific logger by specifying the `className` option of the `$config` variable above.

Custom loggers can be created by extending `\Fyre\Log\Logger`, ensuring all below methods are implemented.

**Can Handle**

Determine whether a log level can be handled.

- `$level` is a number indicating the log level.

```php
$canHandle = $logger->canHandle($level);
```

By default, this method will return *true* if the `$level` is below or equal to the `threshold` defined in the logger config, otherwise *false*.

**Handle**

Handle a message log.

- `$type` is a string representing the type of log.
- `$message` is a string representing the log message.

```php
$logger->handle($type, $message);
```


### File

The File logger can be loaded using custom configuration.

- `$options` is an array containing configuration options.
    - `className` must be set to `\Fyre\Log\Handlers\FileLogger`.
    - `dateFormat` is a string representing the date format, and will default to "*Y-m-d H:i:s*".
    - `threshold` is a number representing the log threshold, and will default to *0*.
    - `suffix` is a string representing the filename suffix, and will default to *null*.
    - `path` is a string representing the directory path, and will default to "*/var/log*".
    - `extension` is a string representing the file extension, and will default to "*log*".
    - `maxSize` is a number representing the maximum file size before log rotation, and will default to *1048576*.

```php
$container->use(Config::class)->set('Log.file', $options);
```


## Logging

Generally, logging is done by calling the `handle` method of a *LogManager* instance.

This will call the `canHandle` method of all defined logger configs, and if that returns *true* then the `handle` method will also be called.

The default log levels are shown below (in order of severity).

- `$message` is a string representing the log message.
- `$data` is an array containing data to insert into the message string.

```php
$logManager->handle('emergency', $message, $data);   // 1
$logManager->handle('alert', $message, $data);       // 2
$logManager->handle('critical', $message, $data);    // 3
$logManager->handle('error', $message, $data);       // 4
$logManager->handle('warning', $message, $data);     // 5
$logManager->handle('notice', $message, $data);      // 6
$logManager->handle('info', $message, $data);        // 7
$logManager->handle('debug', $message, $data);       // 8
```

There are default placeholders that can also be used in log messages:

- *{post_vars}* will be replaced with the `$_POST` data.
- *{get_vars}* will be replaced with the `$_GET` data.
- *{server_vars}* will be replaced with the `$_SERVER` data.
- *{session_vars}* will be replaced with the `$_SESSION` data.
- *{backtrace}* will be replaced with the backtrace.

See the [*MessageFormatter::formatMessage*](https://www.php.net/manual/en/messageformatter.formatmessage.php) method for details about message formatting.