<?php
declare(strict_types=1);

namespace Tests;

use BadMethodCallException;
use Fyre\Config\Config;
use Fyre\Container\Container;
use Fyre\Log\Exceptions\LogException;
use Fyre\Log\Handlers\ArrayLogger;
use Fyre\Log\LogManager;
use PHPUnit\Framework\TestCase;

use function array_diff;
use function json_encode;
use function strtoupper;

use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_UNICODE;

final class ArrayTest extends TestCase
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

        $content = $this->log->use('default')->read();

        $this->assertSame(
            '[DEBUG] test1',
            $content[0] ?? ''
        );

        $this->assertSame(
            '[DEBUG] test2',
            $content[1] ?? ''
        );

        $this->assertEmpty($this->log->use('scoped')->read());
        $this->assertNotEmpty($this->log->use('all')->read());
    }

    public function testData(): void
    {
        foreach ($this->levels as $i => $level) {
            $this->log->handle($level, '{0}', ['test']);

            $this->assertSame(
                '['.strtoupper($level).'] test',
                $this->log->use('default')->read()[$i] ?? ''
            );
        }

        $this->assertEmpty($this->log->use('scoped')->read());
        $this->assertNotEmpty($this->log->use('all')->read());
    }

    public function testInterpolateGet(): void
    {
        foreach ($this->levels as $i => $level) {
            $this->log->handle($level, '{get_vars}');

            $this->assertSame(
                '['.strtoupper($level).'] '.json_encode($_GET, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
                $this->log->use('default')->read()[$i] ?? ''
            );
        }

        $this->assertEmpty($this->log->use('scoped')->read());
        $this->assertNotEmpty($this->log->use('all')->read());
    }

    public function testInterpolatePost(): void
    {
        foreach ($this->levels as $i => $level) {
            $this->log->handle($level, '{post_vars}');

            $this->assertSame(
                '['.strtoupper($level).'] '.json_encode($_POST, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
                $this->log->use('default')->read()[$i] ?? ''
            );
        }

        $this->assertEmpty($this->log->use('scoped')->read());
        $this->assertNotEmpty($this->log->use('all')->read());
    }

    public function testInterpolateServer(): void
    {
        foreach ($this->levels as $i => $level) {
            $this->log->handle($level, '{server_vars}');

            $this->assertSame(
                '['.strtoupper($level).'] '.json_encode($_SERVER, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
                $this->log->use('default')->read()[$i] ?? ''
            );
        }

        $this->assertEmpty($this->log->use('scoped')->read());
        $this->assertNotEmpty($this->log->use('all')->read());
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
        foreach ($this->levels as $i => $level) {
            $this->log->handle($level, 'test');

            $this->assertSame(
                '['.strtoupper($level).'] test',
                $this->log->use('default')->read()[$i] ?? ''
            );
        }

        $this->assertEmpty($this->log->use('scoped')->read());
        $this->assertNotEmpty($this->log->use('all')->read());
    }

    public function testScope(): void
    {
        $this->log->handle('error', 'test', scope: 'scoped');

        $this->assertSame(
            '[ERROR] test',
            $this->log->use('scoped')->read()[0] ?? ''
        );
    }

    public function testSkipped(): void
    {
        foreach ($this->levels as $level) {
            $this->log->clear();
            $this->log->setConfig('array', [
                'className' => ArrayLogger::class,
                'levels' => array_diff($this->levels, [$level]),
            ]);
            $this->log->handle($level, 'test');

            $this->assertEmpty($this->log->use('array')->read());
        }
    }

    protected function setup(): void
    {
        $container = new Container();
        $container->singleton(Config::class);
        $container->use(Config::class)->set('Log', [
            'default' => [
                'className' => ArrayLogger::class,
                'levels' => $this->levels,
            ],
            'scoped' => [
                'className' => ArrayLogger::class,
                'scopes' => ['scoped', 'test'],
            ],
            'all' => [
                'className' => ArrayLogger::class,
            ],
        ]);
        $this->log = $container->use(LogManager::class);
    }
}
