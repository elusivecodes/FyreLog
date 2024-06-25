<?php
declare(strict_types=1);

namespace Tests;

use BadMethodCallException;
use Fyre\Log\Exceptions\LogException;
use Fyre\Log\Handlers\FileLogger;
use Fyre\Log\Log;
use PHPUnit\Framework\TestCase;

use function date;
use function file_get_contents;
use function json_encode;
use function rmdir;
use function unlink;

use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_UNICODE;

final class FileTest extends TestCase
{
    protected array $levels = [
        'emergency' => 1,
        'alert' => 2,
        'critical' => 3,
        'error' => 4,
        'warning' => 5,
        'notice' => 6,
        'info' => 7,
        'debug' => 8,
    ];

    public function testAppends(): void
    {
        Log::debug('test1');
        Log::debug('test2');

        $this->assertMatchesRegularExpression(
            '/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2} - test1/',
            file_get_contents('log/debug.log')
        );

        $this->assertMatchesRegularExpression(
            '/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2} - test2/',
            file_get_contents('log/debug.log')
        );
    }

    public function testData(): void
    {
        foreach ($this->levels as $type => $threshold) {
            Log::$type('{0}', ['test']);

            $this->assertMatchesRegularExpression(
                '/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2} - test/',
                file_get_contents('log/'.$type.'.log')
            );
        }
    }

    public function testInterpolateGet(): void
    {
        foreach ($this->levels as $type => $threshold) {
            Log::$type('{get_vars}');

            $this->assertEquals(
                date('Y-m-d H:i:s').' - '.json_encode($_GET, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)."\r\n",
                file_get_contents('log/'.$type.'.log')
            );
        }
    }

    public function testInterpolatePost(): void
    {
        foreach ($this->levels as $type => $threshold) {
            Log::$type('{post_vars}');

            $this->assertEquals(
                date('Y-m-d H:i:s').' - '.json_encode($_POST, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)."\r\n",
                file_get_contents('log/'.$type.'.log')
            );
        }
    }

    public function testInterpolateServer(): void
    {
        foreach ($this->levels as $type => $threshold) {
            Log::$type('{server_vars}');

            $this->assertEquals(
                date('Y-m-d H:i:s').' - '.json_encode($_SERVER, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)."\r\n",
                file_get_contents('log/'.$type.'.log')
            );
        }
    }

    public function testInvalidHandler(): void
    {
        $this->expectException(LogException::class);

        Log::clear();
        Log::setConfig('invalid', [
            'className' => 'Invalid',
        ]);

        Log::debug('test');
    }

    public function testInvalidLevel(): void
    {
        $this->expectException(BadMethodCallException::class);

        Log::invalid('test');
    }

    public function testLog(): void
    {
        foreach ($this->levels as $type => $threshold) {
            Log::$type('test');

            $this->assertMatchesRegularExpression(
                '/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2} - test/',
                file_get_contents('log/'.$type.'.log')
            );
        }
    }

    public function testSkipped(): void
    {
        foreach ($this->levels as $type => $threshold) {
            Log::clear();
            Log::setConfig('file', [
                'className' => FileLogger::class,
                'threshold' => $threshold - 1,
                'path' => 'log',
            ]);
            Log::$type('test');

            $this->assertFileDoesNotExist('log/'.$type.'.log');
        }
    }

    protected function setup(): void
    {
        Log::clear();

        Log::setConfig([
            'default' => [
                'className' => FileLogger::class,
                'threshold' => 8,
                'path' => 'log',
            ],
        ]);
    }

    protected function tearDown(): void
    {
        foreach ($this->levels as $type => $level) {
            @unlink('log/'.$type.'.log');
        }

        @rmdir('log');
    }
}
