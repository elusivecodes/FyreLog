<?php
declare(strict_types=1);

namespace Fyre\Log;

use function array_replace;

/**
 * Logger
 */
abstract class Logger
{
    protected array $config;

    protected static array $defaults = [
        'dateFormat' => 'Y-m-d H:i:s',
        'threshold' => 0
    ];

    /**
     * New Logger constructor.
     * @param array $options Options for the handler.
     */
    public function __construct(array $options = [])
    {
        $this->config = array_replace(self::$defaults, static::$defaults, $options);
    }

    /**
     * Determine if a log level can be handled.
     * @param int $level The log level.
     * @return bool Whether the logger can handle the level.
     */
    public function canHandle(int $level): bool
    {
        return $level <= $this->config['threshold'];
    }

    /**
     * Get the config.
     * @return array The config.
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * Handle a message log.
     * @param string $type The log type.
     * @param string $message The log message.
     */
    abstract public function handle(string $type, string $message): void;
}
