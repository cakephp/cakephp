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
 * @since         3.3.4
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 *
 * Parts of this file are derived from laminas-diactoros
 * @copyright Copyright (c) 2020 Laminas Project a Series of LF Projects, LLC. (https://getlaminas.org/)
 * @license   https://github.com/laminas/laminas-diactoros/blob/3.9.x/LICENSE.md New BSD License
 */
namespace Cake\Http;

use Closure;
use Psr\Http\Message\StreamInterface;
use RuntimeException;
use Stringable;

/**
 * Stream implementation that generates its content using a callback.
 *
 * The callback is invoked once when the stream contents are read. The
 * callback can either return a string or use `echo` to output content
 * directly. Non-string return values are ignored.
 *
 * @internal
 */
class CallbackStream implements StreamInterface, Stringable
{
    /**
     * The callback generating the stream contents.
     *
     * @var \Closure|null
     */
    protected ?Closure $callback = null;

    /**
     * Constructor
     *
     * @param callable $callback The callback generating the stream contents.
     */
    public function __construct(callable $callback)
    {
        $this->attach($callback);
    }

    /**
     * Attach a new callback to the instance.
     *
     * @param callable $callback The callback generating the stream contents.
     * @return void
     */
    public function attach(callable $callback): void
    {
        $this->callback = $callback(...);
    }

    /**
     * @inheritDoc
     */
    public function __toString(): string
    {
        return $this->getContents();
    }

    /**
     * @inheritDoc
     */
    public function close(): void
    {
        $this->callback = null;
    }

    /**
     * @inheritDoc
     */
    public function detach(): ?Closure
    {
        $callback = $this->callback;
        $this->callback = null;

        return $callback;
    }

    /**
     * @inheritDoc
     */
    public function getSize(): ?int
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function tell(): int
    {
        throw new RuntimeException('Callback streams cannot tell position.');
    }

    /**
     * @inheritDoc
     */
    public function eof(): bool
    {
        return $this->callback === null;
    }

    /**
     * @inheritDoc
     */
    public function isSeekable(): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function seek(int $offset, int $whence = SEEK_SET): void
    {
        throw new RuntimeException('Callback streams cannot seek position.');
    }

    /**
     * @inheritDoc
     */
    public function rewind(): void
    {
        throw new RuntimeException('Callback streams cannot rewind position.');
    }

    /**
     * @inheritDoc
     */
    public function isWritable(): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function write(string $string): int
    {
        throw new RuntimeException('Callback streams cannot write.');
    }

    /**
     * @inheritDoc
     */
    public function isReadable(): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function read(int $length): string
    {
        throw new RuntimeException('Callback streams cannot read.');
    }

    /**
     * @inheritDoc
     */
    public function getContents(): string
    {
        $callback = $this->detach();
        $result = '';
        if ($callback !== null) {
            $result = $callback();
        }
        if (!is_string($result)) {
            return '';
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function getMetadata(?string $key = null): mixed
    {
        $metadata = [
            'eof' => $this->eof(),
            'stream_type' => 'callback',
            'seekable' => false,
        ];
        if ($key === null) {
            return $metadata;
        }

        return $metadata[$key] ?? null;
    }
}
