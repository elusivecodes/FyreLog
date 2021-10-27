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
     * @param array $config Options for the handler.
     * @return Logger The handler.
     * @throws LogException if the handler is invalid.
     */
    public static function load(array $config = []): Logger
    {
        if (!array_key_exists('className', $config)) {
            throw LogException::forInvalidClass();
        }

        if (!class_exists($config['className'], true)) {
            throw LogException::forInvalidClass($config['className']);
        }

        return new $config['className']($config);
    }

    /**
     * Set handler config.
     * @param string $key The config key.
     * @param array $config The config options.
     */
    public static function setConfig(string $key, array $config): void
    {
        static::$config[$key] = $config;
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
    protected static function log(string $type = 'debug', string $message, array $data = []): void
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
