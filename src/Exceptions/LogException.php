<?php
declare(strict_types=1);

namespace Fyre\Log\Exceptions;

use RuntimeException;

/**
 * LogException
 */
class LogException extends RuntimeException
{
    public static function forConfigExists(string $key): static
    {
        return new static('Cache handler config already exists: '.$key);
    }

    public static function forInvalidClass(string $className = ''): static
    {
        return new static('Log handler class not found: '.$className);
    }

    public static function forInvalidConfig(string $key): static
    {
        return new static('Cache handler invalid config: '.$key);
    }
}
