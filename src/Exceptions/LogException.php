<?php
declare(strict_types=1);

namespace Fyre\Log\Exceptions;

use
    Fyre\Error\Exceptions\Exception;

/**
 * LogException
 */
class LogException extends Exception
{

    public static function forConfigExists(string $key)
    {
        return new static('Cache handler config already exists: '.$key);
    }

    public static function forInvalidClass(string $className = '')
    {
        return new static('Log handler class not found: '.$className);
    }

    public static function forInvalidConfig(string $key)
    {
        return new static('Cache handler invalid config: '.$key);
    }

}
