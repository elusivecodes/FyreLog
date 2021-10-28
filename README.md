# FyreLog

**FyreLog** is a free, logging library for *PHP*.


## Table Of Contents
- [Installation](#installation)
- [Methods](#methods)
- [Loggers](#loggers)
- [Logging](#logging)



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

- `$message` is a string representing the log message.
- `$data` is an array containing data to insert into the message string.

```php
Log::emergency($message, $data);   // 1
Log::alert($message, $data);       // 2
Log::critical($message, $data);    // 3
Log::error($message, $data);       // 4
Log::warning($message, $data);     // 5
Log::notice($message, $data);      // 6
Log::info($message, $data);        // 7
Log::debug($message, $data);       // 8
```

There are default placeholders that can also be used in log messages:

- *{post_vars}* will be replaced with the `$_POST` data.
- *{get_vars}* will be replaced with the `$_GET_` data.
- *{server_vars}* will be replaced with the `$_SERVER_` data.
- *{session_vars}* will be replaced with the `$_SESSION` data.
- *{backtrace}* will be replaced with the backtrace.

See the [*MessageFormatter::formatMessage*](https://www.php.net/manual/en/messageformatter.formatmessage.php) method for details about message formatting.