<?php
declare(strict_types=1);

namespace Tests;

use Fyre\Log\Exceptions\LogException;
use Fyre\Log\Handlers\FileLogger;
use Fyre\Log\LogManager;
use PHPUnit\Framework\TestCase;

final class LogManagerTest extends TestCase
{
    protected LogManager $log;

    public function testBuild(): void
    {
        $this->assertInstanceOf(
            FileLogger::class,
            $this->log->build([
                'className' => FileLogger::class,
            ])
        );
    }

    public function testBuildInvalidHandler(): void
    {
        $this->expectException(LogException::class);

        $this->log->build([
            'className' => 'Invalid',
        ]);
    }

    public function testGetConfig(): void
    {
        $this->assertSame(
            [
                'default' => [
                    'className' => FileLogger::class,
                    'threshold' => 8,
                    'path' => 'log',
                ],
                'error' => [
                    'className' => FileLogger::class,
                    'threshold' => 5,
                    'path' => 'error',
                ],
            ],
            $this->log->getConfig()
        );
    }

    public function testGetConfigKey(): void
    {
        $this->assertSame(
            [
                'className' => FileLogger::class,
                'threshold' => 5,
                'path' => 'error',
            ],
            $this->log->getConfig('error')
        );
    }

    public function testIsLoaded(): void
    {
        $this->log->use();

        $this->assertTrue(
            $this->log->isLoaded()
        );
    }

    public function testIsLoadedInvalid(): void
    {
        $this->assertFalse(
            $this->log->isLoaded('test')
        );
    }

    public function testIsLoadedKey(): void
    {
        $this->log->use('error');

        $this->assertTrue(
            $this->log->isLoaded('error')
        );
    }

    public function testSetConfig(): void
    {
        $this->assertSame(
            $this->log,
            $this->log->setConfig('test', [
                'className' => FileLogger::class,
                'threshold' => 1,
                'path' => 'log',
            ])
        );

        $this->assertSame(
            [
                'className' => FileLogger::class,
                'threshold' => 1,
                'path' => 'log',
            ],
            $this->log->getConfig('test')
        );
    }

    public function testSetConfigExists(): void
    {
        $this->expectException(LogException::class);

        $this->log->setConfig('default', [
            'className' => FileLogger::class,
            'threshold' => 1,
            'path' => 'log',
        ]);
    }

    public function testUnload(): void
    {
        $this->log->use();

        $this->assertSame(
            $this->log,
            $this->log->unload()
        );

        $this->assertFalse(
            $this->log->isLoaded()
        );
        $this->assertFalse(
            $this->log->hasConfig()
        );
    }

    public function testUnloadInvalid(): void
    {
        $this->assertSame(
            $this->log,
            $this->log->unload('test')
        );
    }

    public function testUnloadKey(): void
    {
        $this->log->use('error');

        $this->assertSame(
            $this->log,
            $this->log->unload('error')
        );

        $this->assertFalse(
            $this->log->isLoaded('error')
        );
        $this->assertFalse(
            $this->log->hasConfig('error')
        );
    }

    public function testUse(): void
    {
        $handler1 = $this->log->use();
        $handler2 = $this->log->use();

        $this->assertSame($handler1, $handler2);

        $this->assertInstanceOf(
            FileLogger::class,
            $handler1
        );
    }

    protected function setUp(): void
    {
        $this->log = new LogManager([
            'default' => [
                'className' => FileLogger::class,
                'threshold' => 8,
                'path' => 'log',
            ],
            'error' => [
                'className' => FileLogger::class,
                'threshold' => 5,
                'path' => 'error',
            ],
        ]);
    }
}
