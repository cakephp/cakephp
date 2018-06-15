<?php
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
use Cake\TestSuite\TestCase;
use Zend\Diactoros\Stream;

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
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->server = $_SERVER;
    }

    /**
     * teardown
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();
        $_SERVER = $this->server;
        unset($GLOBALS['mockedHeadersSent']);
    }

    /**
     * Tests the request object constructor
     *
     * @return void
     */
    public function testConstruct()
    {
        $response = new Response();
        $this->assertSame('', (string)$response->getBody());
        $this->assertEquals('UTF-8', $response->getCharset());
        $this->assertEquals('text/html', $response->getType());
        $this->assertEquals('text/html; charset=UTF-8', $response->getHeaderLine('Content-Type'));
        $this->assertEquals(200, $response->getStatusCode());

        $options = [
            'body' => 'This is the body',
            'charset' => 'my-custom-charset',
            'type' => 'mp3',
            'status' => '203',
        ];
        $response = new Response($options);
        $this->assertEquals('This is the body', (string)$response->getBody());
        $this->assertEquals('my-custom-charset', $response->getCharset());
        $this->assertEquals('audio/mpeg', $response->getType());
        $this->assertEquals('audio/mpeg', $response->getHeaderLine('Content-Type'));
        $this->assertEquals(203, $response->getStatusCode());
    }

    /**
     * Tests the getCharset/withCharset methods
     *
     * @return void
     */
    public function testWithCharset()
    {
        $response = new Response();
        $this->assertEquals('text/html; charset=UTF-8', $response->getHeaderLine('Content-Type'));

        $new = $response->withCharset('iso-8859-1');
        $this->assertNotContains('iso', $response->getHeaderLine('Content-Type'), 'Old instance not changed');
        $this->assertSame('iso-8859-1', $new->getCharset());

        $this->assertEquals('text/html; charset=iso-8859-1', $new->getHeaderLine('Content-Type'));
    }

    /**
     * Tests the getType method
     *
     * @return void
     */
    public function testGetType()
    {
        $response = new Response();
        $this->assertEquals('text/html', $response->getType());

        $this->assertEquals(
            'application/pdf',
            $response->withType('pdf')->getType()
        );
        $this->assertEquals(
            'custom/stuff',
            $response->withType('custom/stuff')->getType()
        );
        $this->assertEquals(
            'application/json',
            $response->withType('json')->getType()
        );
    }

    /**
     * Tests the withType method
     *
     * @return void
     */
    public function testWithTypeAlias()
    {
        $response = new Response();
        $this->assertEquals(
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
        $this->assertSame(
            'application/json; charset=UTF-8',
            $new->withType('json')->getHeaderLine('Content-Type')
        );
    }

    /**
     * test withType() and full mime-types
     *
     * @return void
     */
    public function withTypeFull()
    {
        $response = new Response();
        $this->assertEquals(
            'application/json',
            $response->withType('application/json')->getHeaderLine('Content-Type'),
            'Should not add charset to explicit type'
        );
        $this->assertEquals(
            'custom/stuff',
            $response->withType('custom/stuff')->getHeaderLine('Content-Type'),
            'Should allow arbitrary types'
        );
    }

    /**
     * Test that an invalid type raises an exception
     *
     * @return void
     */
    public function testWithTypeInvalidType()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('"beans" is an invalid content type');
        $response = new Response();
        $response->withType('beans');
    }

    /**
     * Data provider for content type tests.
     *
     * @return array
     */
    public static function charsetTypeProvider()
    {
        return [
            ['mp3', 'audio/mpeg'],
            ['js', 'application/javascript; charset=UTF-8'],
            ['json', 'application/json; charset=UTF-8'],
            ['xml', 'application/xml; charset=UTF-8'],
            ['txt', 'text/plain; charset=UTF-8'],
        ];
    }

    /**
     * Test that setting certain status codes clears the status code.
     *
     * @return void
     */
    public function testWithStatusClearsContentType()
    {
        $response = new Response();
        $new = $response->withType('pdf')
            ->withStatus(204);
        $this->assertFalse($new->hasHeader('Content-Type'));
        $this->assertSame(204, $new->getStatusCode());

        $response = new Response();
        $new = $response->withStatus(304)
            ->withType('pdf');
        $this->assertFalse($new->hasHeader('Content-Type'));
    }

    /**
     * Tests the withDisabledCache method
     *
     * @return void
     */
    public function testWithDisabledCache()
    {
        $response = new Response();
        $expected = [
            'Expires' => ['Mon, 26 Jul 1997 05:00:00 GMT'],
            'Last-Modified' => [gmdate('D, d M Y H:i:s') . ' GMT'],
            'Cache-Control' => ['no-store, no-cache, must-revalidate, post-check=0, pre-check=0'],
            'Content-Type' => ['text/html; charset=UTF-8'],
        ];
        $new = $response->withDisabledCache();
        $this->assertFalse($response->hasHeader('Expires'), 'Old instance not mutated.');

        $this->assertEquals($expected, $new->getHeaders());
    }

    /**
     * Tests the withCache method
     *
     * @return void
     */
    public function testWithCache()
    {
        $response = new Response();
        $since = $time = time();

        $new = $response->withCache($since, $time);
        $this->assertFalse($response->hasHeader('Date'));
        $this->assertFalse($response->hasHeader('Last-Modified'));

        $this->assertEquals(gmdate('D, j M Y G:i:s ', $since) . 'GMT', $new->getHeaderLine('Date'));
        $this->assertEquals(gmdate('D, j M Y H:i:s ', $since) . 'GMT', $new->getHeaderLine('Last-Modified'));
        $this->assertEquals(gmdate('D, j M Y H:i:s', $time) . ' GMT', $new->getHeaderLine('Expires'));
        $this->assertEquals('public, max-age=0', $new->getHeaderLine('Cache-Control'));
    }

    /**
     * Tests the compress method
     *
     * @return void
     */
    public function testCompress()
    {
        $this->skipIf(defined('HHVM_VERSION'), 'HHVM does not implement ob_gzhandler');

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
     *
     * @return void
     */
    public function testWithDownload()
    {
        $response = new Response();
        $new = $response->withDownload('myfile.mp3');
        $this->assertFalse($response->hasHeader('Content-Disposition'), 'No mutation');

        $expected = 'attachment; filename="myfile.mp3"';
        $this->assertEquals($expected, $new->getHeaderLine('Content-Disposition'));
    }

    /**
     * Tests the mapType method
     *
     * @return void
     */
    public function testMapType()
    {
        $response = new Response();
        $this->assertEquals('wav', $response->mapType('audio/x-wav'));
        $this->assertEquals('pdf', $response->mapType('application/pdf'));
        $this->assertEquals('xml', $response->mapType('text/xml'));
        $this->assertEquals('html', $response->mapType('*/*'));
        $this->assertEquals('csv', $response->mapType('application/vnd.ms-excel'));
        $expected = ['json', 'xhtml', 'css'];
        $result = $response->mapType(['application/json', 'application/xhtml+xml', 'text/css']);
        $this->assertEquals($expected, $result);
    }

    /**
     * Tests the outputCompressed method
     *
     * @return void
     */
    public function testOutputCompressed()
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

        $this->skipIf(defined('HHVM_VERSION'), 'HHVM does not implement ob_gzhandler');

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
     *
     * @return void
     */
    public function testWithLength()
    {
        $response = new Response();
        $this->assertFalse($response->hasHeader('Content-Length'));

        $new = $response->withLength(100);
        $this->assertFalse($response->hasHeader('Content-Length'), 'Old instance not modified');

        $this->assertSame('100', $new->getHeaderLine('Content-Length'));
    }

    /**
     * Tests settings the link
     *
     * @return void
     */
    public function testWithAddedLink()
    {
        $response = new Response();
        $this->assertFalse($response->hasHeader('Link'));

        $new = $response->withAddedLink('http://example.com', ['rel' => 'prev']);
        $this->assertFalse($response->hasHeader('Link'), 'Old instance not modified');
        $this->assertEquals('<http://example.com>; rel="prev"', $new->getHeaderLine('Link'));

        $new = $response->withAddedLink('http://example.com');
        $this->assertEquals('<http://example.com>', $new->getHeaderLine('Link'));

        $new = $response->withAddedLink('http://example.com?p=1', ['rel' => 'prev'])
            ->withAddedLink('http://example.com?p=2', ['rel' => 'next', 'foo' => 'bar']);
        $this->assertEquals('<http://example.com?p=1>; rel="prev",<http://example.com?p=2>; rel="next"; foo="bar"', $new->getHeaderLine('Link'));
    }

    /**
     * Tests the withExpires method
     *
     * @return void
     */
    public function testWithExpires()
    {
        $format = 'D, j M Y H:i:s';
        $response = new Response();
        $now = new \DateTime('now', new \DateTimeZone('America/Los_Angeles'));

        $new = $response->withExpires($now);
        $this->assertFalse($response->hasHeader('Expires'));

        $now->setTimeZone(new \DateTimeZone('UTC'));
        $this->assertEquals($now->format($format) . ' GMT', $new->getHeaderLine('Expires'));

        $now = time();
        $new = $response->withExpires($now);
        $this->assertEquals(gmdate($format) . ' GMT', $new->getHeaderLine('Expires'));

        $time = new \DateTime('+1 day', new \DateTimeZone('UTC'));
        $new = $response->withExpires('+1 day');
        $this->assertEquals($time->format($format) . ' GMT', $new->getHeaderLine('Expires'));
    }

    /**
     * Tests the withModified method
     *
     * @return void
     */
    public function testWithModified()
    {
        $format = 'D, j M Y H:i:s';
        $response = new Response();
        $now = new \DateTime('now', new \DateTimeZone('America/Los_Angeles'));
        $new = $response->withModified($now);
        $this->assertFalse($response->hasHeader('Last-Modified'));

        $now->setTimeZone(new \DateTimeZone('UTC'));
        $this->assertEquals($now->format($format) . ' GMT', $new->getHeaderLine('Last-Modified'));

        $now = time();
        $new = $response->withModified($now);
        $this->assertEquals(gmdate($format) . ' GMT', $new->getHeaderLine('Last-Modified'));

        $time = new \DateTime('+1 day', new \DateTimeZone('UTC'));
        $new = $response->withModified('+1 day');
        $this->assertEquals($time->format($format) . ' GMT', $new->getHeaderLine('Last-Modified'));
    }

    /**
     * Tests withSharable()
     *
     * @return void
     */
    public function testWithSharable()
    {
        $response = new Response();
        $new = $response->withSharable(true);
        $this->assertFalse($response->hasHeader('Cache-Control'), 'old instance unchanged');
        $this->assertEquals('public', $new->getHeaderLine('Cache-Control'));

        $new = $response->withSharable(false);
        $this->assertEquals('private', $new->getHeaderLine('Cache-Control'));

        $new = $response->withSharable(true, 3600);
        $this->assertEquals('public, max-age=3600', $new->getHeaderLine('Cache-Control'));

        $new = $response->withSharable(false, 3600);
        $this->assertEquals('private, max-age=3600', $new->getHeaderLine('Cache-Control'));
    }

    /**
     * Tests withMaxAge()
     *
     * @return void
     */
    public function testWithMaxAge()
    {
        $response = new Response();
        $this->assertFalse($response->hasHeader('Cache-Control'));

        $new = $response->withMaxAge(3600);
        $this->assertEquals('max-age=3600', $new->getHeaderLine('Cache-Control'));

        $new = $response->withMaxAge(3600)
            ->withSharable(false);
        $this->assertEquals('max-age=3600, private', $new->getHeaderLine('Cache-Control'));
    }

    /**
     * Tests setting of s-maxage Cache-Control directive
     *
     * @return void
     */
    public function testWithSharedMaxAge()
    {
        $response = new Response();
        $new = $response->withSharedMaxAge(3600);

        $this->assertFalse($response->hasHeader('Cache-Control'));
        $this->assertEquals('s-maxage=3600', $new->getHeaderLine('Cache-Control'));

        $new = $response->withSharedMaxAge(3600)->withSharable(true);
        $this->assertEquals('s-maxage=3600, public', $new->getHeaderLine('Cache-Control'));
    }

    /**
     * Tests setting of must-revalidate Cache-Control directive
     *
     * @return void
     */
    public function testWithMustRevalidate()
    {
        $response = new Response();
        $this->assertFalse($response->hasHeader('Cache-Control'));

        $new = $response->withMustRevalidate(true);
        $this->assertFalse($response->hasHeader('Cache-Control'));
        $this->assertEquals('must-revalidate', $new->getHeaderLine('Cache-Control'));

        $new = $new->withMustRevalidate(false);
        $this->assertEmpty($new->getHeaderLine('Cache-Control'));
    }

    /**
     * Tests withVary()
     *
     * @return void
     */
    public function testWithVary()
    {
        $response = new Response();
        $new = $response->withVary('Accept-encoding');

        $this->assertFalse($response->hasHeader('Vary'));
        $this->assertEquals('Accept-encoding', $new->getHeaderLine('Vary'));

        $new = $response->withVary(['Accept-encoding', 'Accept-language']);
        $this->assertFalse($response->hasHeader('Vary'));
        $this->assertEquals('Accept-encoding,Accept-language', $new->getHeaderLine('Vary'));
    }

    /**
     * Tests withEtag()
     *
     * @return void
     */
    public function testWithEtag()
    {
        $response = new Response();
        $new = $response->withEtag('something');

        $this->assertFalse($response->hasHeader('Etag'));
        $this->assertEquals('"something"', $new->getHeaderLine('Etag'));

        $new = $response->withEtag('something', true);
        $this->assertEquals('W/"something"', $new->getHeaderLine('Etag'));
    }

    /**
     * Tests that the response is able to be marked as not modified
     *
     * @return void
     */
    public function testNotModified()
    {
        $response = new Response();
        $response = $response->withStringBody('something')
            ->withStatus(200)
            ->withLength(100)
            ->withModified('now');

        $response->notModified();

        $this->assertFalse($response->hasHeader('Content-Length'));
        $this->assertFalse($response->hasHeader('Modified'));
        $this->assertEmpty((string)$response->getBody());
        $this->assertEquals(304, $response->getStatusCode());
    }

    /**
     * Tests withNotModified()
     *
     * @return void
     */
    public function testWithNotModified()
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
     *
     * @return void
     */
    public function testCheckNotModifiedByEtagStar()
    {
        $request = new ServerRequest();
        $request = $request->withHeader('If-None-Match', '*');

        $response = new Response();
        $response = $response->withEtag('something')
            ->withHeader('Content-Length', 99);
        $this->assertTrue($response->checkNotModified($request));
        $this->assertFalse($response->hasHeader('Content-Type'), 'etags match, should be unmodified');
    }

    /**
     * Test checkNotModified method
     *
     * @return void
     */
    public function testCheckNotModifiedByEtagExact()
    {
        $request = new ServerRequest();
        $request = $request->withHeader('If-None-Match', 'W/"something", "other"');

        $response = new Response();
        $response = $response->withEtag('something', true)
            ->withHeader('Content-Length', 99);
        $this->assertTrue($response->checkNotModified($request));
        $this->assertFalse($response->hasHeader('Content-Type'), 'etags match, should be unmodified');
    }

    /**
     * Test checkNotModified method
     *
     * @return void
     */
    public function testCheckNotModifiedByEtagAndTime()
    {
        $request = new ServerRequest();
        $request = $request->withHeader('If-Modified-Since', '2012-01-01 00:00:00')
            ->withHeader('If-None-Match', 'W/"something", "other"');

        $response = new Response();
        $response = $response->withModified('2012-01-01 00:00:00')
            ->withEtag('something', true)
            ->withHeader('Content-Length', 99);
        $this->assertTrue($response->checkNotModified($request));
        $this->assertFalse($response->hasHeader('Content-Length'), 'etags match, should be unmodified');
    }

    /**
     * Test checkNotModified method
     *
     * @return void
     */
    public function testCheckNotModifiedByEtagAndTimeMismatch()
    {
        $request = new ServerRequest();
        $request = $request->withHeader('If-Modified-Since', '2012-01-01 00:00:00')
            ->withHeader('If-None-Match', 'W/"something", "other"');

        $response = new Response();
        $response = $response->withModified('2012-01-01 00:00:01')
            ->withEtag('something', true)
            ->withHeader('Content-Length', 99);
        $this->assertFalse($response->checkNotModified($request));
        $this->assertTrue($response->hasHeader('Content-Length'), 'timestamp in response is newer');
    }

    /**
     * Test checkNotModified method
     *
     * @return void
     */
    public function testCheckNotModifiedByEtagMismatch()
    {
        $request = new ServerRequest();
        $request = $request->withHeader('If-Modified-Since', '2012-01-01 00:00:00')
            ->withHeader('If-None-Match', 'W/"something-else", "other"');

        $response = new Response();
        $response = $response->withModified('2012-01-01 00:00:00')
            ->withEtag('something', true)
            ->withHeader('Content-Length', 99);
        $this->assertFalse($response->checkNotModified($request));
        $this->assertTrue($response->hasHeader('Content-Length'), 'etags do not match');
    }

    /**
     * Test checkNotModified method
     *
     * @return void
     */
    public function testCheckNotModifiedByTime()
    {
        $request = new ServerRequest();
        $request = $request->withHeader('If-Modified-Since', '2012-01-01 00:00:00');

        $response = new Response();
        $response = $response->withModified('2012-01-01 00:00:00')
            ->withHeader('Content-Length', 99);
        $this->assertTrue($response->checkNotModified($request));
        $this->assertFalse($response->hasHeader('Content-Length'), 'modified time matches');
    }

    /**
     * Test checkNotModified method
     *
     * @return void
     */
    public function testCheckNotModifiedNoHints()
    {
        $request = new ServerRequest();
        $request = $request->withHeader('If-None-Match', 'W/"something", "other"')
            ->withHeader('If-Modified-Since', '2012-01-01 00:00:00');
        $response = $this->getMockBuilder('Cake\Http\Response')
            ->setMethods(['notModified'])
            ->getMock();
        $response->expects($this->never())->method('notModified');
        $this->assertFalse($response->checkNotModified($request));
    }

    /**
     * Test setting cookies with no value
     *
     * @return void
     */
    public function testWithCookieEmpty()
    {
        $response = new Response();
        $new = $response->withCookie('testing');
        $this->assertNull($response->getCookie('testing'), 'withCookie does not mutate');

        $expected = [
            'name' => 'testing',
            'value' => '',
            'expire' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => false,
            'httpOnly' => false];
        $result = $new->getCookie('testing');
        $this->assertEquals($expected, $result);
    }

    /**
     * Test setting cookies with scalar values
     *
     * @return void
     */
    public function testWithCookieScalar()
    {
        $response = new Response();
        $new = $response->withCookie('testing', 'abc123');
        $this->assertNull($response->getCookie('testing'), 'withCookie does not mutate');
        $this->assertEquals('abc123', $new->getCookie('testing')['value']);

        $new = $response->withCookie('testing', 99);
        $this->assertEquals(99, $new->getCookie('testing')['value']);

        $new = $response->withCookie('testing', false);
        $this->assertFalse($new->getCookie('testing')['value']);

        $new = $response->withCookie('testing', true);
        $this->assertTrue($new->getCookie('testing')['value']);
    }

    /**
     * Test withCookie() and array data.
     *
     * @return void
     */
    public function testWithCookieArray()
    {
        $response = new Response();
        $cookie = [
            'name' => 'ignored key',
            'value' => '[a,b,c]',
            'expire' => 1000,
            'path' => '/test',
            'secure' => true,
        ];
        $new = $response->withCookie('testing', $cookie);
        $this->assertNull($response->getCookie('testing'), 'withCookie does not mutate');
        $expected = [
            'name' => 'testing',
            'value' => '[a,b,c]',
            'expire' => 1000,
            'path' => '/test',
            'domain' => '',
            'secure' => true,
            'httpOnly' => false,
        ];
        $this->assertEquals($expected, $new->getCookie('testing'));
    }

    /**
     * Test withCookie() and a cookie instance
     *
     * @return void
     */
    public function testWithCookieObject()
    {
        $response = new Response();
        $cookie = new Cookie('yay', 'a value');
        $new = $response->withCookie($cookie);
        $this->assertNull($response->getCookie('yay'), 'withCookie does not mutate');

        $this->assertNotEmpty($new->getCookie('yay'));
        $this->assertSame($cookie, $new->getCookieCollection()->get('yay'));
    }

    public function testWithExpiredCookieScalar()
    {
        $response = new Response();
        $response = $response->withCookie('testing', 'abc123');
        $this->assertEquals('abc123', $response->getCookie('testing')['value']);

        $new = $response->withExpiredCookie('testing');

        $this->assertNull($response->getCookie('testing')['expire']);
        $this->assertEquals('1', $new->getCookie('testing')['expire']);
    }

    public function testWithExpiredCookieOptions()
    {
        $options = [
            'name' => 'testing',
            'value' => 'abc123',
            'domain' => 'cakephp.org',
            'path' => '/custompath/',
            'secure' => true,
            'httpOnly' => true,
            'expire' => (string)strtotime('+14 days'),
        ];

        $response = new Response();
        $response = $response->withCookie('testing', $options);
        $this->assertEquals($options, $response->getCookie('testing'));

        $new = $response->withExpiredCookie('testing', $options);

        $this->assertEquals($options['expire'], $response->getCookie('testing')['expire']);
        $this->assertEquals('1', $new->getCookie('testing')['expire']);
        $this->assertEquals('', $new->getCookie('testing')['value']);
    }

    public function testWithExpiredCookieObject()
    {
        $response = new Response();
        $cookie = new Cookie('yay', 'a value');
        $response = $response->withCookie($cookie);
        $this->assertEquals('a value', $response->getCookie('yay')['value']);

        $new = $response->withExpiredCookie($cookie);

        $this->assertNull($response->getCookie('yay')['expire']);
        $this->assertEquals('1', $new->getCookie('yay')['expire']);
    }

    /**
     * Test getCookies() and array data.
     *
     * @return void
     */
    public function testGetCookies()
    {
        $response = new Response();
        $new = $response->withCookie('testing', 'a')
            ->withCookie('test2', ['value' => 'b', 'path' => '/test', 'secure' => true]);
        $expected = [
            'testing' => [
                'name' => 'testing',
                'value' => 'a',
                'expire' => null,
                'path' => '/',
                'domain' => '',
                'secure' => false,
                'httpOnly' => false,
            ],
            'test2' => [
                'name' => 'test2',
                'value' => 'b',
                'expire' => null,
                'path' => '/test',
                'domain' => '',
                'secure' => true,
                'httpOnly' => false,
            ],
        ];
        $this->assertEquals($expected, $new->getCookies());
    }

    /**
     * Test getCookies() and array data.
     *
     * @return void
     */
    public function testGetCookiesArrayValue()
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
                'expire' => null,
                'path' => '',
                'domain' => '',
                'secure' => false,
                'httpOnly' => true,
            ],
        ];
        $this->assertEquals($expected, $new->getCookies());
    }

    /**
     * Test getCookieCollection() as array data
     *
     * @return void
     */
    public function testGetCookieCollection()
    {
        $response = new Response();
        $new = $response->withCookie('testing', 'a')
            ->withCookie('test2', ['value' => 'b', 'path' => '/test', 'secure' => true]);
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
     * Test that cors() returns a builder.
     *
     * @return void
     */
    public function testCors()
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
     *
     * @return void
     */
    public function testWithFileNotFound()
    {
        $this->expectException(\Cake\Http\Exception\NotFoundException::class);
        $this->expectExceptionMessage('The requested file /some/missing/folder/file.jpg was not found');

        $response = new Response();
        $response->withFile('/some/missing/folder/file.jpg');
    }

    /**
     * test withFile() not found
     *
     * @return void
     */
    public function testWithFileNotFoundNoDebug()
    {
        Configure::write('debug', 0);

        $this->expectException(\Cake\Http\Exception\NotFoundException::class);
        $this->expectExceptionMessage('The requested file was not found');
        $response = new Response();
        $response->withFile('/some/missing/folder/file.jpg');
    }

    /**
     * Provider for various kinds of unacceptable files.
     *
     * @return array
     */
    public function invalidFileProvider()
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
     * @return void
     */
    public function testWithFileInvalidPath($path, $expectedMessage)
    {
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage($expectedMessage);

        $response = new Response();
        $response->withFile($path);
    }

    /**
     * test withFile() + download & name
     *
     * @return void
     */
    public function testWithFileDownloadAndName()
    {
        $response = new Response();
        $new = $response->withFile(
            TEST_APP . 'vendor' . DS . 'css' . DS . 'test_asset.css',
            [
                'name' => 'something_special.css',
                'download' => true,
            ]
        );
        $this->assertEquals(
            'text/html; charset=UTF-8',
            $response->getHeaderLine('Content-Type'),
            'No mutation'
        );
        $this->assertEquals(
            'text/css; charset=UTF-8',
            $new->getHeaderLine('Content-Type')
        );
        $this->assertEquals(
            'attachment; filename="something_special.css"',
            $new->getHeaderLine('Content-Disposition')
        );
        $this->assertEquals('bytes', $new->getHeaderLine('Accept-Ranges'));
        $this->assertEquals('binary', $new->getHeaderLine('Content-Transfer-Encoding'));
        $body = $new->getBody();
        $this->assertInstanceOf('Zend\Diactoros\Stream', $body);

        $expected = '/* this is the test asset css file */';
        $this->assertEquals($expected, trim($body->getContents()));
        $this->assertEquals($expected, trim($new->getFile()->read()));
    }

    /**
     * test withFile() + a generic agent
     *
     * @return void
     */
    public function testWithFileUnknownFileTypeGeneric()
    {
        $response = new Response();
        $new = $response->withFile(CONFIG . 'no_section.ini');
        $this->assertEquals('text/html; charset=UTF-8', $new->getHeaderLine('Content-Type'));
        $this->assertEquals(
            'attachment; filename="no_section.ini"',
            $new->getHeaderLine('Content-Disposition')
        );
        $this->assertEquals('bytes', $new->getHeaderLine('Accept-Ranges'));
        $body = $new->getBody();
        $expected = "some_key = some_value\nbool_key = 1\n";
        $this->assertEquals($expected, $body->getContents());
    }

    /**
     * test withFile() + opera
     *
     * @return void
     */
    public function testWithFileUnknownFileTypeOpera()
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Opera/9.80 (Windows NT 6.0; U; en) Presto/2.8.99 Version/11.10';
        $response = new Response();

        $new = $response->withFile(CONFIG . 'no_section.ini');
        $this->assertEquals('application/octet-stream', $new->getHeaderLine('Content-Type'));
        $this->assertEquals(
            'attachment; filename="no_section.ini"',
            $new->getHeaderLine('Content-Disposition')
        );
    }

    /**
     * test withFile() + old IE
     *
     * @return void
     */
    public function testWithFileUnknownFileTypeOldIe()
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (compatible; MSIE 8.0; Windows NT 5.2; Trident/4.0; Media Center PC 4.0; SLCC1; .NET CLR 3.0.04320)';
        $response = new Response();

        $new = $response->withFile(CONFIG . 'no_section.ini');
        $this->assertEquals('application/force-download', $new->getHeaderLine('Content-Type'));
    }

    /**
     * test withFile() + no download
     *
     * @return void
     */
    public function testWithFileNoDownload()
    {
        $response = new Response();
        $new = $response->withFile(CONFIG . 'no_section.ini', [
            'download' => false,
        ]);
        $this->assertEquals(
            'text/html; charset=UTF-8',
            $new->getHeaderLine('Content-Type')
        );
        $this->assertFalse($new->hasHeader('Content-Disposition'));
        $this->assertFalse($new->hasHeader('Content-Transfer-Encoding'));
    }

    /**
     * Test that uppercase extensions result in correct content-types
     *
     * @return void
     */
    public function testWithFileUpperExtension()
    {
        $response = new Response();
        $new = $response->withFile(TEST_APP . 'vendor/img/test_2.JPG');
        $this->assertEquals('image/jpeg', $new->getHeaderLine('Content-Type'));
    }

    /**
     * A data provider for testing various ranges
     *
     * @return array
     */
    public static function rangeProvider()
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
     * @return void
     */
    public function testWithFileRangeOffsets($range, $length, $offsetResponse)
    {
        $_SERVER['HTTP_RANGE'] = $range;
        $response = new Response();
        $new = $response->withFile(
            TEST_APP . 'vendor' . DS . 'css' . DS . 'test_asset.css',
            ['download' => true]
        );
        $this->assertEquals(
            'attachment; filename="test_asset.css"',
            $new->getHeaderLine('Content-Disposition')
        );
        $this->assertEquals('binary', $new->getHeaderLine('Content-Transfer-Encoding'));
        $this->assertEquals('bytes', $new->getHeaderLine('Accept-Ranges'));
        $this->assertEquals($length, $new->getHeaderLine('Content-Length'));
        $this->assertEquals($offsetResponse, $new->getHeaderLine('Content-Range'));
    }

    /**
     * Test withFile() fetching ranges from a file.
     *
     * @return void
     */
    public function testWithFileRange()
    {
        $_SERVER['HTTP_RANGE'] = 'bytes=8-25';
        $response = new Response();
        $new = $response->withFile(
            TEST_APP . 'vendor' . DS . 'css' . DS . 'test_asset.css',
            ['download' => true]
        );

        $this->assertEquals(
            'attachment; filename="test_asset.css"',
            $new->getHeaderLine('Content-Disposition')
        );
        $this->assertEquals('binary', $new->getHeaderLine('Content-Transfer-Encoding'));
        $this->assertEquals('bytes', $new->getHeaderLine('Accept-Ranges'));
        $this->assertEquals('18', $new->getHeaderLine('Content-Length'));
        $this->assertEquals('bytes 8-25/38', $new->getHeaderLine('Content-Range'));
        $this->assertEquals(206, $new->getStatusCode());
    }

    /**
     * Provider for invalid range header values.
     *
     * @return array
     */
    public function invalidFileRangeProvider()
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
     * @return void
     */
    public function testWithFileInvalidRange($range)
    {
        $_SERVER['HTTP_RANGE'] = $range;
        $response = new Response();
        $new = $response->withFile(
            TEST_APP . 'vendor' . DS . 'css' . DS . 'test_asset.css',
            ['download' => true]
        );

        $this->assertEquals(
            'attachment; filename="test_asset.css"',
            $new->getHeaderLine('Content-Disposition')
        );
        $this->assertEquals('binary', $new->getHeaderLine('Content-Transfer-Encoding'));
        $this->assertEquals('bytes', $new->getHeaderLine('Accept-Ranges'));
        $this->assertEquals('38', $new->getHeaderLine('Content-Length'));
        $this->assertEquals('bytes 0-37/38', $new->getHeaderLine('Content-Range'));
        $this->assertEquals(206, $new->getStatusCode());
    }

    /**
     * Test withFile() and a reversed range
     *
     * @return void
     */
    public function testWithFileReversedRange()
    {
        $_SERVER['HTTP_RANGE'] = 'bytes=30-2';
        $response = new Response();
        $new = $response->withFile(
            TEST_APP . 'vendor' . DS . 'css' . DS . 'test_asset.css',
            ['download' => true]
        );

        $this->assertEquals(
            'attachment; filename="test_asset.css"',
            $new->getHeaderLine('Content-Disposition')
        );
        $this->assertEquals('binary', $new->getHeaderLine('Content-Transfer-Encoding'));
        $this->assertEquals('bytes', $new->getHeaderLine('Accept-Ranges'));
        $this->assertEquals('bytes 0-37/38', $new->getHeaderLine('Content-Range'));
        $this->assertEquals(416, $new->getStatusCode());
    }

    /**
     * Test the withLocation method.
     *
     * @return void
     */
    public function testWithLocation()
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
     *
     * @return void
     */
    public function getProtocolVersion()
    {
        $response = new Response();
        $version = $response->getProtocolVersion();
        $this->assertEquals('1.1', $version);
    }

    /**
     * Test with protocol.
     *
     * @return void
     */
    public function testWithProtocol()
    {
        $response = new Response();
        $version = $response->getProtocolVersion();
        $this->assertEquals('1.1', $version);
        $response2 = $response->withProtocolVersion('1.0');
        $version = $response2->getProtocolVersion();
        $this->assertEquals('1.0', $version);
        $version = $response->getProtocolVersion();
        $this->assertEquals('1.1', $version);
        $this->assertNotEquals($response, $response2);
    }

    /**
     * Test with protocol.
     *
     * @return void
     */
    public function testWithStatusCode()
    {
        $response = new Response();
        $statusCode = $response->getStatusCode();
        $this->assertEquals(200, $statusCode);
        $response2 = $response->withStatus(404);
        $statusCode = $response2->getStatusCode();
        $this->assertEquals(404, $statusCode);
        $statusCode = $response->getStatusCode();
        $this->assertEquals(200, $statusCode);
        $this->assertNotEquals($response, $response2);
    }

    /**
     * Test invalid status codes
     *
     * @return void
     */
    public function testWithStatusInvalid()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid status code: 1001. Use a valid HTTP status code in range 1xx - 5xx.');
        $response = new Response();
        $response->withStatus(1001);
    }

    /**
     * Test get reason phrase.
     *
     * @return void
     */
    public function testGetReasonPhrase()
    {
        $response = new Response();
        $this->assertSame('OK', $response->getReasonPhrase());

        $response = $response->withStatus(404);
        $reasonPhrase = $response->getReasonPhrase();
        $this->assertEquals('Not Found', $reasonPhrase);
    }

    /**
     * Test with body.
     *
     * @return void
     */
    public function testWithBody()
    {
        $response = new Response();
        $body = $response->getBody();
        $body->rewind();
        $result = $body->getContents();
        $this->assertEquals('', $result);

        $stream = new Stream('php://memory', 'wb+');
        $stream->write('test1');

        $response2 = $response->withBody($stream);
        $body = $response2->getBody();
        $body->rewind();
        $result = $body->getContents();
        $this->assertEquals('test1', $result);

        $body = $response->getBody();
        $body->rewind();
        $result = $body->getContents();
        $this->assertEquals('', $result);
    }

    /**
     * Test with string body.
     *
     * @return void
     */
    public function testWithStringBody()
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
        $newResponse = $response->withStringBody(1337);
        $body = $newResponse->getBody();
        $this->assertSame('1337', (string)$body);
        $this->assertNotSame($response, $newResponse);
    }

    /**
     * Test with string body with passed array.
     *
     * This should produce an "Array to string conversion" error
     * which gets thrown as a \PHPUnit\Framework\Error\Error Exception by PHPUnit.
     *
     * @return void
     */
    public function testWithStringBodyArray()
    {
        $this->expectException(\PHPUnit\Framework\Error\Error::class);
        $this->expectExceptionMessage('Array to string conversion');
        $response = new Response();
        $newResponse = $response->withStringBody(['foo' => 'bar']);
        $body = $newResponse->getBody();
        $this->assertSame('', (string)$body);
        $this->assertNotSame($response, $newResponse);
    }

    /**
     * Test get Body.
     *
     * @return void
     */
    public function testGetBody()
    {
        $response = new Response();
        $stream = $response->getBody();
        $this->assertInstanceOf('Psr\Http\Message\StreamInterface', $stream);
    }

    /**
     * Test with header.
     *
     * @return void
     */
    public function testWithHeader()
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
     *
     * @return void
     */
    public function testGetHeaders()
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
     *
     * @return void
     */
    public function testWithoutHeader()
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
     *
     * @return void
     */
    public function testGetHeader()
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
     *
     * @return void
     */
    public function testGetHeaderLine()
    {
        $response = new Response();
        $headers = $response->getHeaderLine('Accept');
        $this->assertEquals('', $headers);

        $response = $response->withAddedHeader('Accept', 'application/json');
        $response = $response->withAddedHeader('Accept', 'application/xml');

        $result = $response->getHeaderLine('Accept');
        $expected = 'application/json,application/xml';
        $this->assertEquals($expected, $result);
        $result = $response->getHeaderLine('accept');
        $this->assertEquals($expected, $result);
    }

    /**
     * Test has header.
     *
     * @return void
     */
    public function testHasHeader()
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
     *
     * @return void
     */
    public function testDebugInfo()
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
