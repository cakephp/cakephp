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
 * @since         5.4.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Http\Response;

use Cake\Core\Configure;
use Cake\Core\InstanceConfigTrait;
use Cake\Http\CallbackStream;
use Cake\Http\Response;
use Cake\Log\Log;
use Closure;
use InvalidArgumentException;

/**
 * Base class for response types that stream an `iterable` payload to the
 * client memory-efficiently.
 *
 * Concrete subclasses such as {@see \Cake\Http\Response\JsonStreamResponse}
 * produce the wire format (JSON, CSV, XML, …); this class owns the streaming
 * lifecycle that is common to all of them:
 *
 * - Wires a {@see \Cake\Http\CallbackStream} into the response body.
 * - Emits the streaming-friendly headers (Content-Type with charset and
 *   `X-Accel-Buffering: no` so proxies / nginx do not buffer the response).
 * - Provides the {@see self::output()}, {@see self::outputAndFlush()} and
 *   {@see self::flushOutputBuffers()} primitives subclasses use to write
 *   the wire format, including the threshold-based flush counter driven by
 *   the `flushEvery` option.
 * - Provides {@see self::logStreamError()} (gated on `cakephp/log` being
 *   installed) for surfacing encoding failures server-side.
 * - Implements the PSR-7 immutability helpers
 *   ({@see self::withStreamOptions()} / {@see self::getStreamOptions()}).
 *
 * Subclasses must implement {@see self::contentType()} (the MIME type without
 * the charset suffix) and {@see self::streamData()} (the actual write loop).
 * They may override {@see self::normalizeStreamOptions()} to validate
 * format-specific options on top of the base validation.
 */
abstract class AbstractStreamResponse extends Response
{
    use InstanceConfigTrait;

    /**
     * The iterable data to stream.
     *
     * @var iterable
     */
    protected iterable $data;

    /**
     * Number of streamed rows since the last flush.
     *
     * @var int
     */
    protected int $rowsSinceLastFlush = 0;

    /**
     * Default streaming options shared by all concrete subclasses.
     *
     * Subclasses extend this array with their format-specific options.
     *
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [
        'flushEvery' => 1,
    ];

    /**
     * Constructor.
     *
     * @param iterable $data The iterable data to stream (array, generator, ResultSet, etc.).
     * @param array<string, mixed> $options Streaming options. The base accepts
     *   `flushEvery` (int >= 1) controlling how many items are buffered before
     *   flushing. Subclasses add their own keys.
     */
    public function __construct(iterable $data, array $options = [])
    {
        $this->data = $data;
        $this->setConfig(
            $this->normalizeStreamOptions($options + $this->_defaultConfig, $options),
            null,
            false,
        );

        $stream = new CallbackStream($this->createStreamCallback());
        parent::__construct(['stream' => $stream]);

        $this->applyStreamingHeaders();
    }

    /**
     * Create the streaming callback installed on the response body.
     *
     * @return \Closure
     */
    protected function createStreamCallback(): Closure
    {
        return function (): void {
            $this->streamData();
        };
    }

    /**
     * Write the streamed payload using {@see self::output()} /
     * {@see self::outputAndFlush()}.
     *
     * Called from the response body callback. Subclasses own the wire format,
     * including any wrapper bytes, item separators and end-of-stream handling.
     *
     * @return void
     */
    abstract protected function streamData(): void;

    /**
     * Return the response MIME type without the charset suffix.
     *
     * Called when applying streaming headers so subclasses can return a
     * different value depending on the active stream options (e.g. JSON vs
     * NDJSON share one response class but emit different content types).
     *
     * @return string
     */
    abstract protected function contentType(): string;

    /**
     * Validate and normalize the streaming options.
     *
     * Subclasses overriding this method should call `parent::normalizeStreamOptions()`
     * so the shared options (currently `flushEvery`) keep their validation.
     *
     * @param array<string, mixed> $options Merged options.
     * @param array<string, mixed> $originalOptions Original options passed by the caller.
     * @return array<string, mixed>
     */
    protected function normalizeStreamOptions(array $options, array $originalOptions = []): array
    {
        if (!is_int($options['flushEvery']) || $options['flushEvery'] < 1) {
            throw new InvalidArgumentException('`flushEvery` must be an integer greater than or equal to 1');
        }

        return $options;
    }

    /**
     * Apply headers derived from the active stream options.
     *
     * Sets the Content-Type (with charset) returned by {@see self::contentType()}
     * and `X-Accel-Buffering: no` so reverse proxies do not buffer the body.
     *
     * @return void
     */
    protected function applyStreamingHeaders(): void
    {
        $charset = Configure::read('App.encoding') ?? 'UTF-8';
        $contentType = $this->contentType() . '; charset=' . $charset;

        $this->_setHeader('Content-Type', $contentType);
        $this->_setHeader('X-Accel-Buffering', 'no');
    }

    /**
     * Output data without flushing.
     *
     * Used for structural bytes like wrapper brackets / separators that do not
     * need an immediate flush.
     *
     * @param string $data The data to output.
     * @return void
     */
    protected function output(string $data): void
    {
        echo $data;
    }

    /**
     * Output data and flush to the client subject to the `flushEvery` threshold.
     *
     * @param string $data The data to output and flush.
     * @param bool $force Whether to force an immediate flush regardless of threshold.
     * @return void
     */
    protected function outputAndFlush(string $data, bool $force = false): void
    {
        echo $data;
        $this->rowsSinceLastFlush++;

        if ($force || $this->rowsSinceLastFlush >= $this->getConfigOrFail('flushEvery')) {
            $this->flushOutputBuffers();
        }
    }

    /**
     * Flush output buffers when it is safe to do so.
     *
     * Only flushes at the implicit output buffer level (1) or when no buffering
     * is active. Higher levels indicate explicit buffering (e.g. tests wrapping
     * the call in `ob_start()`) which should not be disturbed.
     *
     * @return void
     */
    protected function flushOutputBuffers(): void
    {
        $level = ob_get_level();
        if ($level <= 1) {
            if ($level === 1) {
                ob_flush();
            }
            flush();
            $this->rowsSinceLastFlush = 0;
        }
    }

    /**
     * Log a streaming error.
     *
     * No-op when `cakephp/log` is not installed (keeps the dependency
     * optional for the `cakephp/http` package).
     *
     * @param string $message Error message.
     * @param int $index Item index where the error occurred.
     * @return void
     */
    protected function logStreamError(string $message, int $index): void
    {
        if (class_exists(Log::class)) {
            Log::error(sprintf(
                '%s encoding failed at index %d: %s',
                static::class,
                $index,
                $message,
            ));
        }
    }

    /**
     * Get the streaming options.
     *
     * @return array<string, mixed>
     */
    public function getStreamOptions(): array
    {
        return $this->getConfig();
    }

    /**
     * Return an instance with updated streaming options.
     *
     * The body callback is rebuilt so the new instance streams using the
     * updated options.
     *
     * @param array<string, mixed> $options Options to merge with existing options.
     * @return static
     */
    public function withStreamOptions(array $options): static
    {
        $new = clone $this;
        $new->setConfig(
            $this->normalizeStreamOptions($options + $this->getConfig(), $options),
            null,
            false,
        );
        $new->applyStreamingHeaders();

        return $new->withBody(new CallbackStream($new->createStreamCallback()));
    }
}
