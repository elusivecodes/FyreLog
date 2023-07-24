# FyreLog

**FyreLog** is a free, open-source logging library for *PHP*.


## Table Of Contents
- [Installation](#installation)
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
use Fyre\Log\Log;
```


## Methods

**Clear**

Clear instances and configs.

```php
Log::clear();
```

**Get Config**

Get a [*Logger*](#loggers) config.

- `$key` is a string representing the [*Logger*](#loggers) key.

```php
$config = Log::getConfig($key);
```

Alternatively, if the `$key` argument is omitted an array containing all configurations will be returned.

```php
$config = Log::getConfig();
```

**Get Key**

Get the key for a [*Logger*](#loggers) instance.

- `$logger` is a [*Logger*](#loggers).

```php
$key = Log::getKey($logger);
```

**Has Config**

Check if a [*Logger*](#loggers) config exists.

- `$key` is a string representing the [*Logger*](#loggers) key, and will default to `Log::DEFAULT`.

```php
$hasConfig = Log::hasConfig($key);
```

**Is Loaded**

Check if a [*Logger*](#loggers) instance is loaded.

- `$key` is a string representing the [*Logger*](#loggers) key, and will default to `Log::DEFAULT`.

```php
$isLoaded = Log::isLoaded($key);
```

**Load**

Load a [*Logger*](#loggers).

- `$config` is an array containing configuration options.

```php
$logger = Log::load($config);
```

**Set Config**

Set the [*Logger*](#loggers) config.

- `$key` is a string representing the [*Logger*](#loggers) key.
- `$options` is an array containing configuration options.

```php
Log::setConfig($key, $options);
```

Alternatively, a single array can be provided containing key/value of configuration options.

```php
Log::setConfig($config);
```

**Unload**

Unload a [*Logger*](#loggers).

- `$key` is a string representing the [*Logger*](#loggers) key, and will default to `Log::DEFAULT`.

```php
$unloaded = Log::unload($key);
```

**Use**

Load a shared [*Logger*](#loggers) instance.

- `$key` is a string representing the [*Logger*](#loggers) key, and will default to `Log::DEFAULT`.

```php
$logger = Log::use($key);
```


## Loggers

You can load a specific logger by specifying the `className` option of the `$config` variable above.

Custom loggers can be created by extending `\Fyre\Log\Logger`, ensuring all below methods are implemented.

**Can Handle**

Determine if a log level can be handled.

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

- `$key` is a string representing the logger key.
- `$options` is an array containing configuration options.
    - `className` must be set to `\Fyre\Log\Handlers\FileLogger`.
    - `dateFormat` is a string representing the date format, and will default to "*Y-m-d H:i:s*".
    - `threshold` is a number representing the log threshold, and will default to *0*.
    - `path` is a string representing the directory path, and will default to "*/var/log*".
    - `extension` is a string representing the file extension, and will default to "*log*".
    - `maxSize` is a number representing the maximum file size before log rotation, and will default to *1048576*.

```php
Log::setConfig($key, $options);

$logger = Log::use($key);
```


## Logging

Generally, logging is done by calling the static methods of the *Log* class.

This will call the `canHandle` method of all defined logger configs, and if that returns *true* then the `handle` method will also be called.

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
- *{get_vars}* will be replaced with the `$_GET` data.
- *{server_vars}* will be replaced with the `$_SERVER` data.
- *{session_vars}* will be replaced with the `$_SESSION` data.
- *{backtrace}* will be replaced with the backtrace.

See the [*MessageFormatter::formatMessage*](https://www.php.net/manual/en/messageformatter.formatmessage.php) method for details about message formatting.