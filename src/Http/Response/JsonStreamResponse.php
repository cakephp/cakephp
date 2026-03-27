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
 * @since         5.2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Http\Response;

use Cake\Http\CallbackStream;
use Cake\Http\Response;

/**
 * A response class for streaming large JSON datasets memory-efficiently.
 *
 * Supports both standard JSON arrays and NDJSON (newline-delimited JSON) formats.
 * Uses generators to yield JSON chunks, keeping only one item in memory at a time.
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
 */
class JsonStreamResponse extends Response
{
    /**
     * Default JSON encoding flags (consistent with JsonView).
     *
     * @var int
     */
    public const DEFAULT_JSON_FLAGS = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_THROW_ON_ERROR;

    /**
     * The iterable data to stream.
     *
     * @var iterable
     */
    protected iterable $data;

    /**
     * Streaming options.
     *
     * @var array<string, mixed>
     */
    protected array $streamOptions = [];

    /**
     * Default streaming options.
     *
     * @var array<string, mixed>
     */
    protected array $defaultStreamOptions = [
        'root' => null,
        'envelope' => [],
        'dataKey' => 'data',
        'format' => 'json',
        'transform' => null,
        'flags' => self::DEFAULT_JSON_FLAGS,
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
     */
    public function __construct(iterable $data, array $options = [])
    {
        $this->data = $data;
        $this->streamOptions = $options + $this->defaultStreamOptions;

        $contentType = $this->streamOptions['format'] === 'ndjson'
            ? 'application/x-ndjson; charset=UTF-8'
            : 'application/json; charset=UTF-8';

        $stream = new CallbackStream($this->createStreamCallback());
        parent::__construct(['stream' => $stream]);

        $this->_setHeader('Content-Type', $contentType);
    }

    /**
     * Create the streaming callback.
     *
     * @return callable
     */
    protected function createStreamCallback(): callable
    {
        return function (): string {
            if ($this->streamOptions['format'] === 'ndjson') {
                return $this->streamNdjson();
            }

            return $this->streamJson();
        };
    }

    /**
     * Stream data as standard JSON.
     *
     * @return string
     */
    protected function streamJson(): string
    {
        $output = '';
        $flags = $this->streamOptions['flags'];
        $transform = $this->streamOptions['transform'];
        $root = $this->streamOptions['root'];
        $envelope = $this->streamOptions['envelope'];
        $dataKey = $this->streamOptions['dataKey'];

        $hasWrapper = $root !== null || $envelope !== [];

        if ($hasWrapper) {
            if ($envelope !== []) {
                $output .= '{';
                $parts = [];
                foreach ($envelope as $key => $value) {
                    $parts[] = json_encode($key, $flags) . ':' . json_encode($value, $flags);
                }
                $output .= implode(',', $parts);
                $output .= ',' . json_encode($root ?? $dataKey, $flags) . ':';
            } else {
                $output .= '{' . json_encode($root, $flags) . ':';
            }
        }

        $output .= '[';
        $first = true;

        foreach ($this->data as $item) {
            if ($transform !== null) {
                $item = $transform($item);
            }

            if (!$first) {
                $output .= ',';
            }
            $output .= json_encode($item, $flags);
            $first = false;
        }

        $output .= ']';

        if ($hasWrapper) {
            $output .= '}';
        }

        return $output;
    }

    /**
     * Stream data as NDJSON (newline-delimited JSON).
     *
     * @return string
     */
    protected function streamNdjson(): string
    {
        $output = '';
        $flags = $this->streamOptions['flags'];
        $transform = $this->streamOptions['transform'];

        foreach ($this->data as $item) {
            if ($transform !== null) {
                $item = $transform($item);
            }

            $output .= json_encode($item, $flags) . "\n";
        }

        return $output;
    }

    /**
     * Get the streaming options.
     *
     * @return array<string, mixed>
     */
    public function getStreamOptions(): array
    {
        return $this->streamOptions;
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
        $new->streamOptions = $options + $new->streamOptions;

        return $new->withBody(new CallbackStream($new->createStreamCallback()));
    }
}
