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
 * @since         2.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Http;

include_once CORE_TEST_CASES . DS . 'Http' . DS . 'server_mocks.php';

use Cake\Core\Configure;
use Cake\Http\Cookie\Cookie;
use Cake\Http\Cookie\CookieCollection;
use Cake\Http\CorsBuilder;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\I18n\FrozenTime;
use Cake\TestSuite\TestCase;
use DateTime as NativeDateTime;
use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use Laminas\Diactoros\Stream;

/**
 * ResponseTest
 */
class ResponseTest extends TestCase
{
    /**
     * SERVER variable backup.
     *
     * @var array
     */
    protected $server = [];

    /**
     * setup
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->server = $_SERVER;
    }

    /**
     * teardown
     */
    public function tearDown(): void
    {
        parent::tearDown();
        $_SERVER = $this->server;
        unset($GLOBALS['mockedHeadersSent']);
    }

    /**
     * Tests the request object constructor
     */
    public function testConstruct(): void
    {
        $response = new Response();
        $this->assertSame('', (string)$response->getBody());
        $this->assertSame('UTF-8', $response->getCharset());
        $this->assertSame('text/html', $response->getType());
        $this->assertSame('text/html; charset=UTF-8', $response->getHeaderLine('Content-Type'));
        $this->assertSame(200, $response->getStatusCode());

        $options = [
            'body' => 'This is the body',
            'charset' => 'my-custom-charset',
            'type' => 'mp3',
            'status' => 203,
        ];
        $response = new Response($options);
        $this->assertSame('This is the body', (string)$response->getBody());
        $this->assertSame('my-custom-charset', $response->getCharset());
        $this->assertSame('audio/mpeg', $response->getType());
        $this->assertSame('audio/mpeg', $response->getHeaderLine('Content-Type'));
        $this->assertSame(203, $response->getStatusCode());
    }

    /**
     * Tests the getCharset/withCharset methods
     */
    public function testWithCharset(): void
    {
        $response = new Response();
        $this->assertSame('text/html; charset=UTF-8', $response->getHeaderLine('Content-Type'));

        $new = $response->withCharset('iso-8859-1');
        $this->assertStringNotContainsString('iso', $response->getHeaderLine('Content-Type'), 'Old instance not changed');
        $this->assertSame('iso-8859-1', $new->getCharset());

        $this->assertSame('text/html; charset=iso-8859-1', $new->getHeaderLine('Content-Type'));
    }

    /**
     * Tests the getType method
     */
    public function testGetType(): void
    {
        $response = new Response();
        $this->assertSame('text/html', $response->getType());

        $this->assertSame(
            'application/pdf',
            $response->withType('pdf')->getType()
        );
        $this->assertSame(
            'custom/stuff',
            $response->withType('custom/stuff')->getType()
        );
        $this->assertSame(
            'application/json',
            $response->withType('json')->getType()
        );
    }

    public function testSetTypeMap(): void
    {
        $response = new Response();
        $response->setTypeMap('ical', 'text/calendar');

        $response = $response->withType('ical')->getType();
        $this->assertSame('text/calendar', $response);
    }

    public function testSetTypeMapAsArray(): void
    {
        $response = new Response();
        $response->setTypeMap('ical', ['text/calendar']);

        $response = $response->withType('ical')->getType();
        $this->assertSame('text/calendar', $response);
    }

    /**
     * Tests the withType method
     */
    public function testWithTypeAlias(): void
    {
        $response = new Response();
        $this->assertSame(
            'text/html; charset=UTF-8',
            $response->getHeaderLine('Content-Type'),
            'Default content-type should match'
        );

        $new = $response->withType('pdf');
        $this->assertNotSame($new, $response, 'Should be a new instance');

        $this->assertSame(
            'text/html; charset=UTF-8',
            $response->getHeaderLine('Content-Type'),
            'Original object should not be modified'
        );
        $this->assertSame('application/pdf', $new->getHeaderLine('Content-Type'));

        $json = $new->withType('json');
        $this->assertSame('application/json', $json->getHeaderLine('Content-Type'));
        $this->assertSame('application/json', $json->getType());
    }

    /**
     * test withType() and full mime-types
     */
    public function withTypeFull(): void
    {
        $response = new Response();
        $this->assertSame(
            'application/json',
            $response->withType('application/json')->getHeaderLine('Content-Type'),
            'Should not add charset to explicit type'
        );
        $this->assertSame(
            'custom/stuff',
            $response->withType('custom/stuff')->getHeaderLine('Content-Type'),
            'Should allow arbitrary types'
        );
        $this->assertSame(
            'text/html; charset=UTF-8',
            $response->withType('text/html; charset=UTF-8')->getHeaderLine('Content-Type'),
            'Should allow charset types'
        );
    }

    /**
     * Test that an invalid type raises an exception
     */
    public function testWithTypeInvalidType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('"beans" is an invalid content type');
        $response = new Response();
        $response->withType('beans');
    }

    /**
     * Data provider for content type tests.
     *
     * @return array
     */
    public static function charsetTypeProvider(): array
    {
        return [
            ['mp3', 'audio/mpeg'],
            ['js', 'application/javascript; charset=UTF-8'],
            ['xml', 'application/xml; charset=UTF-8'],
            ['txt', 'text/plain; charset=UTF-8'],
        ];
    }

    /**
     * Test that setting certain status codes clears the status code.
     */
    public function testWithStatusClearsContentType(): void
    {
        $response = new Response();
        $new = $response->withType('pdf')
            ->withStatus(204);
        $this->assertFalse($new->hasHeader('Content-Type'));
        $this->assertSame('', $new->getType());
        $this->assertSame(204, $new->getStatusCode(), 'Status code should clear content-type');

        $response = new Response();
        $new = $response->withStatus(304)
            ->withType('pdf');
        $this->assertSame('', $new->getType());
        $this->assertFalse(
            $new->hasHeader('Content-Type'),
            'Type should not be retained because of status code.'
        );

        $response = new Response();
        $new = $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(204);
        $this->assertFalse($new->hasHeader('Content-Type'), 'Should clear direct header');
        $this->assertSame('', $new->getType());
    }

    /**
     * Test that setting status codes doesn't overwrite content-type
     */
    public function testWithStatusDoesNotChangeContentType(): void
    {
        $response = new Response();
        $new = $response->withHeader('Content-Type', 'application/json')
            ->withStatus(403);
        $this->assertSame('application/json', $new->getHeaderLine('Content-Type'));
        $this->assertSame(403, $new->getStatusCode());

        $response = new Response();
        $new = $response->withStatus(403)
            ->withHeader('Content-Type', 'application/json');
        $this->assertSame('application/json', $new->getHeaderLine('Content-Type'));
        $this->assertSame(403, $new->getStatusCode());
        $this->assertSame('application/json', $new->getType());
    }

    /**
     * Tests the withDisabledCache method
     */
    public function testWithDisabledCache(): void
    {
        $response = new Response();
        $expected = [
            'Expires' => ['Mon, 26 Jul 1997 05:00:00 GMT'],
            'Last-Modified' => [gmdate(DATE_RFC7231)],
            'Cache-Control' => ['no-store, no-cache, must-revalidate, post-check=0, pre-check=0'],
            'Content-Type' => ['text/html; charset=UTF-8'],
        ];
        $new = $response->withDisabledCache();
        $this->assertFalse($response->hasHeader('Expires'), 'Old instance not mutated.');

        $this->assertEquals($expected, $new->getHeaders());
    }

    /**
     * Tests the withCache method
     */
    public function testWithCache(): void
    {
        $response = new Response();
        $since = $time = time();

        $new = $response->withCache($since, $time);
        $this->assertFalse($response->hasHeader('Date'));
        $this->assertFalse($response->hasHeader('Last-Modified'));

        $this->assertSame(gmdate(DATE_RFC7231, $since), $new->getHeaderLine('Date'));
        $this->assertSame(gmdate(DATE_RFC7231, $since), $new->getHeaderLine('Last-Modified'));
        $this->assertSame(gmdate(DATE_RFC7231, $time), $new->getHeaderLine('Expires'));
        $this->assertSame('public, max-age=0', $new->getHeaderLine('Cache-Control'));
    }

    /**
     * Tests the compress method
     */
    public function testCompress(): void
    {
        $response = new Response();
        if (ini_get('zlib.output_compression') === '1' || !extension_loaded('zlib')) {
            $this->assertFalse($response->compress());
            $this->markTestSkipped('Is not possible to test output compression');
        }

        $_SERVER['HTTP_ACCEPT_ENCODING'] = '';
        $result = $response->compress();
        $this->assertFalse($result);

        $_SERVER['HTTP_ACCEPT_ENCODING'] = 'gzip';
        $result = $response->compress();
        $this->assertTrue($result);
        $this->assertContains('ob_gzhandler', ob_list_handlers());

        ob_get_clean();
    }

    /**
     * Tests the withDownload method
     */
    public function testWithDownload(): void
    {
        $response = new Response();
        $new = $response->withDownload('myfile.mp3');
        $this->assertFalse($response->hasHeader('Content-Disposition'), 'No mutation');

        $expected = 'attachment; filename="myfile.mp3"';
        $this->assertSame($expected, $new->getHeaderLine('Content-Disposition'));
    }

    /**
     * Tests the mapType method
     */
    public function testMapType(): void
    {
        $response = new Response();
        $this->assertSame('wav', $response->mapType('audio/x-wav'));
        $this->assertSame('pdf', $response->mapType('application/pdf'));
        $this->assertSame('xml', $response->mapType('text/xml'));
        $this->assertSame('html', $response->mapType('*/*'));
        $this->assertSame('csv', $response->mapType('application/vnd.ms-excel'));
        $expected = ['json', 'xhtml', 'css'];
        $result = $response->mapType(['application/json', 'application/xhtml+xml', 'text/css']);
        $this->assertEquals($expected, $result);
    }

    /**
     * Tests the outputCompressed method
     */
    public function testOutputCompressed(): void
    {
        $response = new Response();

        $_SERVER['HTTP_ACCEPT_ENCODING'] = 'gzip';
        $result = $response->outputCompressed();
        $this->assertFalse($result);

        $_SERVER['HTTP_ACCEPT_ENCODING'] = '';
        $result = $response->outputCompressed();
        $this->assertFalse($result);

        if (!extension_loaded('zlib')) {
            $this->markTestSkipped('Skipping further tests for outputCompressed as zlib extension is not loaded');
        }

        if (ini_get('zlib.output_compression') !== '1') {
            ob_start('ob_gzhandler');
        }
        $_SERVER['HTTP_ACCEPT_ENCODING'] = 'gzip';
        $result = $response->outputCompressed();
        $this->assertTrue($result);

        $_SERVER['HTTP_ACCEPT_ENCODING'] = '';
        $result = $response->outputCompressed();
        $this->assertFalse($result);
        if (ini_get('zlib.output_compression') !== '1') {
            ob_get_clean();
        }
    }

    /**
     * Tests settings the content length
     */
    public function testWithLength(): void
    {
        $response = new Response();
        $this->assertFalse($response->hasHeader('Content-Length'));

        $new = $response->withLength(100);
        $this->assertFalse($response->hasHeader('Content-Length'), 'Old instance not modified');

        $this->assertSame('100', $new->getHeaderLine('Content-Length'));
    }

    /**
     * Tests settings the link
     */
    public function testWithAddedLink(): void
    {
        $response = new Response();
        $this->assertFalse($response->hasHeader('Link'));

        $new = $response->withAddedLink('http://example.com', ['rel' => 'prev']);
        $this->assertFalse($response->hasHeader('Link'), 'Old instance not modified');
        $this->assertSame('<http://example.com>; rel="prev"', $new->getHeaderLine('Link'));

        $new = $response->withAddedLink('http://example.com');
        $this->assertSame('<http://example.com>', $new->getHeaderLine('Link'));

        $new = $response->withAddedLink('http://example.com?p=1', ['rel' => 'prev'])
            ->withAddedLink('http://example.com?p=2', ['rel' => 'next', 'foo' => 'bar']);
        $this->assertSame('<http://example.com?p=1>; rel="prev",<http://example.com?p=2>; rel="next"; foo="bar"', $new->getHeaderLine('Link'));
    }

    /**
     * Tests the withExpires method
     */
    public function testWithExpires(): void
    {
        $response = new Response();
        $now = new NativeDateTime('now', new DateTimeZone('America/Los_Angeles'));

        $new = $response->withExpires($now);
        $this->assertFalse($response->hasHeader('Expires'));

        $now->setTimeZone(new DateTimeZone('UTC'));
        $this->assertSame($now->format(DATE_RFC7231), $new->getHeaderLine('Expires'));

        $now = time();
        $new = $response->withExpires($now);
        $this->assertSame(gmdate(DATE_RFC7231), $new->getHeaderLine('Expires'));

        $time = new NativeDateTime('+1 day', new DateTimeZone('UTC'));
        $new = $response->withExpires('+1 day');
        $this->assertSame($time->format(DATE_RFC7231), $new->getHeaderLine('Expires'));
    }

    /**
     * Tests the withModified method
     */
    public function testWithModified(): void
    {
        $response = new Response();
        $now = new NativeDateTime('now', new DateTimeZone('America/Los_Angeles'));
        $new = $response->withModified($now);
        $this->assertFalse($response->hasHeader('Last-Modified'));

        $now->setTimeZone(new DateTimeZone('UTC'));
        $this->assertSame($now->format(DATE_RFC7231), $new->getHeaderLine('Last-Modified'));

        $now = time();
        $new = $response->withModified($now);
        $this->assertSame(gmdate(DATE_RFC7231, $now), $new->getHeaderLine('Last-Modified'));

        $now = new DateTimeImmutable();
        $new = $response->withModified($now);
        $this->assertSame(gmdate(DATE_RFC7231, $now->getTimestamp()), $new->getHeaderLine('Last-Modified'));

        $time = new NativeDateTime('+1 day', new DateTimeZone('UTC'));
        $new = $response->withModified('+1 day');
        $this->assertSame($time->format(DATE_RFC7231), $new->getHeaderLine('Last-Modified'));
    }

    /**
     * Tests withSharable()
     */
    public function testWithSharable(): void
    {
        $response = new Response();
        $new = $response->withSharable(true);
        $this->assertFalse($response->hasHeader('Cache-Control'), 'old instance unchanged');
        $this->assertSame('public', $new->getHeaderLine('Cache-Control'));

        $new = $response->withSharable(false);
        $this->assertSame('private', $new->getHeaderLine('Cache-Control'));

        $new = $response->withSharable(true, 3600);
        $this->assertSame('public, max-age=3600', $new->getHeaderLine('Cache-Control'));

        $new = $response->withSharable(false, 3600);
        $this->assertSame('private, max-age=3600', $new->getHeaderLine('Cache-Control'));
    }

    /**
     * Tests withMaxAge()
     */
    public function testWithMaxAge(): void
    {
        $response = new Response();
        $this->assertFalse($response->hasHeader('Cache-Control'));

        $new = $response->withMaxAge(3600);
        $this->assertSame('max-age=3600', $new->getHeaderLine('Cache-Control'));

        $new = $response->withMaxAge(3600)
            ->withSharable(false);
        $this->assertSame('max-age=3600, private', $new->getHeaderLine('Cache-Control'));
    }

    /**
     * Tests setting of s-maxage Cache-Control directive
     */
    public function testWithSharedMaxAge(): void
    {
        $response = new Response();
        $new = $response->withSharedMaxAge(3600);

        $this->assertFalse($response->hasHeader('Cache-Control'));
        $this->assertSame('s-maxage=3600', $new->getHeaderLine('Cache-Control'));

        $new = $response->withSharedMaxAge(3600)->withSharable(true);
        $this->assertSame('s-maxage=3600, public', $new->getHeaderLine('Cache-Control'));
    }

    /**
     * Tests setting of must-revalidate Cache-Control directive
     */
    public function testWithMustRevalidate(): void
    {
        $response = new Response();
        $this->assertFalse($response->hasHeader('Cache-Control'));

        $new = $response->withMustRevalidate(true);
        $this->assertFalse($response->hasHeader('Cache-Control'));
        $this->assertSame('must-revalidate', $new->getHeaderLine('Cache-Control'));

        $new = $new->withMustRevalidate(false);
        $this->assertEmpty($new->getHeaderLine('Cache-Control'));
    }

    /**
     * Tests withVary()
     */
    public function testWithVary(): void
    {
        $response = new Response();
        $new = $response->withVary('Accept-encoding');

        $this->assertFalse($response->hasHeader('Vary'));
        $this->assertSame('Accept-encoding', $new->getHeaderLine('Vary'));

        $new = $response->withVary(['Accept-encoding', 'Accept-language']);
        $this->assertFalse($response->hasHeader('Vary'));
        $this->assertSame('Accept-encoding,Accept-language', $new->getHeaderLine('Vary'));
    }

    /**
     * Tests withEtag()
     */
    public function testWithEtag(): void
    {
        $response = new Response();
        $new = $response->withEtag('something');

        $this->assertFalse($response->hasHeader('Etag'));
        $this->assertSame('"something"', $new->getHeaderLine('Etag'));

        $new = $response->withEtag('something', true);
        $this->assertSame('W/"something"', $new->getHeaderLine('Etag'));
    }

    /**
     * Tests that the response is able to be marked as not modified
     */
    public function testNotModified(): void
    {
        $response = new Response();
        $response = $response->withStringBody('something')
            ->withStatus(200)
            ->withLength(100)
            ->withModified('now');

        $this->deprecated(function () use ($response) {
            $response->notModified();
        });
        $this->assertFalse($response->hasHeader('Content-Length'));
        $this->assertFalse($response->hasHeader('Modified'));
        $this->assertEmpty((string)$response->getBody());
        $this->assertSame(304, $response->getStatusCode());
    }

    /**
     * Tests withNotModified()
     */
    public function testWithNotModified(): void
    {
        $response = new Response(['body' => 'something']);
        $response = $response->withLength(100)
            ->withStatus(200)
            ->withHeader('Last-Modified', 'value')
            ->withHeader('Content-Language', 'en-EN')
            ->withHeader('X-things', 'things')
            ->withType('application/json');

        $new = $response->withNotModified();
        $this->assertTrue($response->hasHeader('Content-Language'), 'old instance not changed');
        $this->assertTrue($response->hasHeader('Content-Length'), 'old instance not changed');

        $this->assertFalse($new->hasHeader('Content-Type'));
        $this->assertFalse($new->hasHeader('Content-Length'));
        $this->assertFalse($new->hasHeader('Content-Language'));
        $this->assertFalse($new->hasHeader('Last-Modified'));

        $this->assertSame('things', $new->getHeaderLine('X-things'), 'Other headers are retained');
        $this->assertSame(304, $new->getStatusCode());
        $this->assertSame('', $new->getBody()->getContents());
    }

    /**
     * Test checkNotModified method
     */
    public function testCheckNotModifiedByEtagStar(): void
    {
        $request = new ServerRequest();
        $request = $request->withHeader('If-None-Match', '*');

        $response = new Response();
        $response = $response->withEtag('something')
            ->withHeader('Content-Length', 99);
        $this->assertTrue($response->isNotModified($request));

        $this->deprecated(function () use ($response, $request) {
            $this->assertTrue($response->checkNotModified($request));
            $this->assertFalse($response->hasHeader('Content-Type'), 'etags match, should be unmodified');
        });
    }

    /**
     * Test checkNotModified method
     */
    public function testCheckNotModifiedByEtagExact(): void
    {
        $request = new ServerRequest();
        $request = $request->withHeader('If-None-Match', 'W/"something", "other"');

        $response = new Response();
        $response = $response->withEtag('something', true)
            ->withHeader('Content-Length', 99);
        $this->assertTrue($response->isNotModified($request));

        $this->deprecated(function () use ($request, $response) {
            $this->assertTrue($response->checkNotModified($request));
            $this->assertFalse($response->hasHeader('Content-Type'), 'etags match, should be unmodified');
        });
    }

    /**
     * Test checkNotModified method
     */
    public function testCheckNotModifiedByEtagAndTime(): void
    {
        $request = new ServerRequest();
        $request = $request->withHeader('If-Modified-Since', '2012-01-01 00:00:00')
            ->withHeader('If-None-Match', 'W/"something", "other"');

        $response = new Response();
        $response = $response->withModified('2012-01-01 00:00:00')
            ->withEtag('something', true)
            ->withHeader('Content-Length', 99);
        $this->assertTrue($response->isNotModified($request));

        $this->deprecated(function () use ($request, $response) {
            $this->assertTrue($response->checkNotModified($request));
            $this->assertFalse($response->hasHeader('Content-Length'), 'etags match, should be unmodified');
        });
    }

    /**
     * Test checkNotModified method
     */
    public function testCheckNotModifiedByEtagAndTimeMismatch(): void
    {
        $request = new ServerRequest();
        $request = $request->withHeader('If-Modified-Since', '2012-01-01 00:00:00')
            ->withHeader('If-None-Match', 'W/"something", "other"');

        $response = new Response();
        $response = $response->withModified('2012-01-01 00:00:01')
            ->withEtag('something', true)
            ->withHeader('Content-Length', 99);
        $this->assertFalse($response->isNotModified($request));

        $this->deprecated(function () use ($request, $response) {
            $this->assertFalse($response->checkNotModified($request));
            $this->assertTrue($response->hasHeader('Content-Length'), 'timestamp in response is newer');
        });
    }

    /**
     * Test checkNotModified method
     */
    public function testCheckNotModifiedByEtagMismatch(): void
    {
        $request = new ServerRequest();
        $request = $request->withHeader('If-Modified-Since', '2012-01-01 00:00:00')
            ->withHeader('If-None-Match', 'W/"something-else", "other"');

        $response = new Response();
        $response = $response->withModified('2012-01-01 00:00:00')
            ->withEtag('something', true)
            ->withHeader('Content-Length', 99);
        $this->assertFalse($response->isNotModified($request));
        $this->deprecated(function () use ($request, $response) {
            $this->assertFalse($response->checkNotModified($request));
            $this->assertTrue($response->hasHeader('Content-Length'), 'etags do not match');
        });
    }

    /**
     * Test checkNotModified method
     */
    public function testCheckNotModifiedByTime(): void
    {
        $request = new ServerRequest();
        $request = $request->withHeader('If-Modified-Since', '2012-01-01 00:00:00');

        $response = new Response();
        $response = $response->withModified('2012-01-01 00:00:00')
            ->withHeader('Content-Length', 99);
        $this->assertTrue($response->isNotModified($request));

        $this->deprecated(function () use ($request, $response) {
            $this->assertTrue($response->checkNotModified($request));
            $this->assertFalse($response->hasHeader('Content-Length'), 'modified time matches');
        });
    }

    /**
     * Test checkNotModified method
     */
    public function testCheckNotModifiedNoHints(): void
    {
        $request = new ServerRequest();
        $request = $request->withHeader('If-None-Match', 'W/"something", "other"')
            ->withHeader('If-Modified-Since', '2012-01-01 00:00:00');
        $response = new Response();
        $this->assertFalse($response->isNotModified($request));

        $this->deprecated(function () use ($request, $response) {
            $this->assertFalse($response->checkNotModified($request));
            $this->assertSame(200, $response->getStatusCode());
        });
    }

    /**
     * Test setting cookies with no value
     */
    public function testWithCookieEmpty(): void
    {
        $response = new Response();
        $new = $response->withCookie(new Cookie('testing'));
        $this->assertNull($response->getCookie('testing'), 'withCookie does not mutate');

        $expected = [
            'name' => 'testing',
            'value' => '',
            'expires' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => false,
            'httponly' => false,
        ];
        $result = $new->getCookie('testing');
        $this->assertEquals($expected, $result);
    }

    /**
     * Test setting cookies with scalar values
     */
    public function testWithCookieScalar(): void
    {
        $response = new Response();
        $new = $response->withCookie(new Cookie('testing', 'abc123'));
        $this->assertNull($response->getCookie('testing'), 'withCookie does not mutate');
        $this->assertSame('abc123', $new->getCookie('testing')['value']);

        $new = $response->withCookie(new Cookie('testing', 99));
        $this->assertSame(99, $new->getCookie('testing')['value']);

        $new = $response->withCookie(new Cookie('testing', false));
        $this->assertFalse($new->getCookie('testing')['value']);

        $new = $response->withCookie(new Cookie('testing', true));
        $this->assertTrue($new->getCookie('testing')['value']);
    }

    /**
     * Test withCookie() and duplicate data
     *
     * @throws \Exception
     */
    public function testWithDuplicateCookie(): void
    {
        $expiry = new DateTimeImmutable('+24 hours');

        $response = new Response();
        $cookie = new Cookie(
            'testing',
            '[a,b,c]',
            $expiry,
            '/test',
            '',
            true
        );

        $new = $response->withCookie($cookie);
        $this->assertNull($response->getCookie('testing'), 'withCookie does not mutate');

        $expected = [
            'name' => 'testing',
            'value' => '[a,b,c]',
            'expires' => $expiry,
            'path' => '/test',
            'domain' => '',
            'secure' => true,
            'httponly' => false,
        ];

        // Match the date time formatting to Response::convertCookieToArray
        $expected['expires'] = $expiry->format('U');

        $this->assertEquals($expected, $new->getCookie('testing'));
    }

    /**
     * Test withCookie() and a cookie instance
     */
    public function testWithCookieObject(): void
    {
        $response = new Response();
        $cookie = new Cookie('yay', 'a value');
        $new = $response->withCookie($cookie);
        $this->assertNull($response->getCookie('yay'), 'withCookie does not mutate');

        $this->assertNotEmpty($new->getCookie('yay'));
        $this->assertSame($cookie, $new->getCookieCollection()->get('yay'));
    }

    public function testWithExpiredCookieScalar(): void
    {
        $response = new Response();
        $response = $response->withCookie(new Cookie('testing', 'abc123'));
        $this->assertSame('abc123', $response->getCookie('testing')['value']);

        $new = $response->withExpiredCookie(new Cookie('testing'));

        $this->assertSame(0, $response->getCookie('testing')['expires']);
        $this->assertLessThan(FrozenTime::createFromTimestamp(1), (string)$new->getCookie('testing')['expires']);
    }

    /**
     * @throws \Exception If DateImmutable emits an error.
     */
    public function testWithExpiredCookieOptions(): void
    {
        $options = [
            'name' => 'testing',
            'value' => 'abc123',
            'options' => [
                'domain' => 'cakephp.org',
                'path' => '/custompath/',
                'secure' => true,
                'httponly' => true,
                'expires' => new DateTimeImmutable('+14 days'),
            ],
        ];

        $cookie = Cookie::create(
            $options['name'],
            $options['value'],
            $options['options']
        );

        $response = new Response();
        $response = $response->withCookie($cookie);

        $options['options']['expires'] = $options['options']['expires']->format('U');
        $expected = ['name' => $options['name'], 'value' => $options['value']] + $options['options'];
        $this->assertEquals($expected, $response->getCookie('testing'));

        $expiredCookie = $response->withExpiredCookie($cookie);

        $this->assertSame($expected['expires'], (string)$response->getCookie('testing')['expires']);
        $this->assertLessThan(FrozenTime::createFromTimestamp(1), (string)$expiredCookie->getCookie('testing')['expires']);
    }

    public function testWithExpiredCookieObject(): void
    {
        $response = new Response();
        $cookie = new Cookie('yay', 'a value');
        $response = $response->withCookie($cookie);
        $this->assertSame('a value', $response->getCookie('yay')['value']);

        $new = $response->withExpiredCookie($cookie);

        $this->assertSame(0, $response->getCookie('yay')['expires']);
        $this->assertSame(1, $new->getCookie('yay')['expires']);
    }

    public function testWithExpiredCookieNotUtc()
    {
        date_default_timezone_set('Europe/Paris');

        $response = new Response();
        $cookie = new Cookie('yay', 'a value');
        $response = $response->withExpiredCookie($cookie);
        date_default_timezone_set('UTC');

        $this->assertSame(1, $response->getCookie('yay')['expires']);
    }

    /**
     * Test getCookies() and array data.
     */
    public function testGetCookies(): void
    {
        $response = new Response();
        $new = $response->withCookie(new Cookie('testing', 'a'))
            ->withCookie(new Cookie('test2', 'b', null, '/test', '', true));
        $expected = [
            'testing' => [
                'name' => 'testing',
                'value' => 'a',
                'expires' => 0,
                'path' => '/',
                'domain' => '',
                'secure' => false,
                'httponly' => false,
            ],
            'test2' => [
                'name' => 'test2',
                'value' => 'b',
                'expires' => 0,
                'path' => '/test',
                'domain' => '',
                'secure' => true,
                'httponly' => false,
            ],
        ];
        $this->assertEquals($expected, $new->getCookies());
    }

    /**
     * Test getCookies() and array data.
     */
    public function testGetCookiesArrayValue(): void
    {
        $response = new Response();
        $cookie = (new Cookie('urmc'))
            ->withValue(['user_id' => 1, 'token' => 'abc123'])
            ->withHttpOnly(true);

        $new = $response->withCookie($cookie);
        $expected = [
            'urmc' => [
                'name' => 'urmc',
                'value' => '{"user_id":1,"token":"abc123"}',
                'expires' => 0,
                'path' => '/',
                'domain' => '',
                'secure' => false,
                'httponly' => true,
            ],
        ];
        $this->assertEquals($expected, $new->getCookies());
    }

    /**
     * Test getCookieCollection() as array data
     */
    public function testGetCookieCollection(): void
    {
        $response = new Response();
        $new = $response->withCookie(new Cookie('testing', 'a'))
            ->withCookie(new Cookie('test2', 'b', null, '/test', '', true));
        $cookies = $response->getCookieCollection();
        $this->assertInstanceOf(CookieCollection::class, $cookies);
        $this->assertCount(0, $cookies, 'Original response not mutated');

        $cookies = $new->getCookieCollection();
        $this->assertInstanceOf(CookieCollection::class, $cookies);
        $this->assertCount(2, $cookies);

        $this->assertTrue($cookies->has('testing'));
        $this->assertTrue($cookies->has('test2'));
    }

    /**
     * Test withCookieCollection()
     */
    public function testWithCookieCollection(): void
    {
        $response = new Response();
        $collection = new CookieCollection([new Cookie('foo', 'bar')]);
        $newResponse = $response->withCookieCollection($collection);

        $this->assertNotSame($response, $newResponse);
        $this->assertNotSame($response->getCookieCollection(), $newResponse->getCookieCollection());
        $this->assertSame($newResponse->getCookie('foo')['value'], 'bar');
    }

    /**
     * Test that cors() returns a builder.
     */
    public function testCors(): void
    {
        $request = new ServerRequest([
            'environment' => ['HTTP_ORIGIN' => 'http://example.com'],
        ]);
        $response = new Response();
        $builder = $response->cors($request);
        $this->assertInstanceOf(CorsBuilder::class, $builder);
        $this->assertSame($response, $builder->build(), 'Empty builder returns same object');
    }

    /**
     * test withFile() not found
     */
    public function testWithFileNotFound(): void
    {
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('The requested file /some/missing/folder/file.jpg was not found');

        $response = new Response();
        $response->withFile('/some/missing/folder/file.jpg');
    }

    /**
     * test withFile() not found
     */
    public function testWithFileNotFoundNoDebug(): void
    {
        Configure::write('debug', 0);

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('The requested file was not found');
        $response = new Response();
        $response->withFile('/some/missing/folder/file.jpg');
    }

    /**
     * Provider for various kinds of unacceptable files.
     *
     * @return array
     */
    public function invalidFileProvider(): array
    {
        return [
            ['my/../cat.gif', 'The requested file contains `..` and will not be read.'],
            ['my\..\cat.gif', 'The requested file contains `..` and will not be read.'],
            ['my/ca..t.gif', 'my/ca..t.gif was not found or not readable'],
            ['my/ca..t/image.gif', 'my/ca..t/image.gif was not found or not readable'],
        ];
    }

    /**
     * test withFile and invalid paths
     *
     * @dataProvider invalidFileProvider
     */
    public function testWithFileInvalidPath(string $path, string $expectedMessage): void
    {
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage($expectedMessage);

        $response = new Response();
        $response->withFile($path);
    }

    /**
     * test withFile() + download & name
     */
    public function testWithFileDownloadAndName(): void
    {
        $response = new Response();
        $new = $response->withFile(
            TEST_APP . 'vendor' . DS . 'css' . DS . 'test_asset.css',
            [
                'name' => 'something_special.css',
                'download' => true,
            ]
        );
        $this->assertSame(
            'text/html; charset=UTF-8',
            $response->getHeaderLine('Content-Type'),
            'No mutation'
        );
        $this->assertSame(
            'text/css; charset=UTF-8',
            $new->getHeaderLine('Content-Type')
        );
        $this->assertSame(
            'attachment; filename="something_special.css"',
            $new->getHeaderLine('Content-Disposition')
        );
        $this->assertSame('bytes', $new->getHeaderLine('Accept-Ranges'));
        $this->assertSame('binary', $new->getHeaderLine('Content-Transfer-Encoding'));
        $body = $new->getBody();
        $this->assertInstanceOf('Laminas\Diactoros\Stream', $body);

        $expected = '/* this is the test asset css file */';
        $this->assertSame($expected, trim($body->getContents()));
        $file = $new->getFile()->openFile();
        $this->assertSame($expected, trim($file->fread(100)));
    }

    /**
     * test withFile() + a generic agent
     */
    public function testWithFileUnknownFileTypeGeneric(): void
    {
        $response = new Response();
        $new = $response->withFile(CONFIG . 'no_section.ini');
        $this->assertSame('text/html; charset=UTF-8', $new->getHeaderLine('Content-Type'));
        $this->assertSame(
            'attachment; filename="no_section.ini"',
            $new->getHeaderLine('Content-Disposition')
        );
        $this->assertSame('bytes', $new->getHeaderLine('Accept-Ranges'));
        $body = $new->getBody();
        $expected = "some_key = some_value\nbool_key = 1\n";
        $this->assertSame($expected, $body->getContents());
    }

    /**
     * test withFile() + opera
     */
    public function testWithFileUnknownFileTypeOpera(): void
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Opera/9.80 (Windows NT 6.0; U; en) Presto/2.8.99 Version/11.10';
        $response = new Response();

        $new = $response->withFile(CONFIG . 'no_section.ini');
        $this->assertSame('application/octet-stream', $new->getHeaderLine('Content-Type'));
        $this->assertSame(
            'attachment; filename="no_section.ini"',
            $new->getHeaderLine('Content-Disposition')
        );
    }

    /**
     * test withFile() + old IE
     */
    public function testWithFileUnknownFileTypeOldIe(): void
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (compatible; MSIE 8.0; Windows NT 5.2; Trident/4.0; Media Center PC 4.0; SLCC1; .NET CLR 3.0.04320)';
        $response = new Response();

        $new = $response->withFile(CONFIG . 'no_section.ini');
        $this->assertSame('application/force-download', $new->getHeaderLine('Content-Type'));
    }

    /**
     * test withFile() + no download
     */
    public function testWithFileNoDownload(): void
    {
        $response = new Response();
        $new = $response->withFile(CONFIG . 'no_section.ini', [
            'download' => false,
        ]);
        $this->assertSame(
            'text/html; charset=UTF-8',
            $new->getHeaderLine('Content-Type')
        );
        $this->assertFalse($new->hasHeader('Content-Disposition'));
        $this->assertFalse($new->hasHeader('Content-Transfer-Encoding'));
    }

    /**
     * Test that uppercase extensions result in correct content-types
     */
    public function testWithFileUpperExtension(): void
    {
        $response = new Response();
        $new = $response->withFile(TEST_APP . 'vendor/img/test_2.JPG');
        $this->assertSame('image/jpeg', $new->getHeaderLine('Content-Type'));
    }

    /**
     * A data provider for testing various ranges
     *
     * @return array
     */
    public static function rangeProvider(): array
    {
        return [
            // suffix-byte-range
            [
                'bytes=-25', 25, 'bytes 13-37/38',
            ],

            [
                'bytes=0-', 38, 'bytes 0-37/38',
            ],

            [
                'bytes=10-', 28, 'bytes 10-37/38',
            ],

            [
                'bytes=10-20', 11, 'bytes 10-20/38',
            ],

            // Spaced out
            [
                'bytes = 10 - 20', 11, 'bytes 10-20/38',
            ],
        ];
    }

    /**
     * Test withFile() & the various range offset types.
     *
     * @dataProvider rangeProvider
     */
    public function testWithFileRangeOffsets(string $range, int $length, string $offsetResponse): void
    {
        $_SERVER['HTTP_RANGE'] = $range;
        $response = new Response();
        $new = $response->withFile(
            TEST_APP . 'vendor' . DS . 'css' . DS . 'test_asset.css',
            ['download' => true]
        );
        $this->assertSame(
            'attachment; filename="test_asset.css"',
            $new->getHeaderLine('Content-Disposition')
        );
        $this->assertSame('binary', $new->getHeaderLine('Content-Transfer-Encoding'));
        $this->assertSame('bytes', $new->getHeaderLine('Accept-Ranges'));
        $this->assertEquals($length, $new->getHeaderLine('Content-Length'));
        $this->assertEquals($offsetResponse, $new->getHeaderLine('Content-Range'));
    }

    /**
     * Test withFile() fetching ranges from a file.
     */
    public function testWithFileRange(): void
    {
        $_SERVER['HTTP_RANGE'] = 'bytes=8-25';
        $response = new Response();
        $new = $response->withFile(
            TEST_APP . 'vendor' . DS . 'css' . DS . 'test_asset.css',
            ['download' => true]
        );

        $this->assertSame(
            'attachment; filename="test_asset.css"',
            $new->getHeaderLine('Content-Disposition')
        );
        $this->assertSame('binary', $new->getHeaderLine('Content-Transfer-Encoding'));
        $this->assertSame('bytes', $new->getHeaderLine('Accept-Ranges'));
        $this->assertSame('18', $new->getHeaderLine('Content-Length'));
        $this->assertSame('bytes 8-25/38', $new->getHeaderLine('Content-Range'));
        $this->assertSame(206, $new->getStatusCode());
    }

    /**
     * Provider for invalid range header values.
     *
     * @return array
     */
    public function invalidFileRangeProvider(): array
    {
        return [
            // malformed range
            [
                'bytes=0,38',
            ],

            // malformed punctuation
            [
                'bytes: 0 - 38',
            ],
        ];
    }

    /**
     * Test withFile() and invalid ranges
     *
     * @dataProvider invalidFileRangeProvider
     */
    public function testWithFileInvalidRange(string $range): void
    {
        $_SERVER['HTTP_RANGE'] = $range;
        $response = new Response();
        $new = $response->withFile(
            TEST_APP . 'vendor' . DS . 'css' . DS . 'test_asset.css',
            ['download' => true]
        );

        $this->assertSame(
            'attachment; filename="test_asset.css"',
            $new->getHeaderLine('Content-Disposition')
        );
        $this->assertSame('binary', $new->getHeaderLine('Content-Transfer-Encoding'));
        $this->assertSame('bytes', $new->getHeaderLine('Accept-Ranges'));
        $this->assertSame('38', $new->getHeaderLine('Content-Length'));
        $this->assertSame('bytes 0-37/38', $new->getHeaderLine('Content-Range'));
        $this->assertSame(206, $new->getStatusCode());
    }

    /**
     * Test withFile() and a reversed range
     */
    public function testWithFileReversedRange(): void
    {
        $_SERVER['HTTP_RANGE'] = 'bytes=30-2';
        $response = new Response();
        $new = $response->withFile(
            TEST_APP . 'vendor' . DS . 'css' . DS . 'test_asset.css',
            ['download' => true]
        );

        $this->assertSame(
            'attachment; filename="test_asset.css"',
            $new->getHeaderLine('Content-Disposition')
        );
        $this->assertSame('binary', $new->getHeaderLine('Content-Transfer-Encoding'));
        $this->assertSame('bytes', $new->getHeaderLine('Accept-Ranges'));
        $this->assertSame('bytes 0-37/38', $new->getHeaderLine('Content-Range'));
        $this->assertSame(416, $new->getStatusCode());
    }

    /**
     * Test the withLocation method.
     */
    public function testWithLocation(): void
    {
        $response = new Response();
        $this->assertSame('', $response->getHeaderLine('Location'), 'No header should be set.');
        $new = $response->withLocation('http://example.org');

        $this->assertNotSame($new, $response);
        $this->assertSame('', $response->getHeaderLine('Location'), 'No header should be set');
        $this->assertSame('http://example.org', $new->getHeaderLine('Location'), 'Header should be set');
        $this->assertSame(302, $new->getStatusCode(), 'Status should be updated');
    }

    /**
     * Test get protocol version.
     */
    public function getProtocolVersion(): void
    {
        $response = new Response();
        $version = $response->getProtocolVersion();
        $this->assertSame('1.1', $version);
    }

    /**
     * Test with protocol.
     */
    public function testWithProtocol(): void
    {
        $response = new Response();
        $version = $response->getProtocolVersion();
        $this->assertSame('1.1', $version);
        $response2 = $response->withProtocolVersion('1.0');
        $version = $response2->getProtocolVersion();
        $this->assertSame('1.0', $version);
        $version = $response->getProtocolVersion();
        $this->assertSame('1.1', $version);
        $this->assertNotEquals($response, $response2);
    }

    /**
     * Test with status code.
     */
    public function testWithStatusCode(): void
    {
        $response = new Response();
        $statusCode = $response->getStatusCode();
        $this->assertSame(200, $statusCode);

        $response2 = $response->withStatus(404);
        $statusCode = $response2->getStatusCode();
        $this->assertSame(404, $statusCode);

        $statusCode = $response->getStatusCode();
        $this->assertSame(200, $statusCode);
        $this->assertNotEquals($response, $response2);

        $response3 = $response->withStatus(111);
        $this->assertSame(111, $response3->getStatusCode());
        $this->assertSame('', $response3->getReasonPhrase());
    }

    /**
     * Test invalid status codes
     */
    public function testWithStatusInvalid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid status code: 1001. Use a valid HTTP status code in range 1xx - 5xx.');
        $response = new Response();
        $response->withStatus(1001);
    }

    /**
     * Test get reason phrase.
     */
    public function testGetReasonPhrase(): void
    {
        $response = new Response();
        $this->assertSame('OK', $response->getReasonPhrase());

        $response = $response->withStatus(404);
        $reasonPhrase = $response->getReasonPhrase();
        $this->assertSame('Not Found', $reasonPhrase);
    }

    /**
     * Test with body.
     */
    public function testWithBody(): void
    {
        $response = new Response();
        $body = $response->getBody();
        $body->rewind();
        $result = $body->getContents();
        $this->assertSame('', $result);

        $stream = new Stream('php://memory', 'wb+');
        $stream->write('test1');

        $response2 = $response->withBody($stream);
        $body = $response2->getBody();
        $body->rewind();
        $result = $body->getContents();
        $this->assertSame('test1', $result);

        $body = $response->getBody();
        $body->rewind();
        $result = $body->getContents();
        $this->assertSame('', $result);
    }

    /**
     * Test with string body.
     */
    public function testWithStringBody(): void
    {
        $response = new Response();
        $newResponse = $response->withStringBody('Foo');
        $body = $newResponse->getBody();
        $this->assertSame('Foo', (string)$body);
        $this->assertNotSame($response, $newResponse);

        $response = new Response();
        $newResponse = $response->withStringBody('');
        $body = $newResponse->getBody();
        $this->assertSame('', (string)$body);
        $this->assertNotSame($response, $newResponse);

        $response = new Response();
        $newResponse = $response->withStringBody(null);
        $body = $newResponse->getBody();
        $this->assertSame('', (string)$body);
        $this->assertNotSame($response, $newResponse);

        $response = new Response();
        $newResponse = $response->withStringBody('1337');
        $body = $newResponse->getBody();
        $this->assertSame('1337', (string)$body);
        $this->assertNotSame($response, $newResponse);
    }

    /**
     * Test get Body.
     */
    public function testGetBody(): void
    {
        $response = new Response();
        $stream = $response->getBody();
        $this->assertInstanceOf('Psr\Http\Message\StreamInterface', $stream);
    }

    /**
     * Test with header.
     */
    public function testWithHeader(): void
    {
        $response = new Response();
        $response2 = $response->withHeader('Accept', 'application/json');
        $result = $response2->getHeaders();
        $expected = [
            'Content-Type' => ['text/html; charset=UTF-8'],
            'Accept' => ['application/json'],
        ];
        $this->assertEquals($expected, $result);

        $this->assertFalse($response->hasHeader('Accept'));
    }

    /**
     * Test get headers.
     */
    public function testGetHeaders(): void
    {
        $response = new Response();
        $headers = $response->getHeaders();

        $response = $response->withAddedHeader('Location', 'localhost');
        $response = $response->withAddedHeader('Accept', 'application/json');
        $headers = $response->getHeaders();

        $expected = [
            'Content-Type' => ['text/html; charset=UTF-8'],
            'Location' => ['localhost'],
            'Accept' => ['application/json'],
        ];

        $this->assertEquals($expected, $headers);
    }

    /**
     * Test without header.
     */
    public function testWithoutHeader(): void
    {
        $response = new Response();
        $response = $response->withAddedHeader('Location', 'localhost');
        $response = $response->withAddedHeader('Accept', 'application/json');

        $response2 = $response->withoutHeader('Location');
        $headers = $response2->getHeaders();

        $expected = [
            'Content-Type' => ['text/html; charset=UTF-8'],
            'Accept' => ['application/json'],
        ];

        $this->assertEquals($expected, $headers);
    }

    /**
     * Test get header.
     */
    public function testGetHeader(): void
    {
        $response = new Response();
        $response = $response->withAddedHeader('Location', 'localhost');

        $result = $response->getHeader('Location');
        $this->assertEquals(['localhost'], $result);

        $result = $response->getHeader('location');
        $this->assertEquals(['localhost'], $result);

        $result = $response->getHeader('does-not-exist');
        $this->assertEquals([], $result);
    }

    /**
     * Test get header line.
     */
    public function testGetHeaderLine(): void
    {
        $response = new Response();
        $headers = $response->getHeaderLine('Accept');
        $this->assertSame('', $headers);

        $response = $response->withAddedHeader('Accept', 'application/json');
        $response = $response->withAddedHeader('Accept', 'application/xml');

        $result = $response->getHeaderLine('Accept');
        $expected = 'application/json,application/xml';
        $this->assertSame($expected, $result);
        $result = $response->getHeaderLine('accept');
        $this->assertSame($expected, $result);
    }

    /**
     * Test has header.
     */
    public function testHasHeader(): void
    {
        $response = new Response();
        $response = $response->withAddedHeader('Location', 'localhost');

        $this->assertTrue($response->hasHeader('Location'));
        $this->assertTrue($response->hasHeader('location'));
        $this->assertTrue($response->hasHeader('locATIon'));

        $this->assertFalse($response->hasHeader('Accept'));
        $this->assertFalse($response->hasHeader('accept'));
    }

    /**
     * Tests __debugInfo
     */
    public function testDebugInfo(): void
    {
        $response = new Response();
        $response = $response->withStringBody('Foo');
        $result = $response->__debugInfo();

        $expected = [
            'status' => 200,
            'contentType' => 'text/html',
            'headers' => [
                'Content-Type' => ['text/html; charset=UTF-8'],
            ],
            'file' => null,
            'fileRange' => [],
            'cookies' => new CookieCollection(),
            'cacheDirectives' => [],
            'body' => 'Foo',
        ];
        $this->assertEquals($expected, $result);
    }
}
