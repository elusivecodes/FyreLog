<?php
declare(strict_types=1);

namespace Fyre\Log\Handlers;

use Fyre\Log\Logger;

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
     * Handle a message log.
     *
     * @param string $type The log type.
     * @param string $message The log message.
     * @param array $data Additional data to interpolate.
     */
    public function handle(string $type, string $message, array $data = []): void
    {
        $message = $this->interpolate($message, $data);

        $this->content[] = $this->format($type, $message);
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
