# FyreLog

**FyreLog** is a free, logging library for *PHP*.


## Table Of Contents
- [Installation](#installation)
- [Methods](#methods)
- [Loggers](#loggers)



## Installation

**Using Composer**

```
composer install fyre/log
```

In PHP:

```php
use Fyre\Log\Log;
```


## Methods

**Clear**

Clear instances and configs.

```php
Log::clear();
```

**Set Config**

Set the logger config.

- `$key` is a string representing the logger key.
- `$config` is an array containing configuration data.

```php
Log::setConfig($key, $config);
```

**Clear**

Clear instances.

```php
Log::clear();
```

**Load**

Load a logger.

- `$config` is an array containing the configuration for the logger.

```php
$logger = Log::load($config);
```

**Set Config**

Set the logger config.

- `$key` is a string representing the logger key.
- `$config` is an array containing configuration data.

```php
Log::setConfig($key, $config);
```

**Use**

Load a shared logger instance.

- `$key` is a string representing the logger key, and will default to *"default"*.

```php
$logger = Log::use($key);
```


## Loggers

You can load a specific logger by specifying the `className` option of the `$config` variable above.

The default logger are:
- `\Fyre\Log\Handlers\FileLogger`

Custom loggers can be created by extending `\Fyre\Log\Logger`, ensuring all below methods are implemented.

**Can Handle**

Determine if a log level can be handled.

- `$level` is a number indicating the log level.

```php
$canHandle = $logger->canHandle($level);
```

By default, this method will return *TRUE* if the `$level` is below or equal to the `threshold` defined in the logger config, otherwise *FALSE*.

**Handle**

Handle a message log.

- `$type` is a string representing the type of log.
- `$message` is a string representing the log message.

```php
$logger->handle($type, $message);
```


## Logging

Generally, logging is done by calling the static methods of the *Log* class.

This will call the `canHandle` method of all defined logger configs, and if that returns *TRUE* then the `handle` method will also be called.

The default log levels are shown below (in order of severity).

```php
Log::emergency($message);   // 1
Log::alert($message);       // 2
Log::critical($message);    // 3
Log::error($message);       // 4
Log::warning($message);     // 5
Log::notice($message);      // 6
Log::info($message);        // 7
Log::debug($message);       // 8
```