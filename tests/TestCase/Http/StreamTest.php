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

use Cake\Http\Stream;
use Cake\TestSuite\TestCase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use stdClass;

/**
 * Test case for the PSR-7 Stream implementation.
 */
class StreamTest extends TestCase
{
    protected Stream $stream;

    /**
     * @var list<string>
     */
    protected array $tempFiles = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->stream = new Stream('php://memory', 'wb+');
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        foreach ($this->tempFiles as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
        $this->tempFiles = [];
    }

    /**
     * Create a temporary file and register it for cleanup.
     *
     * @param string|null $contents Contents to write into the file.
     * @return string
     */
    protected function createTempFile(?string $contents = 'FOO BAR'): string
    {
        $file = tempnam(TMP, 'stream');
        $this->assertIsString($file);
        $this->tempFiles[] = $file;
        if ($contents !== null) {
            file_put_contents($file, $contents);
        }

        return $file;
    }

    /**
     * Get a temp file name that does not exist and register it for cleanup.
     *
     * @return string
     */
    protected function nonExistentTempFile(): string
    {
        do {
            $file = TMP . 'stream' . uniqid();
        } while (file_exists($file));
        $this->tempFiles[] = $file;

        return $file;
    }

    /**
     * Create a stream around a temp file opened in `wb+` mode.
     *
     * @return array{0: \Cake\Http\Stream, 1: resource}
     */
    protected function createFileStream(string $mode = 'wb+'): array
    {
        $resource = fopen($this->createTempFile(), $mode);
        $this->assertIsResource($resource);

        return [new Stream($resource), $resource];
    }

    public function testCanInstantiateWithStreamIdentifier(): void
    {
        $this->assertTrue($this->stream->isReadable());
        $this->assertTrue($this->stream->isWritable());
    }

    public function testCanInstantiateWithStreamResource(): void
    {
        $resource = fopen('php://memory', 'wb+');
        $stream = new Stream($resource);
        $this->assertSame($resource, $stream->detach());
    }

    public function testIsReadableReturnsFalseIfStreamIsNotReadable(): void
    {
        $stream = new Stream($this->createTempFile(null), 'w');
        $this->assertFalse($stream->isReadable());
    }

    public function testIsWritableReturnsFalseIfStreamIsNotWritable(): void
    {
        $stream = new Stream('php://memory', 'r');
        $this->assertFalse($stream->isWritable());
    }

    public function testToStringRetrievesFullContentsOfStream(): void
    {
        $message = 'foo bar';
        $this->stream->write($message);
        $this->assertSame($message, (string)$this->stream);
    }

    public function testStringSerializationReturnsEmptyStringWhenStreamIsNotReadable(): void
    {
        $stream = new Stream($this->createTempFile(), 'w');

        $this->assertSame('', $stream->__toString());
    }

    public function testStringSerializationReturnsEmptyStringWhenDetached(): void
    {
        $this->stream->write('foo');
        $this->stream->detach();

        $this->assertSame('', (string)$this->stream);
    }

    public function testCanReadContentFromNotSeekableResource(): void
    {
        $resource = fopen($this->createTempFile(), 'r');
        $stream = new class ($resource) extends Stream {
            public function isSeekable(): bool
            {
                return false;
            }
        };

        $this->assertSame('FOO BAR', $stream->__toString());
    }

    public function testPassingInvalidStreamResourceToConstructorRaisesException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Stream(['  THIS WILL NOT WORK  ']);
    }

    public function testCloseClosesResource(): void
    {
        [$stream, $resource] = $this->createFileStream();
        $stream->close();

        $this->assertFalse(is_resource($resource));
        $this->assertNull($stream->detach());
    }

    public function testCloseDoesNothingAfterDetach(): void
    {
        [$stream, $resource] = $this->createFileStream();
        $detached = $stream->detach();

        $stream->close();
        $this->assertTrue(is_resource($detached));
        $this->assertSame($resource, $detached);
    }

    public function testGetSizeReturnsStreamSize(): void
    {
        $resource = fopen(__FILE__, 'r');
        $expected = fstat($resource);
        $stream = new Stream($resource);

        $this->assertSame($expected['size'], $stream->getSize());
    }

    public function testSizeReportsNullWhenNoResourcePresent(): void
    {
        $this->stream->detach();
        $this->assertNull($this->stream->getSize());
    }

    public function testSizeReportsNullForPhpInputStreams(): void
    {
        $stream = new Stream(fopen('php://input', 'r'));
        $this->assertNull($stream->getSize());
    }

    public function testTellReportsCurrentPositionInResource(): void
    {
        [$stream, $resource] = $this->createFileStream();
        fwrite($resource, 'FOO BAR');
        fseek($resource, 2);

        $this->assertSame(2, $stream->tell());
    }

    public function testTellRaisesExceptionIfResourceIsDetached(): void
    {
        $this->stream->detach();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No resource available; stream is detached.');

        $this->stream->tell();
    }

    public function testEofReportsFalseWhenNotAtEndOfStream(): void
    {
        [$stream, $resource] = $this->createFileStream();
        fwrite($resource, 'FOO BAR');
        fseek($resource, 2);

        $this->assertFalse($stream->eof());
    }

    public function testEofReportsTrueWhenAtEndOfStream(): void
    {
        [$stream, $resource] = $this->createFileStream('r');
        while (!feof($resource)) {
            fread($resource, 4096);
        }

        $this->assertTrue($stream->eof());
    }

    public function testEofReportsTrueWhenStreamIsDetached(): void
    {
        $this->stream->detach();
        $this->assertTrue($this->stream->eof());
    }

    public function testIsSeekableReturnsTrueForReadableStreams(): void
    {
        [$stream] = $this->createFileStream();
        $this->assertTrue($stream->isSeekable());
    }

    public function testIsSeekableReturnsFalseForDetachedStreams(): void
    {
        $this->stream->detach();
        $this->assertFalse($this->stream->isSeekable());
    }

    public function testSeekAdvancesToGivenOffsetOfStream(): void
    {
        [$stream, $resource] = $this->createFileStream();
        fwrite($resource, 'FOO BAR');

        $stream->seek(2);
        $this->assertSame(2, $stream->tell());

        $stream->seek(2, SEEK_CUR);
        $this->assertSame(4, $stream->tell());

        $stream->seek(-1, SEEK_END);
        $this->assertSame(6, $stream->tell());
    }

    public function testRewindResetsToStartOfStream(): void
    {
        [$stream, $resource] = $this->createFileStream();
        fwrite($resource, 'FOO BAR');

        $stream->seek(2);
        $stream->rewind();
        $this->assertSame(0, $stream->tell());
    }

    public function testSeekRaisesExceptionWhenStreamIsDetached(): void
    {
        $this->stream->detach();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No resource available; stream is detached.');

        $this->stream->seek(2);
    }

    public function testSeekRaisesExceptionOnInvalidOffset(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Error seeking within stream.');

        $this->stream->seek(-1);
    }

    public function testIsWritableReturnsFalseWhenStreamIsDetached(): void
    {
        $this->stream->detach();
        $this->assertFalse($this->stream->isWritable());
    }

    public function testIsWritableReturnsTrueForWritableMemoryStream(): void
    {
        $stream = new Stream('php://temp', 'r+b');
        $this->assertTrue($stream->isWritable());
    }

    /**
     * @return array<array{string, bool, bool}>
     */
    public static function provideDataForIsWritable(): array
    {
        return [
            ['a', true, true],
            ['a+', true, true],
            ['a+b', true, true],
            ['ab', true, true],
            ['c', true, true],
            ['c+', true, true],
            ['c+b', true, true],
            ['cb', true, true],
            ['r', true, false],
            ['r+', true, true],
            ['r+b', true, true],
            ['rb', true, false],
            ['rw', true, true],
            ['w', true, true],
            ['w+', true, true],
            ['w+b', true, true],
            ['wb', true, true],
            ['x', false, true],
            ['x+', false, true],
            ['x+b', false, true],
            ['xb', false, true],
        ];
    }

    #[DataProvider('provideDataForIsWritable')]
    public function testIsWritableReturnsCorrectFlagForMode(string $mode, bool $fileShouldExist, bool $flag): void
    {
        // "x" modes require that the file does not exist yet
        $file = $fileShouldExist ? $this->createTempFile() : $this->nonExistentTempFile();
        $stream = new Stream(fopen($file, $mode));

        $this->assertSame($flag, $stream->isWritable());
    }

    /**
     * @return array<array{string, bool, bool}>
     */
    public static function provideDataForIsReadable(): array
    {
        return [
            ['a', true, false],
            ['a+', true, true],
            ['a+b', true, true],
            ['ab', true, false],
            ['c', true, false],
            ['c+', true, true],
            ['c+b', true, true],
            ['cb', true, false],
            ['r', true, true],
            ['r+', true, true],
            ['r+b', true, true],
            ['rb', true, true],
            ['rw', true, true],
            ['w', true, false],
            ['w+', true, true],
            ['w+b', true, true],
            ['wb', true, false],
            ['x', false, false],
            ['x+', false, true],
            ['x+b', false, true],
            ['xb', false, false],
        ];
    }

    #[DataProvider('provideDataForIsReadable')]
    public function testIsReadableReturnsCorrectFlagForMode(string $mode, bool $fileShouldExist, bool $flag): void
    {
        // "x" modes require that the file does not exist yet
        $file = $fileShouldExist ? $this->createTempFile() : $this->nonExistentTempFile();
        $stream = new Stream(fopen($file, $mode));

        $this->assertSame($flag, $stream->isReadable());
    }

    public function testWriteReturnsNumberOfBytesWritten(): void
    {
        $this->assertSame(7, $this->stream->write('FOO BAR'));
        $this->assertSame(7, $this->stream->getSize());
        $this->assertSame('FOO BAR', (string)$this->stream);
    }

    public function testWriteRaisesExceptionWhenStreamIsDetached(): void
    {
        $this->stream->detach();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No resource available; stream is detached.');

        $this->stream->write('bar');
    }

    public function testWriteRaisesExceptionWhenStreamIsNotWritable(): void
    {
        $stream = new Stream('php://memory', 'r');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Stream is not writable.');

        $stream->write('bar');
    }

    public function testIsReadableReturnsFalseWhenStreamIsDetached(): void
    {
        $this->stream->detach();
        $this->assertFalse($this->stream->isReadable());
    }

    public function testReadReturnsRequestedBytes(): void
    {
        $stream = new Stream(fopen($this->createTempFile(), 'r'));

        $this->assertSame('FOO', $stream->read(3));
        $this->assertSame(' BAR', $stream->read(4096));
    }

    public function testReadWithZeroLengthReturnsEmptyString(): void
    {
        $stream = new Stream(fopen($this->createTempFile(), 'r'));

        $this->assertSame('', $stream->read(0));
        $this->assertSame(0, $stream->tell());
    }

    public function testReadRaisesExceptionWhenStreamIsDetached(): void
    {
        $stream = new Stream(fopen($this->createTempFile(), 'r'));
        $stream->detach();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No resource available; stream is detached.');

        $stream->read(4096);
    }

    public function testReadRaisesExceptionWhenStreamIsNotReadable(): void
    {
        $stream = new Stream(fopen($this->createTempFile(), 'w'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Stream is not readable.');

        $stream->read(4096);
    }

    public function testReadReturnsEmptyStringWhenAtEndOfFile(): void
    {
        [$stream, $resource] = $this->createFileStream('r');
        while (!feof($resource)) {
            fread($resource, 4096);
        }

        $this->assertSame('', $stream->read(4096));
    }

    public function testGetContentsRaisesExceptionIfStreamIsNotReadable(): void
    {
        $stream = new Stream(fopen($this->createTempFile(), 'w'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Stream is not readable.');

        $stream->getContents();
    }

    public function testGetContentsRaisesExceptionWhenStreamIsDetached(): void
    {
        $this->stream->detach();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No resource available; stream is detached.');

        $this->stream->getContents();
    }

    /**
     * @return array<string, array{mixed}>
     */
    public static function invalidResources(): array
    {
        return [
            'null' => [null],
            'false' => [false],
            'true' => [true],
            'int' => [1],
            'float' => [1.1],
            'array' => [[fopen('php://memory', 'r+')]],
            'object' => [new stdClass()],
            'closed resource' => [(function () {
                $resource = fopen('php://memory', 'r+');
                fclose($resource);

                return $resource;
            })()],
        ];
    }

    #[DataProvider('invalidResources')]
    public function testAttachWithNonStringNonResourceRaisesException(mixed $resource): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid stream provided; must be a string stream identifier or stream resource.');

        $this->stream->attach($resource);
    }

    public function testConstructWithNonStreamResourceRaisesException(): void
    {
        $context = stream_context_create();
        $this->assertSame('stream-context', get_resource_type($context));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid stream provided');

        new Stream($context);
    }

    public function testAttachWithNonStreamResourceRaisesException(): void
    {
        $stream = new Stream(__FILE__);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid stream provided');

        $stream->attach(stream_context_create());
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidStringResources(): array
    {
        return [
            'Empty String' => [''],
            'File path does not exist' => [TMP . 'not-a-valid-file-path-' . uniqid()],
            'Invalid stream' => ['php://mammary'],
        ];
    }

    #[DataProvider('invalidStringResources')]
    public function testAttachWithInvalidStringResourceRaisesException(string $stream): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Empty or non-existent stream identifier or file path provided');

        $this->stream->attach($stream);
    }

    public function testAttachWithResourceAttachesResource(): void
    {
        $resource = fopen($this->createTempFile(null), 'r+');
        $this->stream->attach($resource);

        $this->assertSame($resource, $this->stream->detach());
    }

    public function testAttachWithStringRepresentingResourceCreatesAndAttachesResource(): void
    {
        $file = $this->createTempFile(null);
        $this->stream->attach($file);

        $resource = fopen($file, 'r+');
        fwrite($resource, 'FooBar');
        fclose($resource);

        $this->stream->rewind();
        $this->assertSame('FooBar', (string)$this->stream);
    }

    public function testAttachWithStringUsesGivenMode(): void
    {
        $this->stream->attach($this->createTempFile(), 'r');

        $this->assertTrue($this->stream->isReadable());
        $this->assertFalse($this->stream->isWritable());
        $this->assertSame('r', $this->stream->getMetadata('mode'));
    }

    public function testGetContentsShouldGetFullStreamContents(): void
    {
        $resource = fopen($this->createTempFile(null), 'r+');
        $this->stream->attach($resource);
        fwrite($resource, 'FooBar');

        // rewind, because current pointer is at end of stream
        $this->stream->rewind();
        $this->assertSame('FooBar', $this->stream->getContents());
    }

    public function testGetContentsShouldReturnStreamContentsFromCurrentPointer(): void
    {
        $resource = fopen($this->createTempFile(null), 'r+');
        $this->stream->attach($resource);
        fwrite($resource, 'FooBar');

        $this->stream->seek(3);
        $this->assertSame('Bar', $this->stream->getContents());
    }

    public function testGetMetadataReturnsAllMetadataWhenNoKeyPresent(): void
    {
        $resource = fopen($this->createTempFile(null), 'r+');
        $this->stream->attach($resource);

        $this->assertSame(stream_get_meta_data($resource), $this->stream->getMetadata());
    }

    public function testGetMetadataReturnsEmptyArrayAfterDetach(): void
    {
        $this->assertNotEmpty($this->stream->getMetadata());
        $this->assertNotEmpty($this->stream->getMetadata('mode'));

        $this->stream->detach();
        $this->assertSame([], $this->stream->getMetadata());
        $this->assertNull($this->stream->getMetadata('mode'));
    }

    public function testGetMetadataReturnsDataForSpecifiedKey(): void
    {
        $resource = fopen($this->createTempFile(null), 'r+');
        $this->stream->attach($resource);

        $metadata = stream_get_meta_data($resource);
        $this->assertSame($metadata['uri'], $this->stream->getMetadata('uri'));
    }

    public function testGetMetadataReturnsNullIfNoDataExistsForKey(): void
    {
        $this->assertNull($this->stream->getMetadata('TOTALLY_MADE_UP'));
    }
}
