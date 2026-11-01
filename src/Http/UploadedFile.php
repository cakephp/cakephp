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
namespace Cake\Http;

use InvalidArgumentException;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
use RuntimeException;

/**
 * PSR-7 uploaded file implementation.
 */
class UploadedFile implements UploadedFileInterface
{
    /**
     * Human readable messages for the `UPLOAD_ERR_*` constants.
     *
     * @var array<int, string>
     */
    public const array ERROR_MESSAGES = [
        UPLOAD_ERR_OK => 'There is no error, the file uploaded with success',
        UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
        UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was '
            . 'specified in the HTML form',
        UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded',
        UPLOAD_ERR_NO_FILE => 'No file was uploaded',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
        UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload',
    ];

    /**
     * Path to the uploaded file.
     *
     * @var string|null
     */
    protected ?string $file = null;

    /**
     * Stream of the uploaded file.
     *
     * @var \Psr\Http\Message\StreamInterface|null
     */
    protected ?StreamInterface $stream = null;

    /**
     * Whether the file has been moved already.
     *
     * @var bool
     */
    protected bool $moved = false;

    /**
     * Constructor
     *
     * @param \Psr\Http\Message\StreamInterface|resource|string $streamOrFile The file path, resource or stream.
     * @param int|null $size The file size in bytes.
     * @param int $error One of the `UPLOAD_ERR_*` constants.
     * @param string|null $clientFilename The filename as provided by the client.
     * @param string|null $clientMediaType The media type as provided by the client.
     * @throws \InvalidArgumentException When the stream/file or error status is invalid.
     */
    public function __construct(
        mixed $streamOrFile,
        protected readonly ?int $size,
        protected readonly int $error,
        protected readonly ?string $clientFilename = null,
        protected readonly ?string $clientMediaType = null,
    ) {
        if (!isset(static::ERROR_MESSAGES[$error])) {
            throw new InvalidArgumentException(
                'Invalid error status for UploadedFile; must be an UPLOAD_ERR_* constant.',
            );
        }
        if ($error !== UPLOAD_ERR_OK) {
            return;
        }

        if (is_string($streamOrFile)) {
            $this->file = $streamOrFile;

            return;
        }
        if (is_resource($streamOrFile)) {
            $this->stream = new Stream($streamOrFile);

            return;
        }
        if ($streamOrFile instanceof StreamInterface) {
            $this->stream = $streamOrFile;

            return;
        }

        throw new InvalidArgumentException('Invalid stream or file provided for UploadedFile.');
    }

    /**
     * @inheritDoc
     */
    public function getStream(): StreamInterface
    {
        $this->assertMovable();

        return $this->stream ??= new Stream((string)$this->file);
    }

    /**
     * @inheritDoc
     */
    public function moveTo(string $targetPath): void
    {
        $this->assertMovable();

        if ($targetPath === '') {
            throw new InvalidArgumentException(
                'Invalid path provided for move operation; must be a non-empty string.',
            );
        }

        $targetDirectory = dirname($targetPath);
        if (!is_dir($targetDirectory) || !is_writable($targetDirectory)) {
            throw new RuntimeException(sprintf(
                'The target directory `%s` does not exist or is not writable.',
                $targetDirectory,
            ));
        }

        $sourcePath = $this->file ?? $this->stream?->getMetadata('uri');
        if (is_string($sourcePath) && is_file($sourcePath) && is_file($targetPath)) {
            $sourceStat = stat($sourcePath);
            $targetStat = stat($targetPath);
            $sameFile = realpath($sourcePath) === realpath($targetPath);
            if ($sourceStat !== false && $targetStat !== false && $sourceStat['ino'] !== 0) {
                $sameFile = $sameFile || (
                    $sourceStat['dev'] === $targetStat['dev'] && $sourceStat['ino'] === $targetStat['ino']
                );
            }
            if ($sameFile) {
                throw new RuntimeException('The upload source and target paths must differ.');
            }
        }

        $isCli = str_starts_with(PHP_SAPI, 'cli') || str_starts_with(PHP_SAPI, 'phpdbg');
        if ($this->file !== null && !$isCli) {
            if (!move_uploaded_file($this->file, $targetPath)) {
                throw new RuntimeException('Error occurred while moving uploaded file.');
            }
            $this->moved = true;

            return;
        }

        $this->writeFile($targetPath);
        $this->stream?->close();
        if ($this->file !== null && file_exists($this->file)) {
            unlink($this->file);
        }
        $this->moved = true;
    }

    /**
     * @inheritDoc
     */
    public function getSize(): ?int
    {
        return $this->size;
    }

    /**
     * @inheritDoc
     */
    public function getError(): int
    {
        return $this->error;
    }

    /**
     * @inheritDoc
     */
    public function getClientFilename(): ?string
    {
        return $this->clientFilename;
    }

    /**
     * @inheritDoc
     */
    public function getClientMediaType(): ?string
    {
        return $this->clientMediaType;
    }

    /**
     * Ensure the upload succeeded and hasn't been moved yet.
     *
     * @return void
     * @throws \RuntimeException
     */
    protected function assertMovable(): void
    {
        if ($this->error !== UPLOAD_ERR_OK) {
            throw new RuntimeException(sprintf(
                'Cannot retrieve stream due to upload error: %s',
                static::ERROR_MESSAGES[$this->error],
            ));
        }
        if ($this->moved) {
            throw new RuntimeException('Cannot retrieve stream after it has already been moved.');
        }
    }

    /**
     * Write the uploaded file stream to the given path.
     *
     * @param string $path The target path.
     * @return void
     * @throws \RuntimeException When the target path can't be opened.
     */
    protected function writeFile(string $path): void
    {
        if (preg_match('#^[a-z][a-z0-9+.-]*://#i', $path)) {
            throw new RuntimeException('Upload destinations must be local filesystem paths.');
        }
        $stream = $this->getStream();
        if (!$stream->isReadable()) {
            throw new RuntimeException('The upload source is not readable.');
        }
        if ($stream->isSeekable()) {
            $stream->rewind();
        }

        $temporary = tempnam(dirname($path), '.cake-upload-');
        if ($temporary === false) {
            throw new RuntimeException('Unable to create a temporary upload file.');
        }
        try {
            $handle = fopen($temporary, 'wb');
            if ($handle === false) {
                throw new RuntimeException('Unable to open the temporary upload file.');
            }
            try {
                $this->copyStream($stream, $handle, $path);
            } finally {
                fclose($handle);
            }
            $mode = is_file($path) ? fileperms($path) : false;
            $mode = $mode === false ? 0o666 & ~umask() : ($mode & 0o777);
            set_error_handler(static function (int $severity, string $message): never {
                throw new RuntimeException($message);
            });
            try {
                if (!chmod($temporary, $mode)) {
                    throw new RuntimeException('Unable to set upload destination permissions.');
                }
                if (!rename($temporary, $path)) {
                    throw new RuntimeException(sprintf('Unable to replace designated path `%s`.', $path));
                }
            } finally {
                restore_error_handler();
            }
        } finally {
            if (file_exists($temporary)) {
                unlink($temporary);
            }
        }
    }

    /**
     * Copy a readable upload into an open destination, checking every write.
     *
     * @param \Psr\Http\Message\StreamInterface $stream The upload source.
     * @param resource $handle The destination handle.
     * @param string $path The destination path for error messages.
     * @return void
     */
    protected function copyStream(StreamInterface $stream, mixed $handle, string $path): void
    {
        do {
            $chunk = $stream->read(4096);
            $eof = $stream->eof();
            if ($chunk === '' && !$eof) {
                throw new RuntimeException('Cannot copy an upload that returns no data before EOF.');
            }
            while ($chunk !== '') {
                $written = fwrite($handle, $chunk);
                if ($written === false || $written === 0) {
                    throw new RuntimeException(sprintf('Unable to write to designated path `%s`.', $path));
                }
                $chunk = substr($chunk, $written);
            }
        } while (!$eof);
        if (!fflush($handle)) {
            throw new RuntimeException(sprintf('Unable to flush designated path `%s`.', $path));
        }
    }
}
