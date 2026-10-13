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
use RuntimeException;
use Stringable;
use Throwable;

/**
 * PSR-7 stream implementation wrapping a PHP stream resource.
 */
class Stream implements StreamInterface, Stringable
{
    /**
     * The wrapped stream resource.
     *
     * @var resource|null
     */
    protected $resource;

    /**
     * Cached capabilities of the attached resource.
     *
     * @var bool|null
     */
    protected ?bool $readable = null;

    /**
     * @var bool|null
     */
    protected ?bool $writable = null;

    /**
     * @var bool|null
     */
    protected ?bool $seekable = null;

    /**
     * Constructor
     *
     * @param resource|string $stream A stream resource or a filename/stream URI to open.
     * @param string $mode The mode to open the stream with when a string is given.
     * @throws \InvalidArgumentException When the provided value is not a valid stream resource.
     * @throws \RuntimeException When the stream cannot be opened.
     */
    public function __construct(mixed $stream, string $mode = 'r')
    {
        $this->attach($stream, $mode);
    }

    /**
     * Attach a new stream/resource to the instance.
     *
     * @param resource|string $stream A stream resource or a filename/stream URI to open.
     * @param string $mode The mode to open the stream with when a string is given.
     * @return void
     * @throws \InvalidArgumentException When the provided value is not a valid stream resource.
     * @throws \RuntimeException When the stream cannot be opened.
     */
    public function attach(mixed $stream, string $mode = 'r'): void
    {
        $resource = $stream;
        if (is_string($stream)) {
            $error = null;
            set_error_handler(static fn(): bool => true);
            try {
                $resource = fopen($stream, $mode);
            } catch (Throwable $error) {
                $resource = false;
            } finally {
                restore_error_handler();
            }
            if (!is_resource($resource)) {
                throw new RuntimeException(
                    sprintf('Empty or non-existent stream identifier or file path provided: `%s`.', $stream),
                    0,
                    $error,
                );
            }
        }

        if (!is_resource($resource) || get_resource_type($resource) !== 'stream') {
            throw new InvalidArgumentException(
                'Invalid stream provided; must be a string stream identifier or stream resource.',
            );
        }

        $this->resource = $resource;
        $this->readable = $this->writable = $this->seekable = null;
    }

    /**
     * @inheritDoc
     */
    public function __toString(): string
    {
        if (!$this->isReadable()) {
            return '';
        }

        try {
            if ($this->isSeekable()) {
                $this->rewind();
            }

            return $this->getContents();
        } catch (RuntimeException) {
            return '';
        }
    }

    /**
     * @inheritDoc
     */
    public function close(): void
    {
        $resource = $this->detach();
        if ($resource !== null) {
            fclose($resource);
        }
    }

    /**
     * @return resource|null
     */
    public function detach(): mixed
    {
        $resource = $this->resource;
        $this->resource = null;

        return $resource;
    }

    /**
     * @inheritDoc
     */
    public function getSize(): ?int
    {
        if ($this->resource === null) {
            return null;
        }

        $stats = fstat($this->resource);

        return $stats === false ? null : $stats['size'];
    }

    /**
     * @inheritDoc
     */
    public function tell(): int
    {
        $result = ftell($this->getResource());
        if ($result === false) {
            throw new RuntimeException('Error occurred during tell operation.');
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function eof(): bool
    {
        return $this->resource === null || feof($this->resource);
    }

    /**
     * @inheritDoc
     */
    public function isSeekable(): bool
    {
        if ($this->resource === null) {
            return false;
        }

        return $this->seekable ??= (bool)stream_get_meta_data($this->resource)['seekable'];
    }

    /**
     * @inheritDoc
     */
    public function seek(int $offset, int $whence = SEEK_SET): void
    {
        $resource = $this->getResource();
        if (!$this->isSeekable()) {
            throw new RuntimeException('Stream is not seekable.');
        }
        if (fseek($resource, $offset, $whence) !== 0) {
            throw new RuntimeException('Error seeking within stream.');
        }
    }

    /**
     * @inheritDoc
     */
    public function rewind(): void
    {
        $this->seek(0);
    }

    /**
     * @inheritDoc
     */
    public function isWritable(): bool
    {
        if ($this->resource === null) {
            return false;
        }

        return $this->writable ??= strpbrk(stream_get_meta_data($this->resource)['mode'], 'xwca+') !== false;
    }

    /**
     * @inheritDoc
     */
    public function write(string $string): int
    {
        $resource = $this->getResource();
        if (!$this->isWritable()) {
            throw new RuntimeException('Stream is not writable.');
        }

        $result = fwrite($resource, $string);
        if ($result === false) {
            throw new RuntimeException('Error writing to stream.');
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function isReadable(): bool
    {
        if ($this->resource === null) {
            return false;
        }

        return $this->readable ??= strpbrk(stream_get_meta_data($this->resource)['mode'], 'r+') !== false;
    }

    /**
     * @inheritDoc
     */
    public function read(int $length): string
    {
        $resource = $this->getResource();
        if (!$this->isReadable()) {
            throw new RuntimeException('Stream is not readable.');
        }

        if ($length < 1) {
            return '';
        }

        $result = fread($resource, $length);
        if ($result === false) {
            throw new RuntimeException('Error reading stream.');
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function getContents(): string
    {
        $resource = $this->getResource();
        if (!$this->isReadable()) {
            throw new RuntimeException('Stream is not readable.');
        }

        $result = stream_get_contents($resource);
        if ($result === false) {
            throw new RuntimeException('Error reading stream.');
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function getMetadata(?string $key = null): mixed
    {
        $metadata = $this->resource === null ? [] : stream_get_meta_data($this->resource);
        if ($key === null) {
            return $metadata;
        }

        return $metadata[$key] ?? null;
    }

    /**
     * Get the attached resource or fail.
     *
     * @return resource
     * @throws \RuntimeException When no resource is attached.
     */
    protected function getResource()
    {
        if ($this->resource === null) {
            throw new RuntimeException('No resource available; stream is detached.');
        }

        return $this->resource;
    }
}
