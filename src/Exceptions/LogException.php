<?php
declare(strict_types=1);

namespace Fyre\Log\Exceptions;

use
    RunTimeException;

/**
 * LogException
 */
class LogException extends RunTimeException
{

    public static function forInvalidClass(string $className = '')
    {
        return new static('Log handler class not found: '.$className);
    }

    public static function forInvalidPath(string $path)
    {
        return new static('Invalid file path: '.$path);
    }

}
