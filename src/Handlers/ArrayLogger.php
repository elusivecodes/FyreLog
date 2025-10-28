<?php
declare(strict_types=1);

namespace Fyre\Log\Handlers;

use Fyre\Log\Logger;
use Stringable;

/**
 * ArrayLogger
 */
class ArrayLogger extends Logger
{
    protected array $content = [];

    /**
     * Clear the log content.
     */
    public function clear(): void
    {
        $this->content = [];
    }

    /**
     * Log a message.
     *
     * @param mixed $level The log level.
     * @param string|Stringable $message The log message.
     * @param array $data Additional data to interpolate.
     */
    public function log(mixed $level, string|Stringable $message, array $data = []): void
    {
        $message = $this->interpolate($message, $data);

        $this->content[] = $this->format((string) $level, $message, false);
    }

    /**
     * Read the log content.
     *
     * @return array The log content.
     */
    public function read(): array
    {
        return $this->content;
    }
}
