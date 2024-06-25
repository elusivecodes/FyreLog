<?php
declare(strict_types=1);

namespace Tests;

use Fyre\Log\Exceptions\LogException;
use Fyre\Log\Handlers\FileLogger;
use Fyre\Log\Log;
use PHPUnit\Framework\TestCase;

final class LogTest extends TestCase
{
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
            Log::getConfig()
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
            Log::getConfig('error')
        );
    }

    public function testGetKey(): void
    {
        $handler = Log::use();

        $this->assertSame(
            'default',
            Log::getKey($handler)
        );
    }

    public function testGetKeyInvalid(): void
    {
        $handler = Log::load([
            'className' => FileLogger::class,
            'threshold' => 8,
            'path' => 'log',
        ]);

        $this->assertNull(
            Log::getKey($handler)
        );
    }

    public function testIsLoaded(): void
    {
        Log::use();

        $this->assertTrue(
            Log::isLoaded()
        );
    }

    public function testIsLoadedInvalid(): void
    {
        $this->assertFalse(
            Log::isLoaded('test')
        );
    }

    public function testIsLoadedKey(): void
    {
        Log::use('error');

        $this->assertTrue(
            Log::isLoaded('error')
        );
    }

    public function testLoad(): void
    {
        $this->assertInstanceOf(
            FileLogger::class,
            Log::load([
                'className' => FileLogger::class,
            ])
        );
    }

    public function testLoadInvalidHandler(): void
    {
        $this->expectException(LogException::class);

        Log::load([
            'className' => 'Invalid',
        ]);
    }

    public function testSetConfig(): void
    {
        Log::setConfig('test', [
            'className' => FileLogger::class,
            'threshold' => 1,
            'path' => 'log',
        ]);

        $this->assertSame(
            [
                'className' => FileLogger::class,
                'threshold' => 1,
                'path' => 'log',
            ],
            Log::getConfig('test')
        );
    }

    public function testSetConfigExists(): void
    {
        $this->expectException(LogException::class);

        Log::setConfig('default', [
            'className' => FileLogger::class,
            'threshold' => 1,
            'path' => 'log',
        ]);
    }

    public function testUnload(): void
    {
        Log::use();

        $this->assertTrue(
            Log::unload()
        );

        $this->assertFalse(
            Log::isLoaded()
        );
        $this->assertFalse(
            Log::hasConfig()
        );
    }

    public function testUnloadInvalid(): void
    {
        $this->assertFalse(
            Log::unload('test')
        );
    }

    public function testUnloadKey(): void
    {
        Log::use('error');

        $this->assertTrue(
            Log::unload('error')
        );

        $this->assertFalse(
            Log::isLoaded('error')
        );
        $this->assertFalse(
            Log::hasConfig('error')
        );
    }

    public function testUse(): void
    {
        $handler1 = Log::use();
        $handler2 = Log::use();

        $this->assertSame($handler1, $handler2);

        $this->assertInstanceOf(
            FileLogger::class,
            $handler1
        );
    }

    protected function setUp(): void
    {
        Log::clear();

        Log::setConfig([
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
