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

        $content = file_get_contents('log/debug.log');

        $this->assertMatchesRegularExpression(
            '/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2} \[DEBUG\] test1/',
            $content
        );

        $this->assertMatchesRegularExpression(
            '/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2} \[DEBUG\] test2/',
            $content
        );

        $this->assertFileDoesNotExist('log/scoped.log');
        $this->assertFileExists('log/all.log');
    }

    public function testData(): void
    {
        foreach ($this->levels as $level) {
            $this->log->handle($level, '{0}', ['test']);

            $this->assertMatchesRegularExpression(
                '/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2} \['.strtoupper($level).'\] test/',
                file_get_contents('log/'.$level.'.log')
            );
        }

        $this->assertFileDoesNotExist('log/scoped.log');
        $this->assertFileExists('log/all.log');
    }

    public function testInterpolateGet(): void
    {
        foreach ($this->levels as $level) {
            $this->log->handle($level, '{get_vars}');

            $this->assertMatchesRegularExpression(
                '/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2} \['.strtoupper($level).'\] '.preg_quote(json_encode($_GET, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE), '/').'/',
                file_get_contents('log/'.$level.'.log')
            );
        }

        $this->assertFileDoesNotExist('log/scoped.log');
        $this->assertFileExists('log/all.log');
    }

    public function testInterpolatePost(): void
    {
        foreach ($this->levels as $level) {
            $this->log->handle($level, '{post_vars}');

            $this->assertMatchesRegularExpression(
                '/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2} \['.strtoupper($level).'\] '.preg_quote(json_encode($_POST, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE), '/').'/',
                file_get_contents('log/'.$level.'.log')
            );
        }

        $this->assertFileDoesNotExist('log/scoped.log');
        $this->assertFileExists('log/all.log');
    }

    public function testInterpolateServer(): void
    {
        foreach ($this->levels as $level) {
            $this->log->handle($level, '{server_vars}');

            $this->assertMatchesRegularExpression(
                '/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2} \['.strtoupper($level).'\] '.preg_quote(json_encode($_SERVER, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE), '/').'/',
                file_get_contents('log/'.$level.'.log')
            );
        }

        $this->assertFileDoesNotExist('log/scoped.log');
        $this->assertFileExists('log/all.log');
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
        foreach ($this->levels as $level) {
            $this->log->handle($level, 'test');

            $this->assertMatchesRegularExpression(
                '/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2} \['.strtoupper($level).'\] test/',
                file_get_contents('log/'.$level.'.log')
            );
        }

        $this->assertFileDoesNotExist('log/scoped.log');
        $this->assertFileExists('log/all.log');
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
        foreach ($this->levels as $level) {
            $this->log->clear();
            $this->log->setConfig('file', [
                'className' => FileLogger::class,
                'levels' => array_diff($this->levels, [$level]),
                'path' => 'log',
            ]);
            $this->log->handle($level, 'test');

            $this->assertFileDoesNotExist('log/'.$level.'.log');
        }

        $this->assertFileDoesNotExist('log/scoped.log');
        $this->assertFileDoesNotExist('log/all.log');
    }

    protected function setup(): void
    {
        $container = new Container();
        $container->singleton(Config::class);
        $container->use(Config::class)->set('Log', [
            'default' => [
                'className' => FileLogger::class,
                'levels' => $this->levels,
                'path' => 'log',
                'suffix' => '',
            ],
            'scoped' => [
                'className' => FileLogger::class,
                'scopes' => ['scoped', 'test'],
                'path' => 'log',
                'file' => 'scoped',
                'suffix' => '',
            ],
            'all' => [
                'className' => FileLogger::class,
                'path' => 'log',
                'file' => 'all',
                'suffix' => '',
            ],
        ]);
        $this->log = $container->use(LogManager::class);
    }

    protected function tearDown(): void
    {
        foreach ($this->levels as $level) {
            @unlink('log/'.$level.'.log');
        }

        @unlink('log/scoped.log');
        @unlink('log/all.log');

        @rmdir('log');
    }
}
