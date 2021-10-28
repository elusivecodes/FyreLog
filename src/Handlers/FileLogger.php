<?php
declare(strict_types=1);

namespace Fyre\Log\Handlers;

use
    Fyre\Handlers\LogException,
    Fyre\Log\Logger,
    MessageFormatter;

use const
    FILE_APPEND,
    LOCK_EX;

use function
    array_key_exists,
    date,
    file_exists,
    filesize,
    is_dir,
    mkdir,
    rename,
    time,
    write_file;

/**
 * FileLogger
 */
class FileLogger extends Logger
{

    protected static array $defaults = [
        'path' => '/var/log/',
        'maxSize' => 1048576
    ];

    /**
     * New Logger constructor.
     * @param array $config Options for the handler.
     * @throws LogException if the path is invalid.
     */
    public function __construct(array $config = [])
    {
        parent::__construct($config);

        $this->config['path'] = rtrim($this->config['path'], DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;

        if (!is_dir($this->config['path']) && !mkdir($this->config['path'], 0777, true)) {
            throw LogException::forInvalidPath($this->config['path']);
        }
    }

    /**
     * Handle a message log.
     * @param string $type The log type.
     * @param string $message The log message.
     */
    public function handle(string $type, string $message): void
    {
        $filePath = $this->config['path'].$type.'.log';

        if (file_exists($filePath) && filesize($filePath) > $this->config['maxSize']) {
            $oldPath = $this->config['path'].$type.'.'.time().'.log';
            rename($filePath, $oldPath);
        }

        file_put_contents(
            $filePath,
            MessageFormatter::formatMessage('en', '{0} - {2}'."\r\n", [
                date($this->config['dateFormat']),
                $type,
                $message
            ]),
            FILE_APPEND | LOCK_EX
        );
    }

}
