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
use JsonException;

/**
 * A response class for streaming large JSON datasets memory-efficiently.
 *
 * Uses true streaming - data is flushed to the client as each item is encoded,
 * keeping memory usage constant regardless of dataset size. Supports both
 * standard JSON arrays and NDJSON (newline-delimited JSON) formats.
 *
 * ### Usage
 *
 * ```php
 * // Simple array streaming
 * return new JsonStreamResponse($query);
 *
 * // With root wrapper
 * return new JsonStreamResponse($query, ['root' => 'articles']);
 *
 * // NDJSON format
 * return new JsonStreamResponse($query, ['format' => 'ndjson']);
 * ```
 *
 * ### ORM Integration
 *
 * For true streaming benefits, use unbuffered queries and avoid result formatters:
 *
 * ```php
 * // Good - streams one row at a time
 * $query = $this->Articles->find()->disableBufferedResults();
 * return new JsonStreamResponse($query);
 *
 * // Avoid - formatters like map(), combine() buffer results internally
 * $query = $this->Articles->find()->map(fn($row) => $row); // Breaks streaming
 * ```
 *
 * ### Memory Profile
 *
 * With true streaming, memory usage stays constant:
 * - 10,000 rows @ 1KB each: ~1KB memory (not ~10MB)
 * - 100,000 rows @ 1KB each: ~1KB memory (not ~100MB)
 * - Time to first byte: after first row (not after all rows)
 */
class JsonStreamResponse extends Response
{
    use InstanceConfigTrait;

    /**
     * Default JSON encoding flags (consistent with JsonView).
     *
     * @var int
     */
    public const DEFAULT_JSON_FLAGS = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_THROW_ON_ERROR;

    /**
     * JSON format constant.
     *
     * @var string
     */
    public const FORMAT_JSON = 'json';

    /**
     * NDJSON (newline-delimited JSON) format constant.
     *
     * @var string
     */
    public const FORMAT_NDJSON = 'ndjson';

    /**
     * Supported formats.
     *
     * @var array<string>
     */
    protected const SUPPORTED_FORMATS = [
        self::FORMAT_JSON,
        self::FORMAT_NDJSON,
    ];

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
     * Default streaming options.
     *
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [
        'root' => null,
        'envelope' => [],
        'dataKey' => 'data',
        'format' => self::FORMAT_JSON,
        'transform' => null,
        'flags' => self::DEFAULT_JSON_FLAGS,
        'flushEvery' => 1,
    ];

    /**
     * Constructor.
     *
     * @param iterable $data The iterable data to stream (array, generator, ResultSet, etc.)
     * @param array<string, mixed> $options Streaming options:
     *   - `root`: Wrap data in `{"root": [...]}` (string|null, default: null)
     *   - `envelope`: Static metadata merged with streaming data (array, default: [])
     *   - `dataKey`: Key for streaming data when envelope is used (string, default: 'data')
     *   - `format`: Output format - 'json' or 'ndjson' (string, default: 'json')
     *   - `transform`: Transform each item before encoding (callable|null, default: null)
     *   - `flags`: JSON encode flags (int, default: DEFAULT_JSON_FLAGS)
     *   - `flushEvery`: Flush output buffers every N items (int, default: 1)
     */
    public function __construct(iterable $data, array $options = [])
    {
        $this->data = $data;
        $this->setConfig($this->normalizeStreamOptions($options + $this->_defaultConfig, $options), null, false);

        $stream = new CallbackStream($this->createStreamCallback());
        parent::__construct(['stream' => $stream]);

        $this->applyStreamingHeaders();
    }

    /**
     * Create the streaming callback.
     *
     * The callback uses echo + flush for true streaming, sending data
     * to the client as each item is encoded rather than buffering everything.
     *
     * @return \Closure
     */
    protected function createStreamCallback(): Closure
    {
        return function (): void {
            if ($this->getConfigOrFail('format') === self::FORMAT_NDJSON) {
                $this->streamNdjson();
            } else {
                $this->streamJson();
            }
        };
    }

    /**
     * Stream data as standard JSON.
     *
     * Uses echo + flush to send data immediately to the client.
     *
     * @return void
     */
    protected function streamJson(): void
    {
        $flags = $this->getConfigOrFail('flags');
        $root = $this->getConfig('root');
        $envelope = $this->getConfigOrFail('envelope');
        $dataKey = $this->getConfigOrFail('dataKey');
        $hasWrapper = $root !== null || $envelope !== [];
        $hasItems = false;
        $index = 0;

        foreach ($this->data as $item) {
            if (!$hasItems) {
                $encoded = $this->encodeStreamItem($item, $flags, $index);
                $this->outputJsonPrefix($hasWrapper, $envelope, $root, $dataKey, $flags);
                $this->output('[');
                $this->outputAndFlush($encoded);
                $hasItems = true;
                $index++;

                continue;
            }

            try {
                $encoded = $this->encodeStreamItem($item, $flags, $index);
            } catch (JsonException $exception) {
                $this->output(',');
                $this->outputAndFlush($this->buildStreamErrorMarker($exception->getMessage(), $index), force: true);
                break;
            }

            $this->output(',');
            $this->outputAndFlush($encoded);
            $index++;
        }

        if (!$hasItems) {
            $this->outputJsonPrefix($hasWrapper, $envelope, $root, $dataKey, $flags);
            $this->output('[]');
            $this->outputJsonSuffix($hasWrapper);

            return;
        }

        $this->output(']');
        $this->outputJsonSuffix($hasWrapper);
        $this->flushOutputBuffers();
    }

    /**
     * Stream data as NDJSON (newline-delimited JSON).
     *
     * Uses echo + flush to send each line immediately to the client.
     *
     * @return void
     */
    protected function streamNdjson(): void
    {
        $flags = $this->getConfigOrFail('flags');
        $hasItems = false;
        $index = 0;

        foreach ($this->data as $item) {
            try {
                $encoded = $this->encodeStreamItem($item, $flags, $index);
            } catch (JsonException $exception) {
                $this->outputAndFlush($this->buildStreamErrorMarker($exception->getMessage(), $index) . "\n");
                break;
            }

            $this->outputAndFlush($encoded . "\n");
            $hasItems = true;
            $index++;
        }

        if ($hasItems) {
            $this->flushOutputBuffers();
        }
    }

    /**
     * Encode one stream item and normalize error handling.
     *
     * @param mixed $item Item to encode.
     * @param int $flags JSON encode flags.
     * @param int $index Item index.
     * @return string
     */
    protected function encodeStreamItem(mixed $item, int $flags, int $index): string
    {
        $transform = $this->getConfig('transform');
        if ($transform !== null) {
            $item = $transform($item);
        }

        try {
            $encoded = json_encode($item, $flags);
        } catch (JsonException $exception) {
            $this->logStreamError($exception->getMessage(), $index);

            throw $exception;
        }

        if ($encoded === false) {
            $message = json_last_error_msg();
            $this->logStreamError($message, $index);

            throw new JsonException($message);
        }

        return $encoded;
    }

    /**
     * Build the JSON wrapper prefix.
     *
     * @param bool $hasWrapper Whether wrapper output is needed.
     * @param array<string, mixed> $envelope Envelope data.
     * @param string|null $root Root key.
     * @param string $dataKey Data key.
     * @param int $flags JSON encode flags.
     * @return void
     */
    protected function outputJsonPrefix(
        bool $hasWrapper,
        array $envelope,
        ?string $root,
        string $dataKey,
        int $flags,
    ): void {
        if (!$hasWrapper) {
            return;
        }

        if ($envelope !== []) {
            $this->output('{');
            $parts = [];
            foreach ($envelope as $key => $value) {
                $parts[] = json_encode($key, $flags) . ':' . json_encode($value, $flags);
            }
            $this->output(implode(',', $parts));
            $this->output(',' . json_encode($root ?? $dataKey, $flags) . ':');

            return;
        }

        $this->output('{' . json_encode($root, $flags) . ':');
    }

    /**
     * Output the closing wrapper bytes when needed.
     *
     * @param bool $hasWrapper Whether wrapper output is needed.
     * @return void
     */
    protected function outputJsonSuffix(bool $hasWrapper): void
    {
        if ($hasWrapper) {
            $this->output('}');
        }
    }

    /**
     * Build the marker emitted after a mid-stream encoding failure.
     *
     * @param string $message Error message.
     * @param int $index Item index.
     * @return string
     */
    protected function buildStreamErrorMarker(string $message, int $index): string
    {
        return json_encode([
            '__streamError' => [
                'message' => $message,
                'index' => $index,
            ],
        ], JSON_THROW_ON_ERROR);
    }

    /**
     * Normalize and validate stream options.
     *
     * @param array<string, mixed> $options Merged options.
     * @param array<string, mixed> $originalOptions Original options passed by the caller.
     * @return array<string, mixed>
     */
    protected function normalizeStreamOptions(array $options, array $originalOptions = []): array
    {
        $format = $options['format'];
        if (!in_array($format, self::SUPPORTED_FORMATS, true)) {
            throw new InvalidArgumentException(sprintf(
                'Invalid format `%s`. Supported formats are: %s',
                $format,
                implode(', ', self::SUPPORTED_FORMATS),
            ));
        }

        if (Configure::read('debug') && !isset($originalOptions['flags'])) {
            $options['flags'] |= JSON_PRETTY_PRINT;
        }
        if (!is_int($options['flushEvery']) || $options['flushEvery'] < 1) {
            throw new InvalidArgumentException('`flushEvery` must be an integer greater than or equal to 1');
        }

        return $options;
    }

    /**
     * Apply headers derived from the active stream options.
     *
     * @return void
     */
    protected function applyStreamingHeaders(): void
    {
        $charset = Configure::read('App.encoding') ?? 'UTF-8';
        $mimeType = $this->getConfigOrFail('format') === self::FORMAT_NDJSON
            ? 'application/x-ndjson'
            : 'application/json';
        $contentType = $mimeType . '; charset=' . $charset;

        $this->_setHeader('Content-Type', $contentType);
        // Prevent proxy/nginx buffering for true streaming
        $this->_setHeader('X-Accel-Buffering', 'no');
    }

    /**
     * Output data without flushing.
     *
     * Used for structural elements like brackets that don't need immediate flushing.
     *
     * @param string $data The data to output.
     * @return void
     */
    protected function output(string $data): void
    {
        echo $data;
    }

    /**
     * Output data and flush to client immediately.
     *
     * This ensures data is sent to the client right away rather than
     * being buffered, enabling true streaming behavior.
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
     * @return void
     */
    protected function flushOutputBuffers(): void
    {
        // Only flush if we're at the implicit output buffer level (1) or no buffering.
        // Higher levels indicate explicit buffering (e.g., tests) that we shouldn't disturb.
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
     * @param string $message Error message.
     * @param int $index Item index where error occurred.
     * @return void
     */
    protected function logStreamError(string $message, int $index): void
    {
        if (class_exists(Log::class)) {
            Log::error(sprintf(
                'JsonStreamResponse encoding failed at index %d: %s',
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
