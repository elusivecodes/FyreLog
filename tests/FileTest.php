<?php
declare(strict_types=1);

namespace Tests;

use BadMethodCallException;
use Fyre\Log\Log;
use Fyre\Log\Exceptions\LogException;
use Fyre\Log\Handlers\FileLogger;
use PHPUnit\Framework\TestCase;

use function file_get_contents;
use function rmdir;
use function unlink;

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
        'debug' => 8
    ];

    public function testLog(): void
    {
        foreach ($this->levels AS $type => $threshold) {
            Log::$type('test');

            $this->assertMatchesRegularExpression(
                '/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2} - test/',
                file_get_contents('log/'.$type.'.log')
            );
        }
    }

    public function testData(): void
    {
        foreach ($this->levels AS $type => $threshold) {
            Log::$type('{0}', ['test']);

            $this->assertMatchesRegularExpression(
                '/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2} - test/',
                file_get_contents('log/'.$type.'.log')
            );
        }
    }

    public function testSkipped(): void
    {
        foreach ($this->levels AS $type => $threshold) {
            Log::clear();
            Log::setConfig('file', [
                'className' => FileLogger::class,
                'threshold' => $threshold - 1,
                'path' => 'log'
            ]);
            Log::$type('test');

            $this->assertFileDoesNotExist('log/'.$type.'.log');
        }
    }

    public function testInterpolatePost(): void
    {
        foreach ($this->levels AS $type => $threshold) {
            Log::$type('{post_vars}');

            $this->assertMatchesRegularExpression(
                '/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2} - \$_POST: Array/',
                file_get_contents('log/'.$type.'.log')
            );
        }
    }

    public function testInterpolateGet(): void
    {
        foreach ($this->levels AS $type => $threshold) {
            Log::$type('{get_vars}');

            $this->assertMatchesRegularExpression(
                '/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2} - \$_GET: Array/',
                file_get_contents('log/'.$type.'.log')
            );
        }
    }

    public function testInterpolateServer(): void
    {
        foreach ($this->levels AS $type => $threshold) {
            Log::$type('{server_vars}');

            $this->assertMatchesRegularExpression(
                '/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2} - \$_SERVER: Array/',
                file_get_contents('log/'.$type.'.log')
            );
        }
    }

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

    public function testInvalidLevel(): void
    {
        $this->expectException(BadMethodCallException::class);

        Log::invalid('test');
    }

    public function testInvalidHandler(): void
    {
        $this->expectException(LogException::class);

        Log::clear();
        Log::setConfig('invalid', [
            'className' => 'Invalid'
        ]);

        Log::debug('test');
    }

    protected function setup(): void
    {
        Log::clear();

        Log::setConfig([
            'default' => [
                'className' => FileLogger::class,
                'threshold' => 8,
                'path' => 'log'
            ]
        ]);
    }

    protected function tearDown(): void
    {
        foreach ($this->levels AS $type => $level) {
            @unlink('log/'.$type.'.log');
        }

        @rmdir('log');
    }

}
