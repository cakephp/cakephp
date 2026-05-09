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
namespace Cake\Test\TestCase\Http\Response;

use Cake\Core\Configure;
use Cake\Http\Response\JsonStreamResponse;
use Cake\TestSuite\TestCase;
use InvalidArgumentException;
use JsonException;
use Throwable;

class JsonStreamResponseTest extends TestCase
{
    /**
     * @var bool|null
     */
    protected bool|null $originalDebug = null;

    protected function setUp(): void
    {
        parent::setUp();
        // Save and disable debug mode for consistent test output
        $this->originalDebug = Configure::read('debug');
        Configure::write('debug', false);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        // Restore debug mode
        Configure::write('debug', $this->originalDebug);
    }

    /**
     * Get the streamed body content.
     *
     * Since JsonStreamResponse uses echo for true streaming,
     * we need to capture the output with output buffering.
     *
     * @param \Cake\Http\Response\JsonStreamResponse $response The response to stream.
     * @return string The captured output.
     */
    protected function getStreamedBody(JsonStreamResponse $response): string
    {
        ob_start();
        try {
            // Trigger the stream by converting to string (calls __toString -> getContents -> callback)
            (string)$response->getBody();
        } catch (Throwable $exception) {
            ob_end_clean();

            throw $exception;
        }

        return ob_get_clean() ?: '';
    }

    public function testSimpleArrayStreaming(): void
    {
        $data = [
            ['id' => 1, 'name' => 'Alice'],
            ['id' => 2, 'name' => 'Bob'],
        ];

        $response = new JsonStreamResponse($data);
        $body = $this->getStreamedBody($response);

        $this->assertSame('[{"id":1,"name":"Alice"},{"id":2,"name":"Bob"}]', $body);
        $this->assertSame('application/json; charset=UTF-8', $response->getHeaderLine('Content-Type'));
        $this->assertSame('no', $response->getHeaderLine('X-Accel-Buffering'));
    }

    public function testWithRootWrapper(): void
    {
        $data = [
            ['id' => 1, 'title' => 'First'],
            ['id' => 2, 'title' => 'Second'],
        ];

        $response = new JsonStreamResponse($data, ['root' => 'articles']);
        $body = $this->getStreamedBody($response);

        $this->assertSame('{"articles":[{"id":1,"title":"First"},{"id":2,"title":"Second"}]}', $body);
    }

    public function testWithEnvelope(): void
    {
        $data = [
            ['id' => 1, 'title' => 'First'],
        ];

        $response = new JsonStreamResponse($data, [
            'envelope' => ['meta' => ['total' => 100, 'page' => 1]],
            'dataKey' => 'articles',
        ]);
        $body = $this->getStreamedBody($response);

        $decoded = json_decode($body, true);
        $this->assertSame(['total' => 100, 'page' => 1], $decoded['meta']);
        $this->assertSame([['id' => 1, 'title' => 'First']], $decoded['articles']);
    }

    public function testNdjsonFormat(): void
    {
        $data = [
            ['id' => 1, 'name' => 'Alice'],
            ['id' => 2, 'name' => 'Bob'],
        ];

        $response = new JsonStreamResponse($data, ['format' => 'ndjson']);
        $body = $this->getStreamedBody($response);

        $expected = "{\"id\":1,\"name\":\"Alice\"}\n{\"id\":2,\"name\":\"Bob\"}\n";
        $this->assertSame($expected, $body);
        $this->assertSame('application/x-ndjson; charset=UTF-8', $response->getHeaderLine('Content-Type'));
    }

    public function testTransformCallback(): void
    {
        $data = [
            (object)['id' => 1, 'name' => 'Alice', 'secret' => 'hidden'],
            (object)['id' => 2, 'name' => 'Bob', 'secret' => 'hidden'],
        ];

        $response = new JsonStreamResponse($data, [
            'transform' => fn($item) => ['id' => $item->id, 'name' => $item->name],
        ]);
        $body = $this->getStreamedBody($response);

        $this->assertSame('[{"id":1,"name":"Alice"},{"id":2,"name":"Bob"}]', $body);
        $this->assertStringNotContainsString('secret', $body);
    }

    public function testEmptyIterable(): void
    {
        $response = new JsonStreamResponse([]);
        $body = $this->getStreamedBody($response);

        $this->assertSame('[]', $body);
    }

    public function testEmptyIterableWithRoot(): void
    {
        $response = new JsonStreamResponse([], ['root' => 'data']);
        $body = $this->getStreamedBody($response);

        $this->assertSame('{"data":[]}', $body);
    }

    public function testEmptyIterableNdjson(): void
    {
        $response = new JsonStreamResponse([], ['format' => 'ndjson']);
        $body = $this->getStreamedBody($response);

        $this->assertSame('', $body);
    }

    public function testGeneratorInput(): void
    {
        $generator = function () {
            yield ['id' => 1];
            yield ['id' => 2];
            yield ['id' => 3];
        };

        $response = new JsonStreamResponse($generator());
        $body = $this->getStreamedBody($response);

        $this->assertSame('[{"id":1},{"id":2},{"id":3}]', $body);
    }

    public function testFirstItemValidationThrows(): void
    {
        $resource = fopen('php://memory', 'r');
        $this->assertIsResource($resource);

        $data = [
            ['id' => 1, 'resource' => $resource],
        ];

        $response = new JsonStreamResponse($data);

        try {
            $this->expectException(JsonException::class);
            $this->getStreamedBody($response);
        } finally {
            fclose($resource);
        }
    }

    public function testFirstItemValidationDoesNotEmitPartialJson(): void
    {
        $resource = fopen('php://memory', 'r');
        $this->assertIsResource($resource);

        $response = new JsonStreamResponse([
            ['id' => 1, 'resource' => $resource],
        ], [
            'root' => 'items',
        ]);

        try {
            ob_start();
            try {
                (string)$response->getBody();
                $this->fail('Expected JsonException was not thrown.');
            } catch (JsonException) {
                $this->assertSame('', ob_get_contents());
            }
        } finally {
            ob_end_clean();
            fclose($resource);
        }
    }

    public function testMidStreamErrorMarker(): void
    {
        $resource = fopen('php://memory', 'r');
        $this->assertIsResource($resource);

        $generator = function () use ($resource) {
            yield ['id' => 1, 'name' => 'Valid'];
            yield ['id' => 2, 'name' => 'Also valid'];
            yield ['id' => 3, 'resource' => $resource]; // Will fail
        };

        // Use flags without JSON_THROW_ON_ERROR for mid-stream handling
        $response = new JsonStreamResponse($generator(), [
            'flags' => JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT,
        ]);
        $body = $this->getStreamedBody($response);

        fclose($resource);

        // Should contain error marker
        $this->assertStringContainsString('__streamError', $body);
        $this->assertStringContainsString('"index":2', $body);
        // Should still be valid JSON
        $decoded = json_decode($body, true);
        $this->assertNotNull($decoded);
    }

    public function testNdjsonMidStreamError(): void
    {
        $resource = fopen('php://memory', 'r');
        $this->assertIsResource($resource);

        $generator = function () use ($resource) {
            yield ['id' => 1];
            yield ['id' => 2, 'resource' => $resource];
        };

        $response = new JsonStreamResponse($generator(), [
            'format' => 'ndjson',
            'flags' => JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT,
        ]);
        $body = $this->getStreamedBody($response);

        fclose($resource);

        $lines = explode("\n", trim($body));
        $this->assertCount(2, $lines);
        $this->assertSame('{"id":1}', $lines[0]);
        $this->assertStringContainsString('__streamError', $lines[1]);
    }

    public function testImmutability(): void
    {
        $data = [['id' => 1]];
        $response = new JsonStreamResponse($data);
        $newResponse = $response->withStreamOptions(['root' => 'items']);

        $this->assertNotSame($response, $newResponse);
        $this->assertNull($response->getStreamOptions()['root'] ?? null);
        $this->assertSame('items', $newResponse->getStreamOptions()['root'] ?? null);

        $this->assertSame('[{"id":1}]', $this->getStreamedBody($response));
        $this->assertSame('{"items":[{"id":1}]}', $this->getStreamedBody($newResponse));
    }

    public function testWithStreamOptionsRebuildsContentTypeForFormatChange(): void
    {
        $response = new JsonStreamResponse([['id' => 1]]);

        $newResponse = $response->withStreamOptions([
            'format' => JsonStreamResponse::FORMAT_NDJSON,
        ]);

        $this->assertSame('application/json; charset=UTF-8', $response->getHeaderLine('Content-Type'));
        $this->assertSame('application/x-ndjson; charset=UTF-8', $newResponse->getHeaderLine('Content-Type'));
        $this->assertSame("{\"id\":1}\n", $this->getStreamedBody($newResponse));
    }

    public function testWithStreamOptionsValidatesFormat(): void
    {
        $response = new JsonStreamResponse([['id' => 1]]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid format `xml`. Supported formats are: json, ndjson');

        $response->withStreamOptions(['format' => 'xml']);
    }

    public function testFlushEveryOptionFlushesInBatches(): void
    {
        $response = new class ([
            ['id' => 1],
            ['id' => 2],
            ['id' => 3],
        ], [
            'flushEvery' => 2,
        ]) extends JsonStreamResponse {
            public int $flushCalls = 0;

            protected function flushOutputBuffers(): void
            {
                $this->flushCalls++;
                parent::flushOutputBuffers();
                // Simulate a successful flush. The test wraps output in ob_start(),
                // so ob_get_level() > 1 and parent::flushOutputBuffers() is a no-op
                // (and intentionally does not reset the counter in that case).
                $this->rowsSinceLastFlush = 0;
            }
        };

        $body = $this->getStreamedBody($response);

        $this->assertSame('[{"id":1},{"id":2},{"id":3}]', $body);
        $this->assertSame(2, $response->flushCalls);
    }

    public function testInvalidFlushEveryThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('`flushEvery` must be an integer greater than or equal to 1');

        new JsonStreamResponse([], ['flushEvery' => 0]);
    }

    public function testCustomJsonFlags(): void
    {
        $data = [['html' => '<script>alert("xss")</script>']];

        // Without hex encoding
        $response = new JsonStreamResponse($data, [
            'flags' => JSON_THROW_ON_ERROR,
        ]);
        $body = $this->getStreamedBody($response);
        $this->assertStringContainsString('<script>', $body);

        // With default flags (hex encoded)
        $response = new JsonStreamResponse($data);
        $body = $this->getStreamedBody($response);
        $this->assertStringNotContainsString('<script>', $body);
        $this->assertStringContainsString('\u003C', $body);
    }

    public function testPrettyPrintInDebugMode(): void
    {
        Configure::write('debug', true);

        $data = [['id' => 1, 'name' => 'Test']];
        $response = new JsonStreamResponse($data);
        $body = $this->getStreamedBody($response);

        // Pretty print adds newlines and indentation
        $this->assertStringContainsString("\n", $body);
        $this->assertStringContainsString('    ', $body);
    }

    public function testNoPrettyPrintInProductionMode(): void
    {
        Configure::write('debug', false);

        $data = [['id' => 1, 'name' => 'Test']];
        $response = new JsonStreamResponse($data);
        $body = $this->getStreamedBody($response);

        // No pretty print - no extra newlines
        $this->assertSame('[{"id":1,"name":"Test"}]', $body);
    }

    public function testContentTypeHeaders(): void
    {
        $data = [['id' => 1]];

        $jsonResponse = new JsonStreamResponse($data);
        $this->assertSame('application/json; charset=UTF-8', $jsonResponse->getHeaderLine('Content-Type'));

        $ndjsonResponse = new JsonStreamResponse($data, ['format' => 'ndjson']);
        $this->assertSame('application/x-ndjson; charset=UTF-8', $ndjsonResponse->getHeaderLine('Content-Type'));
    }

    public function testCompleteIntegration(): void
    {
        // Simulate a realistic use case with envelope, transform, and generator
        $generator = function () {
            for ($i = 1; $i <= 3; $i++) {
                yield (object)[
                    'id' => $i,
                    'title' => "Article {$i}",
                    'secret' => 'should-not-appear',
                ];
            }
        };

        $response = new JsonStreamResponse($generator(), [
            'envelope' => ['meta' => ['total' => 3, 'page' => 1]],
            'dataKey' => 'articles',
            'transform' => fn($item) => [
                'id' => $item->id,
                'title' => $item->title,
            ],
        ]);

        $body = $this->getStreamedBody($response);
        $decoded = json_decode($body, true);

        $this->assertSame(['total' => 3, 'page' => 1], $decoded['meta']);
        $this->assertCount(3, $decoded['articles']);
        $this->assertSame(['id' => 1, 'title' => 'Article 1'], $decoded['articles'][0]);
        $this->assertArrayNotHasKey('secret', $decoded['articles'][0]);
    }

    public function testInvalidFormatThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid format `xml`. Supported formats are: json, ndjson');

        new JsonStreamResponse([], ['format' => 'xml']);
    }

    public function testFormatConstants(): void
    {
        $this->assertSame('json', JsonStreamResponse::FORMAT_JSON);
        $this->assertSame('ndjson', JsonStreamResponse::FORMAT_NDJSON);
    }
}
