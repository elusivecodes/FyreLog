<?php
declare(strict_types=1);

namespace Fyre\Log\Handlers;

use
    Fyre\FileSystem\File,
    Fyre\FileSystem\Folder,
    Fyre\Log\Logger,
    Fyre\Utility\Path,
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

    protected string $path;

    /**
     * New Logger constructor.
     * @param array $config Options for the handler.
     * @throws LogException if the path is invalid.
     */
    public function __construct(array $config = [])
    {
        parent::__construct($config);

        $folder = new Folder($this->config['path'], true);

        $this->path = $folder->path();
    }

    /**
     * Handle a message log.
     * @param string $type The log type.
     * @param string $message The log message.
     */
    public function handle(string $type, string $message): void
    {
        $file = $type.'.log';
        $filePath = Path::resolve($this->path, $type.'.log');

        $file = new File($filePath, true);
        $file
            ->open('a')
            ->lock();

        if ($file->size() > $this->config['maxSize']) {
            $oldPath = Path::resolve($this->path, $type.'.'.time().'.log');

            $file
                ->copy($oldPath)
                ->close()
                ->open('w')
                ->lock();
        }

        $message = MessageFormatter::formatMessage('en', '{0} - {2}'."\r\n", [
            date($this->config['dateFormat']),
            $type,
            $message
        ]);

        $file
            ->write($message)
            ->close();
    }

}
