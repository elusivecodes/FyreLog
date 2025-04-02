<?php
declare(strict_types=1);

namespace Fyre\Log;

use function array_replace;
use function in_array;

/**
 * Logger
 */
abstract class Logger
{
    protected static array $defaults = [
        'dateFormat' => 'Y-m-d H:i:s',
        'scopes' => null,
    ];

    protected array $config;

    /**
     * New Logger constructor.
     *
     * @param array $options Options for the handler.
     */
    public function __construct(array $options = [])
    {
        $this->config = array_replace(self::$defaults, static::$defaults, $options);
    }

    /**
     * Determine whether a log level can be handled.
     *
     * @param string $scope The log scope.
     * @return bool Whether the logger can handle the level.
     */
    public function canHandle(string $scope): bool
    {
        return $this->config['scopes'] === null || in_array($scope, $this->config['scopes']);
    }

    /**
     * Get the config.
     *
     * @return array The config.
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * Handle a message log.
     *
     * @param string $type The log type.
     * @param string $message The log message.
     */
    abstract public function handle(string $type, string $message): void;
}
