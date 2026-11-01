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
 * @since         3.3.5
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Http;

use Cake\Http\CallbackStream;
use Cake\Http\Client\Request;
use Cake\Http\Client\Response as ClientResponse;
use Cake\Http\Client\ResponseStream;
use Cake\Http\Cookie\Cookie;
use Cake\Http\Link\Link;
use Cake\Http\Response;
use Cake\Http\Response\RedirectResponse;
use Cake\Http\ResponseEmitter;
use Cake\Http\Stream;
use Cake\TestSuite\TestCase;
use Mockery;
use Psr\Http\Message\StreamInterface;
use RuntimeException;

require_once __DIR__ . '/server_mocks.php';

/**
 * Response emitter test.
 */
class ResponseEmitterTest extends TestCase
{
    /**
     * @var \Cake\Http\ResponseEmitter
     */
    protected $emitter;

    /**
     * setup
     */
    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['mockedHeadersSent'] = false;
        $GLOBALS['mockedHeaders'] = [];
        $GLOBALS['mockedCookies'] = [];

        $this->emitter = Mockery::mock(ResponseEmitter::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
        $this->emitter->__construct();

        $this->emitter->shouldReceive('setCookie')
            ->zeroOrMoreTimes()
            ->andReturnUsing(function ($cookie) {
                if (is_string($cookie)) {
                    $cookie = Cookie::createFromHeaderString($cookie, ['path' => '']);
                }

                $GLOBALS['mockedCookies'][] = ['name' => $cookie->getName(), 'value' => $cookie->getValue()]
                    + $cookie->getOptions();

                return true;
            });
    }

    /**
     * teardown
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        unset($GLOBALS['mockedHeadersSent']);
    }

    /**
     * Readable non-seekable bodies are emitted in chunks, never cast or rewound.
     */
    public function testEmitLiveBody(): void
    {
        $body = Mockery::mock(StreamInterface::class);
        $body->shouldReceive('isReadable')->once()->andReturn(true);
        $body->shouldReceive('isSeekable')->once()->andReturn(false);
        $body->shouldReceive('eof')->twice()->andReturn(false, true);
        $body->shouldReceive('read')->with(8192)->twice()->andReturn('first ', 'second');
        $body->shouldReceive('close')->once();
        $response = new Response()->withBody($body);

        ob_start();
        ob_start();
        try {
            $this->emitter->emit($response);
            $this->assertSame('first second', ob_get_contents());
        } finally {
            ob_end_clean();
            ob_end_clean();
        }
    }

    /**
     * A failed read also closes the live connection.
     */
    public function testEmitLiveBodyReadFailure(): void
    {
        $body = Mockery::mock(StreamInterface::class);
        $body->shouldReceive('isReadable')->once()->andReturn(true);
        $body->shouldReceive('isSeekable')->once()->andReturn(false);
        $body->shouldReceive('read')->with(8192)->once()->andThrow(RuntimeException::class);
        $body->shouldReceive('close')->once();

        $this->expectException(RuntimeException::class);
        $this->emitter->emit(new Response()->withBody($body));
    }

    /**
     * Test emitting simple responses.
     */
    public function testEmitResponseSimple(): void
    {
        $response = new Response()
            ->withStatus(201)
            ->withHeader('Content-Type', 'text/html')
            ->withHeader('Location', 'http://example.com/cake/1');
        $response->getBody()->write('It worked');

        ob_start();
        $this->emitter->emit($response);
        $out = ob_get_clean();

        $this->assertSame('It worked', $out);
        $expected = [
            'HTTP/1.1 201 Created',
            'Content-Type: text/html',
            'Location: http://example.com/cake/1',
        ];
        $this->assertEquals($expected, $GLOBALS['mockedHeaders']);
    }

    /**
     * Test emitting a no-content response
     */
    public function testEmitNoContentResponse(): void
    {
        $response = new Response()
            ->withHeader('X-testing', 'value')
            ->withStatus(204);
        $response->getBody()->write('It worked');

        ob_start();
        $this->emitter->emit($response);
        $out = ob_get_clean();

        $this->assertSame('', $out);
        $expected = [
            'HTTP/1.1 204 No Content',
            'X-testing: value',
        ];
        $this->assertEquals($expected, $GLOBALS['mockedHeaders']);
    }

    /**
     * Test emitting responses with array cookes
     */
    public function testEmitResponseArrayCookies(): void
    {
        $response = new Response()
            ->withCookie(new Cookie('simple', 'val', null, '/', '', true))
            ->withAddedHeader('Set-Cookie', 'google=not=nice;Path=/accounts; HttpOnly')
            ->withHeader('Content-Type', 'text/plain');
        $response->getBody()->write('ok');

        ob_start();
        $this->emitter->emit($response);
        $out = ob_get_clean();

        $this->assertSame('ok', $out);
        $expected = [
            'HTTP/1.1 200 OK',
            'Content-Type: text/plain',
        ];
        $this->assertEquals($expected, $GLOBALS['mockedHeaders']);
        $expected = [
            [
                'name' => 'simple',
                'value' => 'val',
                'path' => '/',
                'expires' => 0,
                'domain' => '',
                'secure' => true,
                'httponly' => false,
            ],
            [
                'name' => 'google',
                'value' => 'not=nice',
                'path' => '/accounts',
                'expires' => 0,
                'domain' => '',
                'secure' => false,
                'httponly' => true,
            ],
        ];
        $this->assertEquals($expected, $GLOBALS['mockedCookies']);
    }

    /**
     * Test emitting responses with cookies
     */
    public function testEmitResponseCookies(): void
    {
        $response = new Response()
            ->withAddedHeader('Set-Cookie', "simple=val;\tSecure")
            ->withAddedHeader('Set-Cookie', 'people=jim,jack,jonny";";Path=/accounts')
            ->withAddedHeader('Set-Cookie', 'google=not=nice;Path=/accounts; HttpOnly; samesite=Strict')
            ->withAddedHeader('Set-Cookie', 'a=b;  Expires=Wed, 13 Jan 2021 22:23:01 GMT; Domain=www.example.com;')
            ->withAddedHeader('Set-Cookie', 'list%5B%5D=a%20b%20c')
            ->withHeader('Content-Type', 'text/plain');
        $response->getBody()->write('ok');

        ob_start();
        $this->emitter->emit($response);
        $out = ob_get_clean();

        $this->assertSame('ok', $out);
        $expected = [
            'HTTP/1.1 200 OK',
            'Content-Type: text/plain',
        ];
        $this->assertEquals($expected, $GLOBALS['mockedHeaders']);
        $expected = [
            [
                'name' => 'simple',
                'value' => 'val',
                'path' => '',
                'expires' => 0,
                'domain' => '',
                'secure' => true,
                'httponly' => false,
            ],
            [
                'name' => 'people',
                'value' => 'jim,jack,jonny";"',
                'path' => '/accounts',
                'expires' => 0,
                'domain' => '',
                'secure' => false,
                'httponly' => false,
            ],
            [
                'name' => 'google',
                'value' => 'not=nice',
                'path' => '/accounts',
                'expires' => 0,
                'domain' => '',
                'secure' => false,
                'httponly' => true,
                'samesite' => 'Strict',
            ],
            [
                'name' => 'a',
                'value' => 'b',
                'path' => '',
                'expires' => 1610576581,
                'domain' => 'www.example.com',
                'secure' => false,
                'httponly' => false,
            ],
            [
                'name' => 'list[]',
                'value' => 'a b c',
                'path' => '',
                'expires' => 0,
                'domain' => '',
                'secure' => false,
                'httponly' => false,
            ],
        ];
        $this->assertEquals($expected, $GLOBALS['mockedCookies']);
    }

    /**
     * Test emitting responses using callback streams.
     *
     * We use callback streams for closure based responses.
     */
    public function testEmitResponseCallbackStream(): void
    {
        $stream = new CallbackStream(function (): void {
            echo 'It worked';
        });
        $response = new Response()
            ->withStatus(201)
            ->withBody($stream)
            ->withHeader('Content-Type', 'text/plain');

        ob_start();
        $this->emitter->emit($response);
        $out = ob_get_clean();

        $this->assertSame('It worked', $out);
        $expected = [
            'HTTP/1.1 201 Created',
            'Content-Type: text/plain',
        ];
        $this->assertEquals($expected, $GLOBALS['mockedHeaders']);
    }

    /**
     * Test valid body ranges.
     */
    public function testEmitResponseBodyRange(): void
    {
        $response = new Response()
            ->withHeader('Content-Type', 'text/plain')
            ->withHeader('Content-Range', 'bytes 1-4/9');
        $response->getBody()->write('It worked');

        ob_start();
        $this->emitter->emit($response);
        $out = ob_get_clean();

        $this->assertSame('t wo', $out);
        $expected = [
            'HTTP/1.1 200 OK',
            'Content-Type: text/plain',
            'Content-Range: bytes 1-4/9',
        ];
        $this->assertEquals($expected, $GLOBALS['mockedHeaders']);
    }

    /**
     * Test valid body ranges.
     */
    public function testEmitResponseBodyRangeComplete(): void
    {
        $response = new Response()
            ->withHeader('Content-Type', 'text/plain')
            ->withHeader('Content-Range', 'bytes 0-20/9');
        $response->getBody()->write('It worked');

        ob_start();
        $this->emitter->emit($response);
        $out = ob_get_clean();

        $this->assertSame('It worked', $out);
    }

    /**
     * Test out of bounds body ranges.
     */
    public function testEmitResponseBodyRangeOverflow(): void
    {
        $response = new Response()
            ->withHeader('Content-Type', 'text/plain')
            ->withHeader('Content-Range', 'bytes 5-20/9');
        $response->getBody()->write('It worked');

        ob_start();
        $this->emitter->emit($response);
        $out = ob_get_clean();

        $this->assertSame('rked', $out);
    }

    /**
     * Test malformed content-range header
     */
    public function testEmitResponseBodyRangeMalformed(): void
    {
        $response = new Response()
            ->withHeader('Content-Type', 'text/plain')
            ->withHeader('Content-Range', 'bytes 9-ba/a');
        $response->getBody()->write('It worked');

        ob_start();
        $this->emitter->emit($response);
        $out = ob_get_clean();

        $this->assertSame('It worked', $out);
    }

    /**
     * Test callback streams returning content and ranges
     */
    public function testEmitResponseBodyRangeCallbackStream(): void
    {
        $stream = new CallbackStream(function () {
            return 'It worked';
        });
        $response = new Response()
            ->withStatus(201)
            ->withBody($stream)
            ->withHeader('Content-Range', 'bytes 1-4/9')
            ->withHeader('Content-Type', 'text/plain');

        ob_start();
        $this->emitter->emit($response);
        $out = ob_get_clean();

        $this->assertSame('t wo', $out);
        $expected = [
            'HTTP/1.1 201 Created',
            'Content-Range: bytes 1-4/9',
            'Content-Type: text/plain',
        ];
        $this->assertEquals($expected, $GLOBALS['mockedHeaders']);
    }

    /**
     * Test emitting Link headers from PSR-13 links.
     */
    public function testEmitResponseLinks(): void
    {
        $response = new Response()
            ->withHeader('Content-Type', 'text/html')
            ->withLink(new Link('/api/users', 'self'))
            ->withLink(new Link('/api/users?page=2', 'next'));
        $response->getBody()->write('ok');

        ob_start();
        $this->emitter->emit($response);
        ob_get_clean();

        $expected = [
            'HTTP/1.1 200 OK',
            'Content-Type: text/html',
            'Link: </api/users>; rel="self"',
            'Link: </api/users?page=2>; rel="next"',
        ];
        $this->assertEquals($expected, $GLOBALS['mockedHeaders']);
    }

    /**
     * Test emitting Link headers with attributes.
     */
    public function testEmitResponseLinksWithAttributes(): void
    {
        $link = new Link('/css/app.css')
            ->withRel('preload')
            ->withAttribute('as', 'style')
            ->withAttribute('crossorigin', 'anonymous');
        $response = new Response()
            ->withLink($link);
        $response->getBody()->write('ok');

        ob_start();
        $this->emitter->emit($response);
        ob_get_clean();

        $expected = [
            'HTTP/1.1 200 OK',
            'Content-Type: text/html; charset=UTF-8',
            'Link: </css/app.css>; rel="preload"; as="style"; crossorigin="anonymous"',
        ];
        $this->assertEquals($expected, $GLOBALS['mockedHeaders']);
    }

    /**
     * Test emitting Link headers with boolean attributes.
     */
    public function testEmitResponseLinksWithBooleanAttributes(): void
    {
        $link = new Link('/script.js')
            ->withRel('preload')
            ->withAttribute('as', 'script')
            ->withAttribute('nopush', true)
            ->withAttribute('disabled', false);
        $response = new Response()
            ->withLink($link);
        $response->getBody()->write('ok');

        ob_start();
        $this->emitter->emit($response);
        ob_get_clean();

        $expected = [
            'HTTP/1.1 200 OK',
            'Content-Type: text/html; charset=UTF-8',
            'Link: </script.js>; rel="preload"; as="script"; nopush',
        ];
        $this->assertEquals($expected, $GLOBALS['mockedHeaders']);
    }

    /**
     * Test emitting Link headers with array attributes.
     */
    public function testEmitResponseLinksWithArrayAttributes(): void
    {
        $link = new Link('/api/resource')
            ->withRel('self')
            ->withAttribute('hreflang', ['en', 'de']);
        $response = new Response()
            ->withLink($link);
        $response->getBody()->write('ok');

        ob_start();
        $this->emitter->emit($response);
        ob_get_clean();

        $expected = [
            'HTTP/1.1 200 OK',
            'Content-Type: text/html; charset=UTF-8',
            'Link: </api/resource>; rel="self"; hreflang="en"; hreflang="de"',
        ];
        $this->assertEquals($expected, $GLOBALS['mockedHeaders']);
    }

    /**
     * Test emitting Link headers escapes special characters.
     */
    public function testEmitResponseLinksEscapesValues(): void
    {
        $link = new Link('/api/users')
            ->withRel('self')
            ->withAttribute('title', 'A "quoted" value');
        $response = new Response()
            ->withLink($link);
        $response->getBody()->write('ok');

        ob_start();
        $this->emitter->emit($response);
        ob_get_clean();

        $expected = [
            'HTTP/1.1 200 OK',
            'Content-Type: text/html; charset=UTF-8',
            'Link: </api/users>; rel="self"; title="A \"quoted\" value"',
        ];
        $this->assertEquals($expected, $GLOBALS['mockedHeaders']);
    }

    /**
     * Test no Link headers emitted when no links present.
     */
    public function testEmitResponseNoLinks(): void
    {
        $response = new Response()
            ->withHeader('Content-Type', 'text/html');
        $response->getBody()->write('ok');

        ob_start();
        $this->emitter->emit($response);
        ob_get_clean();

        $expected = [
            'HTTP/1.1 200 OK',
            'Content-Type: text/html',
        ];
        $this->assertEquals($expected, $GLOBALS['mockedHeaders']);
    }

    public function testEmptyReadBeforeEofDoesNotSpin(): void
    {
        $body = Mockery::mock(StreamInterface::class);
        $body->shouldReceive('isReadable')->andReturn(true);
        $body->shouldReceive('isSeekable')->andReturn(false);
        $body->shouldReceive('eof')->once()->andReturn(false);
        $body->shouldReceive('read')->once()->andReturn('');
        $body->shouldReceive('close')->once();
        $response = new Response()->withBody($body);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('use a blocking stream');
        $this->emitter->emitBody($response);
    }

    public function testRedirectCookieHeadersAreEmittedOnceWithOriginalScope(): void
    {
        $response = new RedirectResponse('/', 302, ['Set-Cookie' => 'token=value']);
        $this->emitter->emitHeaders($response);
        $this->assertCount(1, $GLOBALS['mockedCookies']);
        $this->assertSame('', $GLOBALS['mockedCookies'][0]['path']);
    }

    public function testAlreadyRangedUpstreamBodyIsForwardedWithoutSlicing(): void
    {
        $resource = fopen('php://temp', 'w+');
        fwrite($resource, str_repeat('R', 100));
        rewind($resource);
        $body = new ResponseStream($resource, new Request('https://example.com'));
        $response = new Response()->withStatus(206)
            ->withHeader('Content-Range', 'bytes 100-199/1000')
            ->withHeader('Content-Length', '100')->withBody($body);
        ob_start();
        ob_start();
        try {
            $this->emitter->emit($response);
            $this->assertSame(str_repeat('R', 100), ob_get_contents());
        } finally {
            ob_end_clean();
            ob_end_clean();
        }
    }

    public function testNestedBuffersCanBeDrainedForLiveEmission(): void
    {
        $process = proc_open([PHP_BINARY, TEST_APP . 'TestApp/Http/live_emitter.php'], [
            0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w'],
        ], $pipes);
        $this->assertIsResource($process);
        fclose($pipes[0]);
        $output = stream_get_contents($pipes[1]);
        $error = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $this->assertSame(0, proc_close($process), $error . $output);
        $this->assertSame('first second', $output);
    }

    public function testBufferedClientPartialResponseIsNotSlicedAgain(): void
    {
        $response = new ClientResponse([
            'HTTP/1.1 206 Partial Content', 'Content-Range: bytes 100-199/1000',
        ], str_repeat('R', 100));
        ob_start();
        try {
            $this->emitter->emit($response);
            $this->assertSame(str_repeat('R', 100), ob_get_contents());
        } finally {
            ob_end_clean();
        }
    }

    public function testPartialBodyCopiedIntoServerResponseIsNotSlicedAgain(): void
    {
        $response = new Response()->withStatus(206)
            ->withHeader('Content-Range', 'bytes 100-199/1000')
            ->withBody(new Stream('data://text/plain,' . str_repeat('R', 100)));
        ob_start();
        try {
            $this->emitter->emit($response);
            $this->assertSame(str_repeat('R', 100), ob_get_contents());
        } finally {
            ob_end_clean();
        }
    }

    public function testExplicitRangeStateHandlesUnknownSizeFullBody(): void
    {
        $stream = new class ('data://text/plain,0123456789') extends Stream {
            public function getSize(): ?int
            {
                return null;
            }
        };
        $response = new Response()->withHeader('Content-Range', 'bytes 2-4/10')->withBody($stream);
        ob_start();
        try {
            $this->emitter->emit($response, bodyIsRange: false);
            $this->assertSame('234', ob_get_contents());
        } finally {
            ob_end_clean();
        }
    }
}
