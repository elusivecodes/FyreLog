<?php
declare(strict_types=1);

namespace Fyre\Log;

use function
    array_replace_recursive;

/**
 * Logger
 */
abstract class Logger
{

    protected static array $defaults = [
        'dateFormat' => 'Y-m-d H:i:s',
        'threshold' => 0
    ];

    protected array $config;

    /**
     * New Logger constructor.
     * @param array $config Options for the handler.
     */
    public function __construct(array $config = [])
    {
        $this->config = array_replace_recursive(self::$defaults, static::$defaults, $config);
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
     * Handle a message log.
     * @param string $type The log type.
     * @param string $message The log message.
     */
    abstract public function handle(string $type, string $message): void;

}
