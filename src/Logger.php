<?php
declare(strict_types=1);

namespace Fyre\Log;

use function array_intersect;
use function array_key_exists;
use function array_keys;
use function array_replace;
use function array_unique;
use function date;
use function debug_backtrace;
use function in_array;
use function is_scalar;
use function json_encode;
use function preg_match_all;
use function str_replace;
use function strpos;
use function strtoupper;

use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_UNICODE;

/**
 * Logger
 */
abstract class Logger
{
    protected static array $defaults = [
        'dateFormat' => 'Y-m-d H:i:s',
        'levels' => null,
        'scopes' => [],
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

        if ($this->config['levels'] !== null) {
            $this->config['levels'] = (array) $this->config['levels'];
        }

        if ($this->config['scopes'] !== null) {
            $this->config['scopes'] = (array) $this->config['scopes'];
        }
    }

    /**
     * Determine whether a log level can be handled.
     *
     * @param string $level The log level.
     * @param array|string|null $scope The log scope(s).
     * @return bool Whether the logger can handle the level.
     */
    public function canHandle(string $level, array|string|null $scope = null): bool
    {
        $hasLevel = $this->config['levels'] === null || in_array($level, $this->config['levels'], true);
        $inScope = $this->config['scopes'] === null ||
            ($scope === null && $this->config['scopes'] === []) ||
            array_intersect((array) $scope, $this->config['scopes']) !== [];

        return $hasLevel && $inScope;
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
     * @param string $level The log level.
     * @param string $message The log message.
     */
    abstract public function handle(string $level, string $message): void;

    protected function format($level, string $message, bool $includeDate = true): string
    {
        return ($includeDate ? date($this->config['dateFormat']).' ' : '').
            '['.strtoupper($level).'] '.
            $message;
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
                $value = match ($key) {
                    'backtrace' => debug_backtrace(0),
                    'get_vars' => $_GET,
                    'post_vars' => $_POST,
                    'server_vars' => $_SERVER,
                    'session_vars' => $_SESSION,
                    default => null
                };

                if ($value !== null) {
                    $replacements[$replaceKey] = json_encode($value, $jsonFlags);
                }
            }
        }

        $replacementKeys = array_keys($replacements);

        return str_replace($replacementKeys, $replacements, $message);
    }
}
