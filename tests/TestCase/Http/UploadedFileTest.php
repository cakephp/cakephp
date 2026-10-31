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
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;

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
}
