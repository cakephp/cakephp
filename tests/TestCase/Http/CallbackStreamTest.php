<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         6.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 *
 * Parts of this file are derived from laminas-diactoros
 * @copyright Copyright (c) 2020 Laminas Project a Series of LF Projects, LLC. (https://getlaminas.org/)
 * @license   https://github.com/laminas/laminas-diactoros/blob/3.9.x/LICENSE.md New BSD License
 */
namespace Cake\Test\TestCase\Http;

use Cake\Http\CallbackStream;
use Cake\TestSuite\TestCase;
use Closure;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;

/**
 * Test case for the CallbackStream implementation.
 */
class CallbackStreamTest extends TestCase
{
    public function testToString(): void
    {
        $stream = new CallbackStream(fn(): string => 'foobarbaz');

        $this->assertSame('foobarbaz', $stream->__toString());
    }

    public function testClose(): void
    {
        $stream = new CallbackStream(function (): void {
        });

        $stream->close();

        $this->assertNull($stream->detach());
        $this->assertTrue($stream->eof());
    }

    public function testDetach(): void
    {
        $callback = function (): string {
            return 'foo';
        };
        $stream = new CallbackStream($callback);

        $result = $stream->detach();
        $this->assertInstanceOf(Closure::class, $result);
        $this->assertSame('foo', $result());

        $this->assertNull($stream->detach());
        $this->assertSame('', $stream->getContents());
    }

    public function testAttach(): void
    {
        $stream = new CallbackStream(fn(): string => 'foo');
        $stream->attach(fn(): string => 'bar');

        $this->assertSame('bar', $stream->getContents());
    }

    public function testEof(): void
    {
        $stream = new CallbackStream(function (): void {
        });
        $this->assertFalse($stream->eof());

        $stream->getContents();
        $this->assertTrue($stream->eof());
    }

    public function testGetSize(): void
    {
        $stream = new CallbackStream(function (): void {
        });

        $this->assertNull($stream->getSize());
    }

    public function testTell(): void
    {
        $stream = new CallbackStream(function (): void {
        });

        $this->expectException(RuntimeException::class);

        $stream->tell();
    }

    public function testIsSeekable(): void
    {
        $stream = new CallbackStream(function (): void {
        });

        $this->assertFalse($stream->isSeekable());
    }

    public function testIsWritable(): void
    {
        $stream = new CallbackStream(function (): void {
        });

        $this->assertFalse($stream->isWritable());
    }

    public function testIsReadable(): void
    {
        $stream = new CallbackStream(function (): void {
        });

        $this->assertFalse($stream->isReadable());
    }

    public function testSeek(): void
    {
        $stream = new CallbackStream(function (): void {
        });

        $this->expectException(RuntimeException::class);

        $stream->seek(0);
    }

    public function testRewind(): void
    {
        $stream = new CallbackStream(function (): void {
        });

        $this->expectException(RuntimeException::class);

        $stream->rewind();
    }

    public function testWrite(): void
    {
        $stream = new CallbackStream(function (): void {
        });

        $this->expectException(RuntimeException::class);

        $stream->write('foobarbaz');
    }

    public function testRead(): void
    {
        $stream = new CallbackStream(function (): void {
        });

        $this->expectException(RuntimeException::class);

        $stream->read(3);
    }

    public function testGetContents(): void
    {
        $calls = 0;
        $stream = new CallbackStream(function () use (&$calls): string {
            $calls++;

            return 'foobarbaz';
        });

        $this->assertSame('foobarbaz', $stream->getContents());
        $this->assertSame('', $stream->getContents(), 'Callback should only be invoked once');
        $this->assertSame(1, $calls);
    }

    public function testGetContentsWithNonStringResult(): void
    {
        $stream = new CallbackStream(fn(): array => ['foo']);

        $this->assertSame('', $stream->getContents());
    }

    public function testGetContentsWithEchoingCallback(): void
    {
        $stream = new CallbackStream(function (): void {
            echo 'echoed content';
        });

        $this->expectOutputString('echoed content');
        $this->assertSame('', $stream->getContents());
        $this->assertTrue($stream->eof());
    }

    public function testGetMetadata(): void
    {
        $stream = new CallbackStream(function (): void {
        });

        $this->assertSame('callback', $stream->getMetadata('stream_type'));
        $this->assertFalse($stream->getMetadata('seekable'));
        $this->assertFalse($stream->getMetadata('eof'));
        $this->assertSame([
            'eof' => false,
            'stream_type' => 'callback',
            'seekable' => false,
        ], $stream->getMetadata());
        $this->assertNull($stream->getMetadata('boo'));

        $stream->getContents();
        $this->assertTrue($stream->getMetadata('eof'));
    }

    public static function sampleStaticCallback(): string
    {
        return __METHOD__;
    }

    /**
     * @return array<string, array{callable, string}>
     */
    public static function phpCallbacksForStreams(): array
    {
        $class = self::class;
        $instance = new class {
            public function sampleCallback(): string
            {
                return 'instance';
            }
        };

        return [
            'instance-method' => [$instance->sampleCallback(...), 'instance'],
            'static-method' => [[$class, 'sampleStaticCallback'], $class . '::sampleStaticCallback'],
            'static-string' => [$class . '::sampleStaticCallback', $class . '::sampleStaticCallback'],
            'function-name' => ['phpversion', PHP_VERSION],
            'invokable' => [new class {
                public function __invoke(): string
                {
                    return 'invoked';
                }
            }, 'invoked'],
        ];
    }

    #[DataProvider('phpCallbacksForStreams')]
    public function testAllowsArbitraryPhpCallbacks(callable $callback, string $expected): void
    {
        $stream = new CallbackStream($callback);

        $this->assertSame($expected, $stream->getContents());
    }
}
