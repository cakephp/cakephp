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
use Cake\Http\Cookie\Cookie;
use Cake\Http\Response;
use Cake\Http\ResponseEmitter;
use Cake\TestSuite\TestCase;

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
    public function setUp(): void
    {
        parent::setUp();

        $GLOBALS['mockedHeadersSent'] = false;
        $GLOBALS['mockedHeaders'] = [];

        $this->emitter = $this->getMockBuilder(ResponseEmitter::class)
            ->onlyMethods(['setCookie'])
            ->getMock();

        $this->emitter->expects($this->any())
            ->method('setCookie')
            ->will($this->returnCallback(function ($cookie) {
                if (is_string($cookie)) {
                    $cookie = Cookie::createFromHeaderString($cookie, ['path' => '']);
                }

                $GLOBALS['mockedCookies'][] = ['name' => $cookie->getName(), 'value' => $cookie->getValue()]
                    + $cookie->getOptions();

                return true;
            }));
    }

    /**
     * teardown
     */
    public function tearDown(): void
    {
        parent::tearDown();
        unset($GLOBALS['mockedHeadersSent']);
    }

    /**
     * Test emitting simple responses.
     */
    public function testEmitResponseSimple(): void
    {
        $response = (new Response())
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
        $response = (new Response())
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
        $response = (new Response())
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
        $response = (new Response())
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
        $response = (new Response())
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
        $response = (new Response())
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
        $response = (new Response())
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
        $response = (new Response())
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
        $response = (new Response())
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
        $response = (new Response())
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
}
