<?php
declare(strict_types=1);

namespace Fyre\Log;

use ArrayObject;
use Fyre\Utility\Traits\MacroTrait;
use JsonSerializable;
use Psr\Log\AbstractLogger;
use Serializable;
use Stringable;

use function array_intersect;
use function array_key_exists;
use function array_keys;
use function array_replace;
use function array_unique;
use function date;
use function debug_backtrace;
use function get_debug_type;
use function in_array;
use function is_array;
use function is_object;
use function is_scalar;
use function json_encode;
use function method_exists;
use function preg_match_all;
use function serialize;
use function str_replace;
use function strpos;
use function strtoupper;

use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_UNICODE;

/**
 * Logger
 */
abstract class Logger extends AbstractLogger
{
    use MacroTrait;

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
     * Format a log message.
     *
     * @param string $level The log level.
     * @param string $message The log message.
     * @param bool $includeDate Whether to include the date.
     * @return string The formatted log message.
     */
    protected function format(string $level, string $message, bool $includeDate = true): string
    {
        return ($includeDate ? date($this->config['dateFormat']).' ' : '').
            '['.strtoupper($level).'] '.
            $message;
    }

    /**
     * Interpolate a message.
     *
     * @param string|Stringable $message The log message.
     * @param array $data Additional data to interpolate.
     * @return string The interpolated message.
     */
    protected static function interpolate(string|Stringable $message, array $data = []): string
    {
        $message = (string) $message;

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
                $value = $data[$key];

                if (is_scalar($value)) {
                    $replacements[$replaceKey] = (string) $value;
                } else if (is_array($value) || $value instanceof JsonSerializable) {
                    $replacements[$replaceKey] = json_encode($value, $jsonFlags);
                } else if ($value instanceof ArrayObject) {
                    $replacements[$replaceKey] = json_encode($value->getArrayCopy(), $jsonFlags);
                } else if ($value instanceof Serializable) {
                    $replacements[$replaceKey] = serialize($value);
                } else if ($value instanceof Stringable) {
                    $replacements[$replaceKey] = (string) $value;
                } else if (is_object($value) && method_exists($value, 'toArray')) {
                    $replacements[$replaceKey] = json_encode($value->toArray(), $jsonFlags);
                } else if (is_object($value) && method_exists($value, '__debugInfo')) {
                    $replacements[$replaceKey] = json_encode($value->__debugInfo(), $jsonFlags);
                } else {
                    $replacements[$replaceKey] = '[unhandled type '.get_debug_type($value).']';
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
