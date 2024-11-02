<?php
declare(strict_types=1);

namespace Fyre\Log;

use BadMethodCallException;
use Fyre\Config\Config;
use Fyre\Container\Container;
use Fyre\Log\Exceptions\LogException;

use function array_key_exists;
use function array_keys;
use function array_unique;
use function class_exists;
use function debug_backtrace;
use function is_scalar;
use function is_subclass_of;
use function json_encode;
use function preg_match_all;
use function str_replace;
use function strpos;

use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_UNICODE;

/**
 * LogManager
 */
class LogManager
{
    public const DEFAULT = 'default';

    protected static array $levels = [
        'emergency' => 1,
        'alert' => 2,
        'critical' => 3,
        'error' => 4,
        'warning' => 5,
        'notice' => 6,
        'info' => 7,
        'debug' => 8,
    ];

    protected array $config = [];

    protected Container $container;

    protected array $instances = [];

    /**
     * New LogManager constructor.
     *
     * @param Container $container The Container.
     * @param Config $config The Config.
     */
    public function __construct(Container $container, Config $config)
    {
        $this->container = $container;

        $handlers = $config->get('Log', []);

        foreach ($handlers as $key => $options) {
            $this->setConfig($key, $options);
        }
    }

    /**
     * Build a handler.
     *
     * @param array $options Options for the handler.
     * @return Logger The handler.
     *
     * @throws LogException if the handler is not valid.
     */
    public function build(array $options = []): Logger
    {
        if (!array_key_exists('className', $options)) {
            throw LogException::forInvalidClass();
        }

        if (!class_exists($options['className'], true) || !is_subclass_of($options['className'], Logger::class)) {
            throw LogException::forInvalidClass($options['className']);
        }

        return $this->container->build($options['className'], ['options' => $options]);
    }

    /**
     * Clear all instances and configs.
     */
    public function clear(): void
    {
        $this->config = [];
        $this->instances = [];
    }

    /**
     * Get the handler config.
     *
     * @param string|null $key The config key.
     */
    public function getConfig(string|null $key = null): array|null
    {
        if ($key === null) {
            return $this->config;
        }

        return $this->config[$key] ?? null;
    }

    /**
     * Handle a message.
     *
     * @param string $type The log type.
     * @param string $message The log message.
     * @param array $data Additional data to interpolate.
     */
    public function handle(string $type, string $message, array $data = []): void
    {
        if (!array_key_exists($type, static::$levels)) {
            throw new BadMethodCallException();
        }

        $level = static::$levels[$type];

        $message = static::interpolate($message, $data);

        foreach ($this->config as $key => $config) {
            $instance = static::use($key);

            if (!$instance->canHandle($level)) {
                continue;
            }

            $instance->handle($type, $message);
        }
    }

    /**
     * Determine whether a config exists.
     *
     * @param string $key The config key.
     * @return bool TRUE if the config exists, otherwise FALSE.
     */
    public function hasConfig(string $key = self::DEFAULT): bool
    {
        return array_key_exists($key, $this->config);
    }

    /**
     * Determine whether a handler is loaded.
     *
     * @param string $key The config key.
     * @return bool TRUE if the handler is loaded, otherwise FALSE.
     */
    public function isLoaded(string $key = self::DEFAULT): bool
    {
        return array_key_exists($key, $this->instances);
    }

    /**
     * Set handler config.
     *
     * @param string $key The config key.
     * @param array $options The config options.
     * @return static The LogManager.
     *
     * @throws LogException if the config is not valid.
     */
    public function setConfig(string $key, array $options): static
    {
        if (array_key_exists($key, $this->config)) {
            throw LogException::forConfigExists($key);
        }

        $this->config[$key] = $options;

        return $this;
    }

    /**
     * Unload a handler.
     *
     * @param string $key The config key.
     * @return static The LogManager.
     */
    public function unload(string $key = self::DEFAULT): static
    {
        unset($this->instances[$key]);
        unset($this->config[$key]);

        return $this;
    }

    /**
     * Load a shared handler instance.
     *
     * @param string $key The config key.
     * @return Logger The handler.
     */
    public function use(string $key = self::DEFAULT): Logger
    {
        return $this->instances[$key] ??= static::build($this->config[$key] ?? []);
    }

    /**
     * Interpolate a message.
     *
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

        foreach ($keys as $key) {
            $replaceKey = '{'.$key.'}';

            if (array_key_exists($key, $data)) {
                if (is_scalar($data[$key])) {
                    $replacements[$replaceKey] = (string) $data[$key];
                } else {
                    $replacements[$replaceKey] = json_encode($data[$key], $jsonFlags);
                }
            } else {
                $data = match ($key) {
                    'backtrace' => debug_backtrace(0),
                    'get_vars' => $_GET,
                    'post_vars' => $_POST,
                    'server_vars' => $_SERVER,
                    'session_vars' => $_SESSION,
                    default => null
                };

                if ($data !== null) {
                    $replacements[$replaceKey] = json_encode($data, $jsonFlags);
                }
            }
        }

        $replacementKeys = array_keys($replacements);

        return str_replace($replacementKeys, $replacements, $message);
    }
}
