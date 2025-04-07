<?php
declare(strict_types=1);

namespace Fyre\Log\Handlers;

use Fyre\FileSystem\File;
use Fyre\Log\Logger;
use Fyre\Utility\Path;

use function time;

use const PHP_EOL;
use const PHP_SAPI;

/**
 * FileLogger
 */
class FileLogger extends Logger
{
    protected static array $defaults = [
        'path' => '/var/log/',
        'file' => null,
        'suffix' => null,
        'extension' => 'log',
        'maxSize' => 1048576,
        'mask' => null,
    ];

    protected string $path;

    /**
     * New Logger constructor.
     *
     * @param array $options Options for the handler.
     */
    public function __construct(array $options = [])
    {
        parent::__construct($options);

        if (PHP_SAPI === 'cli') {
            $this->config['suffix'] ??= '-cli';
        }

        $this->path = Path::resolve($this->config['path']);
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
        $file = ($this->config['file'] ?? $type).
            ($this->config['suffix'] ?? '').
            ($this->config['extension'] ? '.'.$this->config['extension'] : '');
        $filePath = Path::join($this->path, $file);

        $file = new File($filePath, true);

        if ($this->config['mask']) {
            $file->chmod($this->config['mask']);
        }

        $file
            ->open('a')
            ->lock();

        if ($file->size() >= $this->config['maxSize']) {
            $oldPath = Path::join($this->path, $type.'.'.time().'.'.$this->config['extension']);

            $file
                ->copy($oldPath)
                ->close()
                ->open('w')
                ->lock();
        }

        $message = static::interpolate($message, $data);
        $message = $this->format($type, $message);

        $file
            ->write($message.PHP_EOL)
            ->close();
    }
}
