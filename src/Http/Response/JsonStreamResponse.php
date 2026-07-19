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
 * ### Options
 *
 * - `root` (string|null, default: null): Wrap data in `{"root": [...]}`
 * - `envelope` (array, default: []): Static metadata merged with streaming data
 * - `dataKey` (string, default: 'data'): Key for streaming data when envelope is used
 * - `format` (string, default: 'json'): Output format — 'json' or 'ndjson'
 * - `transform` (callable|null, default: null): Transform each item before encoding
 * - `flags` (int, default: DEFAULT_JSON_FLAGS): JSON encode flags
 * - `flushEvery` (int, default: 1): Flush output buffers every N items
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
class JsonStreamResponse extends AbstractStreamResponse
{
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
     * @inheritDoc
     */
    protected function streamData(): void
    {
        if ($this->getConfigOrFail('format') === self::FORMAT_NDJSON) {
            $this->streamNdjson();
        } else {
            $this->streamJson();
        }
    }

    /**
     * @inheritDoc
     */
    protected function contentType(): string
    {
        return $this->getConfigOrFail('format') === self::FORMAT_NDJSON
            ? 'application/x-ndjson'
            : 'application/json';
    }

    /**
     * Stream data as standard JSON.
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
     * @inheritDoc
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

        return parent::normalizeStreamOptions($options, $originalOptions);
    }
}
