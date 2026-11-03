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
 * Parts of this file are derived from laminas-diactoros
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @copyright     Copyright (c) 2020 Laminas Project a Series of LF Projects, LLC. (https://getlaminas.org/)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         6.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 * @license       https://github.com/laminas/laminas-diactoros/blob/3.9.x/LICENSE.md New BSD License
 */
namespace Cake\Test\TestCase\Http;

use Cake\Http\Stream;
use Cake\Http\UploadedFile;
use Cake\TestSuite\TestCase;
use InvalidArgumentException;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Http\Message\StreamInterface;
use RuntimeException;
use TestApp\Http\Client\Adapter\UploadTargetStream;

/**
 * UploadedFile test case
 */
class UploadedFileTest extends TestCase
{
    /**
     * @var string|null
     */
    protected ?string $orgFile = null;

    /**
     * @var string|null
     */
    protected ?string $tmpFile = null;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->orgFile = null;
        $this->tmpFile = null;
    }

    /**
     * @inheritDoc
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        foreach ([$this->tmpFile, $this->orgFile] as $file) {
            if ($file !== null && file_exists($file)) {
                unlink($file);
            }
        }
    }

    /**
     * Create a temporary file in TMP.
     *
     * @param string $prefix The file prefix.
     * @return string
     */
    protected function createTempFile(string $prefix = 'upl'): string
    {
        $file = tempnam(TMP, $prefix);
        $this->assertIsString($file);

        return $file;
    }

    /**
     * @return array<string, array{mixed}>
     */
    public static function invalidStreams(): array
    {
        return [
            'null' => [null],
            'true' => [true],
            'false' => [false],
            'int' => [1],
            'float' => [1.1],
            'array' => [['filename']],
            'object' => [(object)['filename']],
        ];
    }

    #[DataProvider('invalidStreams')]
    public function testRaisesExceptionOnInvalidStreamOrFile(mixed $streamOrFile): void
    {
        $this->expectException(InvalidArgumentException::class);

        new UploadedFile($streamOrFile, 0, UPLOAD_ERR_OK);
    }

    public function testValidSize(): void
    {
        $uploaded = new UploadedFile(fopen('php://temp', 'wb+'), 123, UPLOAD_ERR_OK);

        $this->assertSame(123, $uploaded->getSize());
    }

    public function testValidNullSize(): void
    {
        $uploaded = new UploadedFile(fopen('php://temp', 'wb+'), null, UPLOAD_ERR_OK);

        $this->assertNull($uploaded->getSize());
    }

    /**
     * @return array<string, array{int}>
     */
    public static function invalidErrorStatuses(): array
    {
        return [
            'negative' => [-1],
            'too-big' => [9],
        ];
    }

    #[DataProvider('invalidErrorStatuses')]
    public function testRaisesExceptionOnInvalidErrorStatus(int $status): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('status');

        new UploadedFile(fopen('php://temp', 'wb+'), 0, $status);
    }

    public function testValidClientFilename(): void
    {
        $file = new UploadedFile(fopen('php://temp', 'wb+'), 0, UPLOAD_ERR_OK, 'boo.txt');
        $this->assertSame('boo.txt', $file->getClientFilename());
    }

    public function testValidNullClientFilename(): void
    {
        $file = new UploadedFile(fopen('php://temp', 'wb+'), 0, UPLOAD_ERR_OK);
        $this->assertNull($file->getClientFilename());
    }

    public function testValidClientMediaType(): void
    {
        $file = new UploadedFile(fopen('php://temp', 'wb+'), 0, UPLOAD_ERR_OK, 'foobar.baz', 'mediatype');
        $this->assertSame('mediatype', $file->getClientMediaType());
    }

    public function testValidNullClientMediaType(): void
    {
        $file = new UploadedFile(fopen('php://temp', 'wb+'), 0, UPLOAD_ERR_OK, 'foobar.baz');
        $this->assertNull($file->getClientMediaType());
    }

    public function testGetStreamReturnsOriginalStreamObject(): void
    {
        $stream = new Stream('php://temp');
        $upload = new UploadedFile($stream, 0, UPLOAD_ERR_OK);
        $this->assertSame($stream, $upload->getStream());
    }

    public function testGetStreamReturnsWrappedPhpStream(): void
    {
        $stream = fopen('php://temp', 'wb+');
        $upload = new UploadedFile($stream, 0, UPLOAD_ERR_OK);
        $uploadStream = $upload->getStream()->detach();
        $this->assertSame($stream, $uploadStream);
    }

    public function testGetStreamReturnsStreamForFile(): void
    {
        $this->tmpFile = $this->createTempFile();
        file_put_contents($this->tmpFile, 'file contents');

        $upload = new UploadedFile($this->tmpFile, 0, UPLOAD_ERR_OK);
        $uploadStream = $upload->getStream();

        $this->assertSame($this->tmpFile, $uploadStream->getMetadata('uri'));
        $this->assertSame('file contents', (string)$uploadStream);
        $this->assertSame($uploadStream, $upload->getStream());
    }

    public function testMovesFileToDesignatedPath(): void
    {
        $originalContents = 'Foo bar!';
        $stream = new Stream('php://temp', 'wb+');
        $stream->write($originalContents);
        $upload = new UploadedFile($stream, 0, UPLOAD_ERR_OK);
        $this->tmpFile = $this->createTempFile();
        $to = $this->tmpFile;
        $upload->moveTo($to);
        $this->assertFileExists($to);
        $this->assertSame($originalContents, file_get_contents($to));
    }

    public function testMoveRaisesExceptionForEmptyPath(): void
    {
        $stream = new Stream('php://temp', 'wb+');
        $stream->write('Foo bar!');
        $upload = new UploadedFile($stream, 0, UPLOAD_ERR_OK);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('path');

        $upload->moveTo('');
    }

    public function testMoveRaisesExceptionForNonExistentTargetDirectory(): void
    {
        $stream = new Stream('php://temp', 'wb+');
        $stream->write('Foo bar!');
        $upload = new UploadedFile($stream, 0, UPLOAD_ERR_OK);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('does not exist or is not writable');

        $upload->moveTo(TMP . uniqid('missing-dir-') . DS . 'file.txt');
    }

    public function testMoveCannotBeCalledMoreThanOnce(): void
    {
        $stream = new Stream('php://temp', 'wb+');
        $stream->write('Foo bar!');
        $upload = new UploadedFile($stream, 0, UPLOAD_ERR_OK);
        $this->tmpFile = $this->createTempFile();
        $to = $this->tmpFile;
        $upload->moveTo($to);
        $this->assertFileExists($to);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('moved');

        $upload->moveTo($to);
    }

    public function testCannotRetrieveStreamAfterMove(): void
    {
        $stream = new Stream('php://temp', 'wb+');
        $stream->write('Foo bar!');
        $upload = new UploadedFile($stream, 0, UPLOAD_ERR_OK);
        $this->tmpFile = $this->createTempFile();
        $to = $this->tmpFile;
        $upload->moveTo($to);
        $this->assertFileExists($to);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('moved');

        $upload->getStream();
    }

    /**
     * @return array<string, array{int}>
     */
    public static function nonOkErrorStatus(): array
    {
        return [
            'UPLOAD_ERR_INI_SIZE' => [UPLOAD_ERR_INI_SIZE],
            'UPLOAD_ERR_FORM_SIZE' => [UPLOAD_ERR_FORM_SIZE],
            'UPLOAD_ERR_PARTIAL' => [UPLOAD_ERR_PARTIAL],
            'UPLOAD_ERR_NO_FILE' => [UPLOAD_ERR_NO_FILE],
            'UPLOAD_ERR_NO_TMP_DIR' => [UPLOAD_ERR_NO_TMP_DIR],
            'UPLOAD_ERR_CANT_WRITE' => [UPLOAD_ERR_CANT_WRITE],
            'UPLOAD_ERR_EXTENSION' => [UPLOAD_ERR_EXTENSION],
        ];
    }

    #[DataProvider('nonOkErrorStatus')]
    public function testConstructorDoesNotRaiseExceptionForInvalidStreamWhenErrorStatusPresent(int $status): void
    {
        $uploadedFile = new UploadedFile('not ok', 0, $status);
        $this->assertSame($status, $uploadedFile->getError());

        $uploadedFile = new UploadedFile(null, 0, $status);
        $this->assertSame($status, $uploadedFile->getError());
    }

    #[DataProvider('nonOkErrorStatus')]
    public function testMoveToRaisesExceptionWhenErrorStatusPresent(int $status): void
    {
        $uploadedFile = new UploadedFile('not ok', 0, $status);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('upload error');

        $uploadedFile->moveTo(TMP . uniqid());
    }

    #[DataProvider('nonOkErrorStatus')]
    public function testGetStreamRaisesExceptionWhenErrorStatusPresent(int $status): void
    {
        $uploadedFile = new UploadedFile('not ok', 0, $status);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('upload error');

        $uploadedFile->getStream();
    }

    public function testMoveToCreatesStreamIfOnlyAFilenameWasProvided(): void
    {
        $this->orgFile = $this->createTempFile('org');
        $this->tmpFile = $this->createTempFile('dst');
        file_put_contents($this->orgFile, 'Hello');

        $original = file_get_contents($this->orgFile);

        $uploadedFile = new UploadedFile($this->orgFile, 100, UPLOAD_ERR_OK, basename($this->orgFile), 'text/plain');
        $uploadedFile->moveTo($this->tmpFile);

        $this->assertSame($original, file_get_contents($this->tmpFile));
    }

    /**
     * @return iterable<int, array{int, string}>
     */
    public static function errorConstantsAndMessages(): iterable
    {
        foreach (UploadedFile::ERROR_MESSAGES as $constant => $message) {
            if ($constant === UPLOAD_ERR_OK) {
                continue;
            }
            yield $constant => [$constant, $message];
        }
    }

    #[DataProvider('errorConstantsAndMessages')]
    public function testGetStreamRaisesExceptionWithAppropriateMessageWhenUploadErrorDetected(
        int $constant,
        string $message,
    ): void {
        $uploadedFile = new UploadedFile(__FILE__, 100, $constant);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage($message);
        $uploadedFile->getStream();
    }

    #[DataProvider('errorConstantsAndMessages')]
    public function testMoveToRaisesExceptionWithAppropriateMessageWhenUploadErrorDetected(
        int $constant,
        string $message,
    ): void {
        $uploadedFile = new UploadedFile(__FILE__, 100, $constant);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage($message);
        $uploadedFile->moveTo(TMP . 'foo');
    }

    public function testMoveToInCLIShouldRemoveOriginalFile(): void
    {
        $this->orgFile = $this->createTempFile('org');
        file_put_contents($this->orgFile, 'Hello');
        $upload = new UploadedFile($this->orgFile, 0, UPLOAD_ERR_OK);
        $this->tmpFile = $this->createTempFile('dst');
        $to = $this->tmpFile;
        $upload->moveTo($to);
        $this->assertFileDoesNotExist($this->orgFile);
        $this->assertFileExists($to);
        $this->assertSame('Hello', file_get_contents($to));
    }

    public function testMovingToSamePathPreservesSource(): void
    {
        $this->orgFile = $this->createTempFile();
        file_put_contents($this->orgFile, 'original contents');
        $upload = new UploadedFile($this->orgFile, null, UPLOAD_ERR_OK);
        try {
            $upload->moveTo(dirname($this->orgFile) . DS . '.' . DS . basename($this->orgFile));
            $this->fail('Moving an upload over itself must fail.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('paths must differ', $exception->getMessage());
            $this->assertSame('original contents', file_get_contents($this->orgFile));
            $this->assertSame('original contents', (string)$upload->getStream());
        } finally {
            $upload->getStream()->close();
        }
    }

    public static function uploadTargetCapacities(): array
    {
        return ['partial writes' => [null], 'full destination' => [3]];
    }

    #[DataProvider('uploadTargetCapacities')]
    public function testCopyChecksDestinationWrites(?int $capacity): void
    {
        $this->orgFile = $this->createTempFile();
        file_put_contents($this->orgFile, 'original contents');
        $upload = new class ($this->orgFile, null, UPLOAD_ERR_OK) extends UploadedFile {
            public function copyTo(string $path): void
            {
                $handle = fopen($path, 'wb');
                try {
                    $this->copyStream($this->getStream(), $handle, $path);
                } finally {
                    fclose($handle);
                }
            }
        };
        stream_wrapper_register('uploadtarget', UploadTargetStream::class);
        UploadTargetStream::$capacity = $capacity;
        try {
            try {
                $upload->copyTo('uploadtarget://destination/file');
                $this->assertNull($capacity, 'A full destination must fail.');
                $this->assertSame('original contents', UploadTargetStream::$contents);
                $this->assertFileExists($this->orgFile);
            } catch (RuntimeException $exception) {
                $this->assertSame(3, $capacity);
                $this->assertStringContainsString('Unable to write', $exception->getMessage());
                $this->assertSame('original contents', file_get_contents($this->orgFile));
                $this->tmpFile = $this->createTempFile();
                $upload->moveTo($this->tmpFile);
                $this->assertSame('original contents', file_get_contents($this->tmpFile));
            }
        } finally {
            stream_wrapper_unregister('uploadtarget');
            if ($capacity === null) {
                $upload->getStream()->close();
            }
        }
    }

    public static function selfMoveSources(): array
    {
        $cases = [];
        foreach (['file', 'stream', 'resource'] as $source) {
            foreach (['same', 'relative', 'hardlink', 'symlink'] as $target) {
                $cases[$source . '-' . $target] = [$source, $target];
            }
        }

        return $cases;
    }

    #[DataProvider('selfMoveSources')]
    public function testSelfMoveChecksFileIdentity(string $sourceType, string $targetType): void
    {
        $this->orgFile = $this->createTempFile();
        file_put_contents($this->orgFile, 'preserve this upload');
        $source = match ($sourceType) {
            'stream' => new Stream($this->orgFile),
            'resource' => fopen($this->orgFile, 'rb'),
            default => $this->orgFile,
        };
        $upload = new UploadedFile($source, null, UPLOAD_ERR_OK);
        try {
            $target = $this->orgFile;
            if ($targetType === 'relative') {
                $target = dirname($target) . DS . '.' . DS . basename($target);
            } elseif (in_array($targetType, ['hardlink', 'symlink'], true)) {
                $this->tmpFile = $this->createTempFile();
                unlink($this->tmpFile);
                $link = $targetType === 'hardlink' ? 'link' : 'symlink';
                // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged -- Skip systems without link permissions.
                if (!@$link($this->orgFile, $this->tmpFile)) {
                    $this->markTestSkipped('Cannot create a file link.');
                }
                $target = $this->tmpFile;
            }
            try {
                $upload->moveTo($target);
                $this->fail('A move onto the upload source must fail.');
            } catch (RuntimeException $exception) {
                $this->assertStringContainsString('paths must differ', $exception->getMessage());
                $this->assertSame('preserve this upload', file_get_contents($target));
                $this->assertSame('preserve this upload', (string)$upload->getStream());
            }
        } finally {
            $upload->getStream()->close();
        }
    }

    public function testEmptyNonEofReadFailsWithoutSpinning(): void
    {
        $this->tmpFile = $this->createTempFile();
        $stream = Mockery::mock(StreamInterface::class);
        $stream->shouldReceive('getMetadata')->with('uri')->andReturn(null);
        $stream->shouldReceive('isReadable')->andReturn(true);
        $stream->shouldReceive('isSeekable')->andReturn(false);
        $stream->shouldReceive('read')->with(4096)->once()->andReturn('');
        $stream->shouldReceive('eof')->once()->andReturn(false);
        $upload = new UploadedFile($stream, null, UPLOAD_ERR_OK);

        try {
            $upload->moveTo($this->tmpFile);
            $this->fail('An empty read before EOF must fail.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('returns no data before EOF', $exception->getMessage());
            $this->assertSame($stream, $upload->getStream());
        }
    }

    public static function failedUploadSources(): array
    {
        return ['missing source' => ['missing'], 'closed source' => ['closed'], 'read failure' => ['read']];
    }

    #[DataProvider('failedUploadSources')]
    public function testFailedCopyPreservesExistingDestination(string $failure): void
    {
        $this->orgFile = $this->createTempFile();
        $this->tmpFile = $this->createTempFile();
        file_put_contents($this->tmpFile, 'existing destination');
        $source = $this->orgFile;
        if ($failure === 'missing') {
            unlink($this->orgFile);
        } elseif ($failure === 'closed') {
            $source = new Stream($this->orgFile);
            $source->close();
        } else {
            $source = Mockery::mock(StreamInterface::class);
            $source->shouldReceive('getMetadata')->with('uri')->andReturn(null);
            $source->shouldReceive('isReadable')->andReturn(true);
            $source->shouldReceive('isSeekable')->andReturn(false);
            $source->shouldReceive('read')->once()->ordered()->andReturn('partial copy');
            $source->shouldReceive('eof')->once()->ordered()->andReturn(false);
            $source->shouldReceive('read')->once()->ordered()->andThrow(new RuntimeException('read failed'));
        }
        $upload = new UploadedFile($source, null, UPLOAD_ERR_OK);
        $temporaryFiles = glob(dirname($this->tmpFile) . DS . '.cake-upload-*');
        try {
            $upload->moveTo($this->tmpFile);
            $this->fail('Copying an unreadable upload must fail.');
        } catch (RuntimeException) {
            $this->assertSame('existing destination', file_get_contents($this->tmpFile));
            $this->assertSame($temporaryFiles, glob(dirname($this->tmpFile) . DS . '.cake-upload-*'));
        }
    }

    public function testMovePreservesDestinationPermissionsAndUsesUmaskForNewFiles(): void
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $this->markTestSkipped('Unix file permissions are required.');
        }
        $this->tmpFile = $this->createTempFile();
        chmod($this->tmpFile, 0o640);
        $upload = new UploadedFile(new Stream('data://text/plain,existing'), null, UPLOAD_ERR_OK);
        $upload->moveTo($this->tmpFile);
        clearstatcache(true, $this->tmpFile);
        $this->assertSame(0o640, fileperms($this->tmpFile) & 0o777);

        unlink($this->tmpFile);
        $mask = umask(0o027);
        try {
            $upload = new UploadedFile(new Stream('data://text/plain,new'), null, UPLOAD_ERR_OK);
            $upload->moveTo($this->tmpFile);
            clearstatcache(true, $this->tmpFile);
            $this->assertSame(0o640, fileperms($this->tmpFile) & 0o777);
        } finally {
            umask($mask);
        }
    }
}
