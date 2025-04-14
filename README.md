# FyreLog

**FyreLog** is a free, open-source logging library for *PHP*.


## Table Of Contents
- [Installation](#installation)
- [Basic Usage](#basic-usage)
- [Methods](#methods)
- [Loggers](#loggers)
    - [Array](#array)
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
- `$config` is a [*Config*](https://github.com/elusivecodes/FyreConfig).

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

- `$level` is a string representing the log level .
- `$message` is a string representing the log message.
- `$data` is an array containing data to insert into the message string.
- `$scope` is a string or array representing the log scope, and will default to *null*.

```php
$logManager->handle($level, $message, $data, $scope);
```

The supported log levels include: "*emergency*", "*alert*", "*critical*", "*error*", "*warning*", "*notice*", "*info*" and "*debug*".

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

- `$level` is a string representing the log level.
- `$scope` is a string or array representing the log scope, and will default to *null*.

```php
$canHandle = $logger->canHandle($level, $scope);
```

This method will return *true* if the `$level` is contained in the `levels` of the *Logger* config (or `levels` is set to *null*), and the `$scope` is contained in `scopes` (or `$scope` is *null* and `scopes` is an empty array, or `scopes` is set to *null*).

**Handle**

Handle a message log.

- `$level` is a string representing the log level.
- `$message` is a string representing the log message.
- `$data` is an array containing data to insert into the message string.

```php
$logger->handle($level, $message, $data);
```


## Array

The Array logger can be loaded using custom configuration.

- `$options` is an array containing configuration options.
    - `className` must be set to `\Fyre\Log\Handlers\ArrayLogger`.
    - `levels` is an array containing the levels that should be handled, and will default to *null*.
    - `scopes` is an array containing the scopes that should be handled, and will default to *[]*.

```php
$container->use(Config::class)->set('Log.array', $options);
```

**Clear**

Clear the log content.

```php
$logger->clear();
```

**Read**

Read the log content.

```php
$content = $logger->read();
```


### File

The File logger can be loaded using custom configuration.

- `$options` is an array containing configuration options.
    - `className` must be set to `\Fyre\Log\Handlers\FileLogger`.
    - `dateFormat` is a string representing the date format, and will default to "*Y-m-d H:i:s*".
    - `levels` is an array containing the levels that should be handled, and will default to *null*.
    - `scopes` is an array containing the scopes that should be handled, and will default to *[]*.
    - `path` is a string representing the directory path, and will default to "*/var/log*".
    - `file` is a string representing the file name, and will default *null* (the type of log will be used instead).
    - `suffix` is a string representing the filename suffix, and will default to *null* (or "*-cli*" if running from the CLI).
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
- `$scope` is a string or array representing the log scope, and will default to *null*.

```php
$logManager->handle('emergency', $message, $data, $scope);
$logManager->handle('alert', $message, $data, $scope);
$logManager->handle('critical', $message, $data, $scope);
$logManager->handle('error', $message, $data, $scope);
$logManager->handle('warning', $message, $data, $scope);
$logManager->handle('notice', $message, $data, $scope);
$logManager->handle('info', $message, $data, $scope);
$logManager->handle('debug', $message, $data, $scope);
```

There are default placeholders that can also be used in log messages:

- *{post_vars}* will be replaced with the `$_POST` data.
- *{get_vars}* will be replaced with the `$_GET` data.
- *{server_vars}* will be replaced with the `$_SERVER` data.
- *{session_vars}* will be replaced with the `$_SESSION` data.
- *{backtrace}* will be replaced with the backtrace.

See the [*MessageFormatter::formatMessage*](https://www.php.net/manual/en/messageformatter.formatmessage.php) method for details about message formatting.