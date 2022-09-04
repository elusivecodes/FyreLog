<?php
declare(strict_types=1);

namespace Fyre\Log;

use
    BadMethodCallException,
    Fyre\Log\Exceptions\LogException,
    MessageFormatter;

use function
    array_key_exists,
    call_user_func_array,
    class_exists,
    debug_backtrace,
    is_array,
    print_r,
    strpos;

/**
 * Log
 */
abstract class Log
{

    protected static array $levels =[ 
        'emergency' => 1,
        'alert' => 2,
        'critical' => 3,
        'error' => 4,
        'warning' => 5,
        'notice' => 6,
        'info' => 7,
        'debug' => 8
    ];

    protected static array $config = [];

    protected static array $instances = [];

    /**
     * Log a message.
     * @param string $type The log type.
     * @param array $arguments Arguments to pass to the log method.
     * @throws BadMethodCallException if the log type is invalid.
     */
    public static function __callStatic(string $type, array $arguments)
    {
        if (!array_key_exists($type, static::$levels)) {
            throw new BadMethodCallException();
        }

        static::log($type, ...$arguments);
    }

    /**
     * Clear all instances and configs.
     */
    public static function clear(): void
    {
        static::$config = [];
        static::$instances = [];
    }

    /**
     * Load a handler.
     * @param array $options Options for the handler.
     * @return Logger The handler.
     * @throws LogException if the handler is invalid.
     */
    public static function load(array $options = []): Logger
    {
        if (!array_key_exists('className', $options)) {
            throw LogException::forInvalidClass();
        }

        if (!class_exists($options['className'], true)) {
            throw LogException::forInvalidClass($options['className']);
        }

        return new $options['className']($options);
    }

    /**
     * Set handler config.
     * @param string|array $key The config key.
     * @param array|null $options The config options.
     * @throws LogException if the config is invalid.
     */
    public static function setConfig(string|array $key, array|null $options = null): void
    {
        if (is_array($key)) {
            foreach ($key AS $k => $value) {
                static::setConfig($k, $value);
            }

            return;
        }

        if (!is_array($options)) {
            throw LogException::forInvalidConfig($key);
        }

        if (array_key_exists($key, static::$config)) {
            throw LogException::forConfigExists($key);
        }

        static::$config[$key] = $options;
    }

    /**
     * Unload a handler.
     * @param string $key The config key.
     */
    public static function unload(string $key = 'default'): void
    {
        unset(static::$instances[$key]);
        unset(static::$config[$key]);
    }

    /**
     * Load a shared handler instance.
     * @param string $key The config key.
     * @return Logger The handler.
     */
    public static function use(string $key = 'default'): Logger
    {
        return static::$instances[$key] ??= static::load(static::$config[$key] ?? []);
    }

    /**
     * Interpolate a message.
     * @param string $message The log message.
     * @param array $data Additional data to interpolate.
     * @return string The interpolated message.
     */
    protected static function interpolate(string $message, array $data = []): string
    {
        if (strpos($message, '{') === false) {
            return $message;
        }

        $data['post_vars'] = '$_POST: '.print_r($_POST, true);
        $data['get_vars'] = '$_GET: '.print_r($_GET, true);
        $data['server_vars'] = '$_SERVER: '.print_r($_SERVER, true);
        $data['session_vars'] = '$_SESSION: '.print_r($_SESSION ?? [], true);

        $trace = debug_backtrace(0);
        $data['backtrace'] = 'Backtrace: '.print_r($trace, true);

        return MessageFormatter::formatMessage('en', $message, $data);
    }

    /**
     * Log a message.
     * @param string $type The log type.
     * @param string $message The log message.
     * @param array $data Additional data to interpolate.
     * @throws LogException if the handler is invalid.
     */
    protected static function log(string $type, string $message, array $data = []): void
    {
        $level = static::$levels[$type];

        $message = static::interpolate($message, $data);

        foreach (static::$config AS $key => $config) {
            $instance = static::use($key);

            if (!$instance->canHandle($level)) {
                continue;
            }

            call_user_func_array([$instance, 'handle'], [$type, $message]);
        }
    }

}
