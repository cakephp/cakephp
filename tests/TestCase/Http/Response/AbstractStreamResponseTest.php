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
use Cake\Http\Response\AbstractStreamResponse;
use Cake\Log\Engine\ArrayLog;
use Cake\Log\Log;
use Cake\TestSuite\TestCase;
use InvalidArgumentException;
use Throwable;

/**
 * Tests for {@see \Cake\Http\Response\AbstractStreamResponse} that exercise
 * the shared streaming lifecycle independently of any concrete wire format.
 */
class AbstractStreamResponseTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Log::setConfig('streamtest', ['className' => ArrayLog::class]);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Log::drop('streamtest');
    }

    /**
     * Capture body output emitted by the streaming callback.
     */
    protected function getStreamedBody(AbstractStreamResponse $response): string
    {
        ob_start();
        try {
            (string)$response->getBody();
        } catch (Throwable $exception) {
            ob_end_clean();

            throw $exception;
        }

        return ob_get_clean() ?: '';
    }

    public function testEmitsContentTypeWithCharsetAndAccelBufferingHeader(): void
    {
        $response = $this->newFixture([['id' => 1]]);

        $this->assertSame('text/plain; charset=UTF-8', $response->getHeaderLine('Content-Type'));
        $this->assertSame('no', $response->getHeaderLine('X-Accel-Buffering'));
    }

    public function testContentTypeUsesAppEncodingWhenSet(): void
    {
        $original = Configure::read('App.encoding');
        Configure::write('App.encoding', 'ISO-8859-1');
        try {
            $response = $this->newFixture([['id' => 1]]);
            $this->assertSame('text/plain; charset=ISO-8859-1', $response->getHeaderLine('Content-Type'));
        } finally {
            Configure::write('App.encoding', $original);
        }
    }

    public function testStreamDataIsInvokedViaResponseBody(): void
    {
        $response = $this->newFixture([['id' => 1], ['id' => 2]]);

        $this->assertSame('1|2|', $this->getStreamedBody($response));
    }

    public function testInvalidFlushEveryThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('`flushEvery` must be an integer greater than or equal to 1');

        $this->newFixture([], ['flushEvery' => 0]);
    }

    public function testNonIntegerFlushEveryThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('`flushEvery` must be an integer greater than or equal to 1');

        $this->newFixture([], ['flushEvery' => 'every']);
    }

    public function testOutputAndFlushBatchesAccordingToFlushEvery(): void
    {
        $response = $this->newFixture(
            [['id' => 1], ['id' => 2], ['id' => 3], ['id' => 4]],
            ['flushEvery' => 2],
            simulateSuccessfulFlush: true,
        );

        $this->getStreamedBody($response);

        $this->assertSame(2, $response->flushCalls);
    }

    public function testLogStreamErrorIncludesSubclassNameAndIndex(): void
    {
        $response = $this->newFixture([]);
        $response->triggerLogStreamError('boom', 7);

        $messages = Log::engine('streamtest')->read();
        $this->assertCount(1, $messages);
        $this->assertStringContainsString(
            sprintf('%s encoding failed at index 7: boom', $response::class),
            $messages[0],
        );
    }

    public function testGetStreamOptionsReturnsCurrentConfig(): void
    {
        $response = $this->newFixture([], ['flushEvery' => 5]);

        $this->assertSame(['flushEvery' => 5], $response->getStreamOptions());
    }

    public function testWithStreamOptionsReturnsNewInstanceAndPreservesOriginal(): void
    {
        $response = $this->newFixture([['id' => 1]]);
        $new = $response->withStreamOptions(['flushEvery' => 3]);

        $this->assertNotSame($response, $new);
        $this->assertSame(1, $response->getStreamOptions()['flushEvery']);
        $this->assertSame(3, $new->getStreamOptions()['flushEvery']);
    }

    public function testWithStreamOptionsRebuildsStreamCallback(): void
    {
        $response = $this->newFixture([['id' => 1], ['id' => 2]]);
        $new = $response->withStreamOptions(['flushEvery' => 1]);

        $this->assertSame('1|2|', $this->getStreamedBody($new));
    }

    /**
     * Build a minimal concrete subclass used to exercise the abstract behavior.
     *
     * The fixture writes `{id}|` per row using {@see AbstractStreamResponse::outputAndFlush()},
     * exposes a counter for `flushOutputBuffers()` invocations, and exposes a
     * public wrapper around the protected `logStreamError()` so tests can drive
     * each piece directly.
     *
     * @param iterable $data Data to stream.
     * @param array<string, mixed> $options Stream options.
     * @param bool $simulateSuccessfulFlush When true, the fixture resets the
     *   flush counter on every `flushOutputBuffers()` call so threshold-based
     *   batching tests work even when PHPUnit wraps output in ob_start() and
     *   the parent flush would otherwise no-op (and intentionally not reset
     *   the counter).
     * @return object Anonymous instance of AbstractStreamResponse with test hooks.
     */
    protected function newFixture(
        iterable $data,
        array $options = [],
        bool $simulateSuccessfulFlush = false,
    ): object {
        return new class ($data, $options, $simulateSuccessfulFlush) extends AbstractStreamResponse {
            public int $flushCalls = 0;

            protected bool $simulateSuccessfulFlush;

            public function __construct(iterable $data, array $options, bool $simulateSuccessfulFlush)
            {
                $this->simulateSuccessfulFlush = $simulateSuccessfulFlush;
                parent::__construct($data, $options);
            }

            protected function contentType(): string
            {
                return 'text/plain';
            }

            protected function streamData(): void
            {
                foreach ($this->data as $row) {
                    $this->outputAndFlush($row['id'] . '|');
                }
            }

            protected function flushOutputBuffers(): void
            {
                $this->flushCalls++;
                parent::flushOutputBuffers();
                if ($this->simulateSuccessfulFlush) {
                    $this->rowsSinceLastFlush = 0;
                }
            }

            public function triggerLogStreamError(string $message, int $index): void
            {
                $this->logStreamError($message, $index);
            }
        };
    }
}
