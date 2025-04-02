<?php
declare(strict_types=1);

namespace Tests;

use BadMethodCallException;
use Fyre\Config\Config;
use Fyre\Container\Container;
use Fyre\Log\Exceptions\LogException;
use Fyre\Log\Handlers\FileLogger;
use Fyre\Log\LogManager;
use PHPUnit\Framework\TestCase;

use function array_diff;
use function file_get_contents;
use function json_encode;
use function preg_quote;
use function rmdir;
use function strtoupper;
use function unlink;

use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_UNICODE;

final class FileTest extends TestCase
{
    protected array $levels = [
        'emergency',
        'alert',
        'critical',
        'error',
        'warning',
        'notice',
        'info',
        'debug',
    ];

    protected LogManager $log;

    public function testAppends(): void
    {
        $this->log->handle('debug', 'test1');
        $this->log->handle('debug', 'test2');

        $this->assertMatchesRegularExpression(
            '/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2} \[DEBUG\] test1/',
            file_get_contents('log/debug.log')
        );

        $this->assertMatchesRegularExpression(
            '/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2} \[DEBUG\] test2/',
            file_get_contents('log/debug.log')
        );

        $this->assertFileExists('log/all.log');
        $this->assertFileDoesNotExist('log/scoped.log');
    }

    public function testData(): void
    {
        foreach ($this->levels as $type) {
            $this->log->handle($type, '{0}', ['test']);

            $this->assertMatchesRegularExpression(
                '/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2} \['.strtoupper($type).'\] test/',
                file_get_contents('log/'.$type.'.log')
            );
        }

        $this->assertFileExists('log/all.log');
        $this->assertFileDoesNotExist('log/scoped.log');
    }

    public function testInterpolateGet(): void
    {
        foreach ($this->levels as $type) {
            $this->log->handle($type, '{get_vars}');

            $this->assertMatchesRegularExpression(
                '/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2} \['.strtoupper($type).'\] '.preg_quote(json_encode($_GET, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE), '/').'/',
                file_get_contents('log/'.$type.'.log')
            );
        }

        $this->assertFileExists('log/all.log');
        $this->assertFileDoesNotExist('log/scoped.log');
    }

    public function testInterpolatePost(): void
    {
        foreach ($this->levels as $type) {
            $this->log->handle($type, '{post_vars}');

            $this->assertMatchesRegularExpression(
                '/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2} \['.strtoupper($type).'\] '.preg_quote(json_encode($_POST, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE), '/').'/',
                file_get_contents('log/'.$type.'.log')
            );
        }

        $this->assertFileExists('log/all.log');
        $this->assertFileDoesNotExist('log/scoped.log');
    }

    public function testInterpolateServer(): void
    {
        foreach ($this->levels as $type) {
            $this->log->handle($type, '{server_vars}');

            $this->assertMatchesRegularExpression(
                '/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2} \['.strtoupper($type).'\] '.preg_quote(json_encode($_SERVER, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE), '/').'/',
                file_get_contents('log/'.$type.'.log')
            );
        }

        $this->assertFileExists('log/all.log');
        $this->assertFileDoesNotExist('log/scoped.log');
    }

    public function testInvalidHandler(): void
    {
        $this->expectException(LogException::class);

        $this->log->clear();
        $this->log->setConfig('invalid', [
            'className' => 'Invalid',
        ]);

        $this->log->handle('debug', 'test');
    }

    public function testInvalidLevel(): void
    {
        $this->expectException(BadMethodCallException::class);

        $this->log->handle('invalid', 'test');
    }

    public function testLog(): void
    {
        foreach ($this->levels as $type) {
            $this->log->handle($type, 'test');

            $this->assertMatchesRegularExpression(
                '/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2} \['.strtoupper($type).'\] test/',
                file_get_contents('log/'.$type.'.log')
            );
        }

        $this->assertFileExists('log/all.log');
        $this->assertFileDoesNotExist('log/scoped.log');
    }

    public function testScope(): void
    {
        $this->log->handle('error', 'test', scope: 'scoped');

        $this->assertMatchesRegularExpression(
            '/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2} \[ERROR\] test/',
            file_get_contents('log/scoped.log')
        );
    }

    public function testSkipped(): void
    {
        foreach ($this->levels as $type) {
            $this->log->clear();
            $this->log->setConfig('file', [
                'className' => FileLogger::class,
                'scopes' => array_diff($this->levels, [$type]),
                'path' => 'log',
            ]);
            $this->log->handle($type, 'test');

            $this->assertFileDoesNotExist('log/'.$type.'.log');
        }

        $this->assertFileDoesNotExist('log/all.log');
        $this->assertFileDoesNotExist('log/scoped.log');
    }

    protected function setup(): void
    {
        $container = new Container();
        $container->singleton(Config::class);
        $container->use(Config::class)->set('Log', [
            'default' => [
                'className' => FileLogger::class,
                'scopes' => $this->levels,
                'path' => 'log',
                'suffix' => '',
            ],
            'scoped' => [
                'className' => FileLogger::class,
                'scopes' => ['scoped'],
                'path' => 'log',
                'file' => 'scoped',
            ],
            'all' => [
                'className' => FileLogger::class,
                'path' => 'log',
                'file' => 'all',
            ],
        ]);
        $this->log = $container->use(LogManager::class);
    }

    protected function tearDown(): void
    {
        foreach ($this->levels as $type) {
            @unlink('log/'.$type.'.log');
        }

        @unlink('log/scoped.log');
        @unlink('log/all.log');

        @rmdir('log');
    }
}
