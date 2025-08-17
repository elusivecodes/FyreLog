<?php
declare(strict_types=1);

namespace Fyre\Log;

use BadMethodCallException;
use Fyre\Config\Config;
use Fyre\Container\Container;
use Fyre\Log\Exceptions\LogException;
use Fyre\Utility\Traits\MacroTrait;

use function array_key_exists;
use function class_exists;
use function in_array;
use function is_subclass_of;

/**
 * LogManager
 */
class LogManager
{
    use MacroTrait;

    public const DEFAULT = 'default';

    protected static array $levels = [
        'emergency',
        'alert',
        'critical',
        'error',
        'warning',
        'notice',
        'info',
        'debug',
    ];

    protected array $config = [];

    protected array $instances = [];

    /**
     * New LogManager constructor.
     *
     * @param Container $container The Container.
     * @param Config $config The Config.
     */
    public function __construct(
        protected Container $container,
        Config $config
    ) {
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
     * @param string $level The log level.
     * @param string $message The log message.
     * @param array $data Additional data to interpolate.
     * @param array|string|null $scope The log scope(s).
     */
    public function handle(string $level, string $message, array $data = [], array|string|null $scope = null): void
    {
        if (!in_array($level, static::$levels)) {
            throw new BadMethodCallException();
        }

        foreach ($this->config as $key => $config) {
            $instance = $this->use($key);

            if (!$instance->canHandle($level, $scope)) {
                continue;
            }

            $instance->handle($level, $message, $data);
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
        return $this->instances[$key] ??= $this->build($this->config[$key] ?? []);
    }
}
