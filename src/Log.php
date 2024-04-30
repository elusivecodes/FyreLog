<?php
declare(strict_types=1);

namespace Fyre\Log;

use BadMethodCallException;
use Fyre\Log\Exceptions\LogException;

use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_UNICODE;

use function array_key_exists;
use function array_keys;
use function array_unique;
use function call_user_func_array;
use function class_exists;
use function debug_backtrace;
use function is_array;
use function is_scalar;
use function json_encode;
use function preg_match_all;
use function str_replace;
use function strpos;

/**
 * Log
 */
abstract class Log
{

    public const DEFAULT = 'default';

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
     * @throws BadMethodCallException if the log type is not valid.
     */
    public static function __callStatic(string $type, array $arguments): void
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
     * Get the handler config.
     * @param string|null $key The config key.
     * @return array|null
     */
    public static function getConfig(string|null $key = null): array|null
    {
        if ($key === null) {
            return static::$config;
        }

        return static::$config[$key] ?? null;
    }

    /**
     * Get the key for an logger instance.
     * @param Logger $logger The logger.
     * @return string|null The logger key.
     */
    public static function getKey(Logger $logger): string|null
    {
        return array_search($logger, static::$instances, true) ?: null;
    }

    /**
     * Determine if a config exists.
     * @param string $key The config key.
     * @return bool TRUE if the config exists, otherwise FALSE.
     */
    public static function hasConfig(string $key = self::DEFAULT): bool
    {
        return array_key_exists($key, static::$config);
    }

    /**
     * Determine if a handler is loaded.
     * @param string $key The config key.
     * @return bool TRUE if the handler is loaded, otherwise FALSE.
     */
    public static function isLoaded(string $key = self::DEFAULT): bool
    {
        return array_key_exists($key, static::$instances);
    }

    /**
     * Load a handler.
     * @param array $options Options for the handler.
     * @return Logger The handler.
     * @throws LogException if the handler is not valid.
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
     * @throws LogException if the config is not valid.
     */
    public static function setConfig(string|array $key, array|null $options = null): void
    {
        if (is_array($key)) {
            foreach ($key AS $k => $v) {
                static::setConfig($k, $v);
            }

            return;
        }

        if ($options === null) {
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
     * @return bool TRUE if the handler was removed, otherwise FALSE.
     */
    public static function unload(string $key = self::DEFAULT): bool
    {
        if (!array_key_exists($key, static::$config)) {
            return false;
        }

        unset(static::$instances[$key]);
        unset(static::$config[$key]);

        return true;
    }

    /**
     * Load a shared handler instance.
     * @param string $key The config key.
     * @return Logger The handler.
     */
    public static function use(string $key = self::DEFAULT): Logger
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

        preg_match_all('/(?<!\\\\){([\w-]+)}/i', $message, $matches);

        if ($matches === []) {
            return $message;
        }

        $keys = array_unique($matches[1]);
        $replacements = [];
        $jsonFlags = JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE;

        foreach ($keys AS $key) {
            $replaceKey = '{'.$key.'}';

            if (array_key_exists($key, $data)) {
                if (is_scalar($data[$key])) {
                    $replacements[$replaceKey] = (string) $data[$key];
                } else {
                    $replacements[$replaceKey] = json_encode($data[$key], $jsonFlags);
                }
            } else {
                switch ($key) {
                    case 'backtrace':
                        $trace = debug_backtrace(0);
                        $replacements[$replaceKey] = json_encode($trace, $jsonFlags);
                        break;
                    case 'get_vars':
                        $replacements[$replaceKey] = json_encode($_GET, $jsonFlags);
                        break;
                    case 'post_vars':
                        $replacements[$replaceKey] = json_encode($_POST, $jsonFlags);
                        break;
                    case 'server_vars':
                        $replacements[$replaceKey] = json_encode($_SERVER, $jsonFlags);
                        break;
                    case 'session_vars':
                        $replacements[$replaceKey] = json_encode($_SESSION ?? [], $jsonFlags);
                        break;
                }
            }
        }

        $replacementKeys = array_keys($replacements);

        return str_replace($replacementKeys, $replacements, $message);
    }

    /**
     * Log a message.
     * @param string $type The log type.
     * @param string $message The log message.
     * @param array $data Additional data to interpolate.
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
