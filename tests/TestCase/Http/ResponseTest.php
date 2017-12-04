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

use Cake\Http\Cookie\Cookie;
use Cake\Http\Cookie\CookieCollection;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\Network\Exception\NotFoundException;
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
    }

    /**
     * Tests the request object constructor
     *
     * @return void
     */
    public function testConstruct()
    {
        $response = new Response();
        $this->assertNull($response->body());
        $this->assertEquals('UTF-8', $response->charset());
        $this->assertEquals('text/html', $response->type());
        $this->assertEquals('text/html; charset=UTF-8', $response->getHeaderLine('Content-Type'));
        $this->assertEquals(200, $response->statusCode());

        $options = [
            'body' => 'This is the body',
            'charset' => 'my-custom-charset',
            'type' => 'mp3',
            'status' => '203'
        ];
        $response = new Response($options);
        $this->assertEquals('This is the body', $response->body());
        $this->assertEquals('my-custom-charset', $response->charset());
        $this->assertEquals('audio/mpeg', $response->type());
        $this->assertEquals('audio/mpeg', $response->getHeaderLine('Content-Type'));
        $this->assertEquals(203, $response->statusCode());

        $options = [
            'body' => 'This is the body',
            'charset' => 'ISO-8859-1',
            'type' => 'txt',
            'status' => '422',
            'statusCodes' => [
                422 => 'Unprocessable Entity'
            ]
        ];
        $response = new Response($options);
        $this->assertEquals($options['body'], $response->body());
        $this->assertEquals($options['charset'], $response->charset());
        $this->assertEquals($response->getMimeType($options['type']), $response->type());
        $this->assertEquals($options['status'], $response->statusCode());
        $this->assertEquals('text/plain; charset=ISO-8859-1', $response->getHeaderLine('Content-Type'));
    }

    /**
     * Tests the body method
     *
     * @return void
     */
    public function testBody()
    {
        $response = new Response();
        $this->assertNull($response->body());
        $response->body('Response body');
        $this->assertEquals('Response body', $response->body());
        $this->assertEquals('Changed Body', $response->body('Changed Body'));

        $response = new Response();
        $response->body(0);
        $this->assertEquals(0, $response->body());

        $response = new Response();
        $response->body('0');
        $this->assertEquals('0', $response->body());

        $response = new Response();
        $response->body(null);
        $this->assertEquals(null, $response->body());
    }

    /**
     * Tests the charset method
     *
     * @return void
     */
    public function testCharset()
    {
        $response = new Response();
        $this->assertEquals('UTF-8', $response->charset());
        $response->charset('iso-8859-1');
        $this->assertEquals('iso-8859-1', $response->charset());
        $this->assertEquals('UTF-16', $response->charset('UTF-16'));
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
     * Tests the statusCode method
     *
     * @return void
     */
    public function testStatusCode()
    {
        $response = new Response();
        $this->assertEquals(200, $response->statusCode());

        $response->statusCode(404);
        $this->assertEquals(404, $response->getStatusCode(), 'Sets shared state.');
        $this->assertEquals(404, $response->statusCode());
        $this->assertEquals('Not Found', $response->getReasonPhrase());

        $this->assertEquals(500, $response->statusCode(500));
    }

    /**
     * Test invalid status codes
     *
     * @return void
     */
    public function testStatusCodeInvalid()
    {
        $this->expectException(\InvalidArgumentException::class);
        $response = new Response();
        $response->statusCode(1001);
    }

    /**
     * Tests the type method
     *
     * @return void
     */
    public function testType()
    {
        $response = new Response();
        $this->assertEquals('text/html', $response->type());
        $response->type('pdf');
        $this->assertEquals('application/pdf', $response->type());
        $this->assertEquals('application/crazy-mime', $response->type('application/crazy-mime'));
        $this->assertEquals('application/json', $response->type('json'));
        $this->assertEquals('text/vnd.wap.wml', $response->type('wap'));
        $this->assertEquals('application/vnd.wap.xhtml+xml', $response->type('xhtml-mobile'));
        $this->assertEquals('text/csv', $response->type('csv'));

        $response->type(['keynote' => 'application/keynote', 'bat' => 'application/bat']);
        $this->assertEquals('application/keynote', $response->type('keynote'));
        $this->assertEquals('application/bat', $response->type('bat'));

        $this->assertFalse($response->type('wackytype'));
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
        $response->type('pdf');
        $this->assertEquals('application/pdf', $response->getType());

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
     * Tests the header method
     *
     * @return void
     */
    public function testHeader()
    {
        $response = new Response();
        $headers = [
            'Content-Type' => 'text/html; charset=UTF-8',
        ];
        $this->assertEquals($headers, $response->header());

        $response->header('Location', 'http://example.com');
        $headers += ['Location' => 'http://example.com'];
        $this->assertEquals($headers, $response->header());

        // Headers with the same name are overwritten
        $response->header('Location', 'http://example2.com');
        $headers = [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Location' => 'http://example2.com'
        ];
        $this->assertEquals($headers, $response->header());

        $response->header(['WWW-Authenticate' => 'Negotiate']);
        $headers += ['WWW-Authenticate' => 'Negotiate'];
        $this->assertEquals($headers, $response->header());

        $response->header(['WWW-Authenticate' => 'Not-Negotiate']);
        $headers['WWW-Authenticate'] = 'Not-Negotiate';
        $this->assertEquals($headers, $response->header());

        $response->header(['Age' => 12, 'Allow' => 'GET, HEAD']);
        $headers += ['Age' => 12, 'Allow' => 'GET, HEAD'];
        $this->assertEquals($headers, $response->header());

        // String headers are allowed
        $response->header('Content-Language: da');
        $headers += ['Content-Language' => 'da'];
        $this->assertEquals($headers, $response->header());

        $response->header('Content-Language: da');
        $headers += ['Content-Language' => 'da'];
        $this->assertEquals($headers, $response->header());

        $response->header(['Content-Encoding: gzip', 'Vary: *', 'Pragma' => 'no-cache']);
        $headers += ['Content-Encoding' => 'gzip', 'Vary' => '*', 'Pragma' => 'no-cache'];
        $this->assertEquals($headers, $response->header());

        $response->header('Access-Control-Allow-Origin', ['domain1', 'domain2']);
        $headers += ['Access-Control-Allow-Origin' => ['domain1', 'domain2']];
        $this->assertEquals($headers, $response->header());
    }

    /**
     * Tests the send method
     *
     * @return void
     */
    public function testSend()
    {
        $response = $this->getMockBuilder('Cake\Http\Response')
            ->setMethods(['_sendHeader', '_sendContent'])
            ->getMock();
        $response->header([
            'Content-Language' => 'es',
            'WWW-Authenticate' => 'Negotiate',
            'Access-Control-Allow-Origin' => ['domain1', 'domain2'],
        ]);
        $response->cookie(['name' => 'thing', 'value' => 'value']);
        $response->body('the response body');

        $response->expects($this->once())->method('_sendContent')->with('the response body');
        $response->expects($this->at(0))
            ->method('_sendHeader')->with('HTTP/1.1 200 OK');
        $response->expects($this->at(1))
            ->method('_sendHeader')->with('Content-Type', 'text/html; charset=UTF-8');
        $response->expects($this->at(2))
            ->method('_sendHeader')->with('Content-Language', 'es');
        $response->expects($this->at(3))
            ->method('_sendHeader')->with('WWW-Authenticate', 'Negotiate');
        $response->expects($this->at(4))
            ->method('_sendHeader')->with('Access-Control-Allow-Origin', 'domain1');
        $response->expects($this->at(5))
            ->method('_sendHeader')->with('Access-Control-Allow-Origin', 'domain2');
        $response->send();

        $this->assertCount(1, $GLOBALS['mockedCookies']);
        $this->assertSame('value', $GLOBALS['mockedCookies'][0]['value']);
        $this->assertSame('thing', $GLOBALS['mockedCookies'][0]['name']);
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
     * Tests the send method and changing the content type
     *
     * @dataProvider charsetTypeProvider
     * @return void
     */
    public function testSendChangingContentType($original, $expected)
    {
        $response = new Response();
        $response->type($original);
        $response->body('the response body');

        $this->assertEquals($expected, $response->getHeaderLine('Content-Type'));
    }

    /**
     * Tests the send method and changing the content type to JS without adding the charset
     *
     * @return void
     */
    public function testCharsetSetContentTypeWithoutCharset()
    {
        $response = new Response();
        $response->type('js');
        $response->charset('');
        $this->assertEquals('application/javascript', $response->getHeaderLine('Content-Type'));
    }

    /**
     * Tests the send method and changing the content type
     *
     * @return void
     */
    public function testLocationSetsStatus()
    {
        $response = new Response();
        $response->location('http://www.example.com');
        $this->assertEquals(302, $response->getStatusCode());
    }

    /**
     * Test that setting certain status codes clears the status code.
     *
     * @return void
     */
    public function testStatusClearsContentType()
    {
        $response = new Response();
        $response->statusCode(204);
        $response->statusCode(304);
        $this->assertFalse($response->hasHeader('Content-Type'));
        $this->assertSame(304, $response->getStatusCode());

        $response = new Response();
        $response->type('pdf');
        $response->statusCode(204);
        $this->assertFalse($response->hasHeader('Content-Type'));
        $this->assertSame(204, $response->getStatusCode());

        $response = new Response();
        $new = $response->withType('pdf')
            ->withStatus(204);
        $this->assertFalse($new->hasHeader('Content-Type'));
        $this->assertSame(204, $new->getStatusCode());

        $response = new Response();
        $new = $response->withStatus(304)
            ->withType('pdf');
        $this->assertFalse($new->hasHeader('Content-Type'));

        $response = new Response();
        $response->statusCode(204);
        $response->type('pdf');
        $this->assertFalse($response->hasHeader('Content-Type'));
    }

    /**
     * Tests the send method and changing the content type
     *
     * @return void
     */
    public function testSendWithCallableBody()
    {
        $response = $this->getMockBuilder('Cake\Http\Response')
            ->setMethods(['_sendHeader'])
            ->getMock();
        $response->body(function () {
            echo 'the response body';
        });

        ob_start();
        $response->send();
        $this->assertEquals('the response body', ob_get_clean());
    }

    /**
     * Tests that the returned a string from a body callable is also sent
     * as the response body
     *
     * @return void
     */
    public function testSendWithCallableBodyWithReturn()
    {
        $response = $this->getMockBuilder('Cake\Http\Response')
            ->setMethods(['_sendHeader'])
            ->getMock();
        $response->body(function () {
            return 'the response body';
        });

        ob_start();
        $response->send();
        $this->assertEquals('the response body', ob_get_clean());
    }

    /**
     * Tests that callable strings are not triggered
     *
     * @return void
     */
    public function testSendWithCallableStringBody()
    {
        $response = $this->getMockBuilder('Cake\Http\Response')
            ->setMethods(['_sendHeader'])
            ->getMock();
        $response->body('phpversion');

        ob_start();
        $response->send();
        $this->assertEquals('phpversion', ob_get_clean());
    }

    /**
     * Tests the disableCache method
     *
     * @return void
     */
    public function testDisableCache()
    {
        $response = new Response();
        $expected = [
            'Expires' => 'Mon, 26 Jul 1997 05:00:00 GMT',
            'Last-Modified' => gmdate('D, d M Y H:i:s') . ' GMT',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0',
            'Content-Type' => 'text/html; charset=UTF-8',
        ];
        $response->disableCache();
        $this->assertEquals($expected, $response->header());
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
     * Tests the cache method
     *
     * @return void
     */
    public function testCache()
    {
        $response = new Response();
        $since = time();
        $time = new \DateTime('+1 day', new \DateTimeZone('UTC'));
        $response->expires('+1 day');
        $expected = [
            'Date' => gmdate('D, j M Y G:i:s ', $since) . 'GMT',
            'Last-Modified' => gmdate('D, j M Y H:i:s ', $since) . 'GMT',
            'Expires' => $time->format('D, j M Y H:i:s') . ' GMT',
            'Cache-Control' => 'public, max-age=' . ($time->format('U') - time()),
            'Content-Type' => 'text/html; charset=UTF-8',
        ];
        $response->cache($since);
        $this->assertEquals($expected, $response->header());

        $response = new Response();
        $since = time();
        $time = '+5 day';
        $expected = [
            'Date' => gmdate('D, j M Y G:i:s ', $since) . 'GMT',
            'Last-Modified' => gmdate('D, j M Y H:i:s ', $since) . 'GMT',
            'Expires' => gmdate('D, j M Y H:i:s', strtotime($time)) . ' GMT',
            'Cache-Control' => 'public, max-age=' . (strtotime($time) - time()),
            'Content-Type' => 'text/html; charset=UTF-8',
        ];
        $response->cache($since, $time);
        $this->assertEquals($expected, $response->header());

        $response = new Response();
        $since = time();
        $time = time();
        $expected = [
            'Date' => gmdate('D, j M Y G:i:s ', $since) . 'GMT',
            'Last-Modified' => gmdate('D, j M Y H:i:s ', $since) . 'GMT',
            'Expires' => gmdate('D, j M Y H:i:s', $time) . ' GMT',
            'Cache-Control' => 'public, max-age=0',
            'Content-Type' => 'text/html; charset=UTF-8',
        ];
        $response->cache($since, $time);
        $this->assertEquals($expected, $response->header());
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
        $this->assertTrue(in_array('ob_gzhandler', ob_list_handlers()));

        ob_get_clean();
    }

    /**
     * Tests the httpCodes method
     *
     * @return void
     */
    public function testHttpCodes()
    {
        $this->expectException(\InvalidArgumentException::class);
        $response = new Response();
        $result = $response->httpCodes();
        $this->assertCount(65, $result);

        $result = $response->httpCodes(100);
        $expected = [100 => 'Continue'];
        $this->assertEquals($expected, $result);

        $codes = [
            381 => 'Unicorn Moved',
            555 => 'Unexpected Minotaur'
        ];

        $result = $response->httpCodes($codes);
        $this->assertTrue($result);
        $this->assertCount(67, $response->httpCodes());

        $result = $response->httpCodes(381);
        $expected = [381 => 'Unicorn Moved'];
        $this->assertEquals($expected, $result);

        $codes = [404 => 'Sorry Bro'];
        $result = $response->httpCodes($codes);
        $this->assertTrue($result);
        $this->assertCount(67, $response->httpCodes());

        $result = $response->httpCodes(404);
        $expected = [404 => 'Sorry Bro'];
        $this->assertEquals($expected, $result);

        //Throws exception
        $response->httpCodes([
            0 => 'Nothing Here',
            -1 => 'Reverse Infinity',
            12345 => 'Universal Password',
            'Hello' => 'World'
        ]);
    }

    /**
     * Tests the download method
     *
     * @return void
     */
    public function testDownload()
    {
        $response = new Response();
        $expected = [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="myfile.mp3"'
        ];
        $response->download('myfile.mp3');
        $this->assertEquals($expected, $response->header());
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
     * Tests getting/setting the protocol
     *
     * @return void
     */
    public function testProtocol()
    {
        $response = $this->getMockBuilder('Cake\Http\Response')
            ->setMethods(['_sendHeader', '_sendContent'])
            ->getMock();
        $response->protocol('HTTP/1.0');
        $this->assertEquals('HTTP/1.0', $response->protocol());
        $response->expects($this->at(0))
            ->method('_sendHeader')->with('HTTP/1.0 200 OK');
        $response->send();
    }

    /**
     * Tests getting/setting the Content-Length
     *
     * @return void
     */
    public function testLength()
    {
        $response = new Response();
        $response->length(100);
        $this->assertEquals(100, $response->length());
        $this->assertEquals('100', $response->getHeaderLine('Content-Length'));
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
        $this->assertSame('100', $new->length(), 'new method is compat with old.');
    }

    /**
     * Tests setting the expiration date
     *
     * @return void
     */
    public function testExpires()
    {
        $format = 'D, j M Y H:i:s';
        $response = new Response();
        $now = new \DateTime('now', new \DateTimeZone('America/Los_Angeles'));
        $response->expires($now);
        $now->setTimeZone(new \DateTimeZone('UTC'));
        $this->assertEquals($now->format($format) . ' GMT', $response->expires());
        $this->assertEquals($now->format($format) . ' GMT', $response->getHeaderLine('Expires'));

        $now = time();
        $response = new Response();
        $response->expires($now);
        $this->assertEquals(gmdate($format) . ' GMT', $response->expires());
        $this->assertEquals(gmdate($format) . ' GMT', $response->getHeaderLine('Expires'));

        $response = new Response();
        $time = new \DateTime('+1 day', new \DateTimeZone('UTC'));
        $response->expires('+1 day');
        $this->assertEquals($time->format($format) . ' GMT', $response->expires());
        $this->assertEquals($time->format($format) . ' GMT', $response->getHeaderLine('Expires'));
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
     * Tests setting the modification date
     *
     * @return void
     */
    public function testModified()
    {
        $format = 'D, j M Y H:i:s';
        $response = new Response();
        $now = new \DateTime('now', new \DateTimeZone('America/Los_Angeles'));
        $response->modified($now);
        $now->setTimeZone(new \DateTimeZone('UTC'));
        $this->assertEquals($now->format($format) . ' GMT', $response->modified());
        $this->assertEquals($now->format($format) . ' GMT', $response->getHeaderLine('Last-Modified'));

        $response = new Response();
        $now = time();
        $response->modified($now);
        $this->assertEquals(gmdate($format) . ' GMT', $response->modified());
        $this->assertEquals(gmdate($format) . ' GMT', $response->getHeaderLine('Last-Modified'));

        $response = new Response();
        $time = new \DateTime('+1 day', new \DateTimeZone('UTC'));
        $response->modified('+1 day');
        $this->assertEquals($time->format($format) . ' GMT', $response->modified());
        $this->assertEquals($time->format($format) . ' GMT', $response->getHeaderLine('Last-Modified'));
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
     * Tests setting of public/private Cache-Control directives
     *
     * @return void
     */
    public function testSharable()
    {
        $response = new Response();
        $this->assertNull($response->sharable());
        $response->sharable(true);
        $this->assertTrue($response->sharable());
        $this->assertEquals('public', $response->getHeaderLine('Cache-Control'));

        $response = new Response();
        $response->sharable(false);
        $this->assertFalse($response->sharable());
        $this->assertEquals('private', $response->getHeaderLine('Cache-Control'));

        $response = new Response();
        $response->sharable(true, 3600);
        $this->assertEquals('public, max-age=3600', $response->getHeaderLine('Cache-Control'));

        $response = new Response();
        $response->sharable(false, 3600);
        $this->assertEquals('private, max-age=3600', $response->getHeaderLine('Cache-Control'));
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
     * Tests setting of max-age Cache-Control directive
     *
     * @return void
     */
    public function testMaxAge()
    {
        $response = new Response();
        $this->assertNull($response->maxAge());
        $response->maxAge(3600);
        $this->assertEquals(3600, $response->maxAge());
        $this->assertEquals('max-age=3600', $response->getHeaderLine('Cache-Control'));

        $response = new Response();
        $response->maxAge(3600);
        $response->sharable(false);
        $this->assertEquals('max-age=3600, private', $response->getHeaderLine('Cache-Control'));
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
    public function testSharedMaxAge()
    {
        $response = new Response();
        $this->assertNull($response->maxAge());
        $response->sharedMaxAge(3600);
        $this->assertEquals(3600, $response->sharedMaxAge());
        $this->assertEquals('s-maxage=3600', $response->getHeaderLine('Cache-Control'));

        $response = new Response();
        $response->sharedMaxAge(3600);
        $response->sharable(true);
        $this->assertEquals('s-maxage=3600, public', $response->getHeaderLine('Cache-Control'));
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
    public function testMustRevalidate()
    {
        $response = new Response();
        $this->assertFalse($response->mustRevalidate());

        $response->mustRevalidate(true);
        $this->assertTrue($response->mustRevalidate());
        $this->assertEquals('must-revalidate', $response->getHeaderLine('Cache-Control'));

        $response->mustRevalidate(false);
        $this->assertFalse($response->mustRevalidate());

        $response = new Response();
        $response->sharedMaxAge(3600);
        $response->mustRevalidate(true);
        $this->assertEquals('s-maxage=3600, must-revalidate', $response->getHeaderLine('Cache-Control'));
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
     * Tests getting/setting the Vary header
     *
     * @return void
     */
    public function testVary()
    {
        $response = new Response();
        $response->vary('Accept-encoding');
        $this->assertEquals('Accept-encoding', $response->getHeaderLine('vary'));

        $response = new Response();
        $response->vary(['Accept-language', 'Accept-encoding']);
        $this->assertEquals(['Accept-language', 'Accept-encoding'], $response->vary());
        $this->assertEquals('Accept-language, Accept-encoding', $response->getHeaderLine('vary'));
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
     * Tests getting/setting the Etag header
     *
     * @return void
     */
    public function testEtag()
    {
        $response = new Response();
        $response->etag('something');
        $this->assertEquals('"something"', $response->etag());
        $this->assertEquals('"something"', $response->getHeaderLine('Etag'));

        $response = new Response();
        $response->etag('something', true);
        $this->assertEquals('W/"something"', $response->etag());
        $this->assertEquals('W/"something"', $response->getHeaderLine('Etag'));
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
        $response = $this->getMockBuilder('Cake\Http\Response')
            ->setMethods(['_sendHeader', '_sendContent'])
            ->getMock();
        $response->body('something');
        $response->statusCode(200);
        $response->length(100);
        $response->modified('now');
        $response->notModified();

        $this->assertEmpty($response->header());
        $this->assertEmpty($response->body());
        $this->assertEquals(304, $response->statusCode());
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
        $_SERVER['HTTP_IF_NONE_MATCH'] = '*';
        $response = $this->getMockBuilder('Cake\Http\Response')
            ->setMethods(['notModified'])
            ->getMock();
        $response->etag('something');
        $response->expects($this->once())->method('notModified');
        $response->checkNotModified(new ServerRequest);
    }

    /**
     * Test checkNotModified method
     *
     * @return void
     */
    public function testCheckNotModifiedByEtagExact()
    {
        $_SERVER['HTTP_IF_NONE_MATCH'] = 'W/"something", "other"';
        $response = $this->getMockBuilder('Cake\Http\Response')
            ->setMethods(['notModified'])
            ->getMock();
        $response->etag('something', true);
        $response->expects($this->once())->method('notModified');
        $this->assertTrue($response->checkNotModified(new ServerRequest));
    }

    /**
     * Test checkNotModified method
     *
     * @return void
     */
    public function testCheckNotModifiedByEtagAndTime()
    {
        $_SERVER['HTTP_IF_NONE_MATCH'] = 'W/"something", "other"';
        $_SERVER['HTTP_IF_MODIFIED_SINCE'] = '2012-01-01 00:00:00';
        $response = $this->getMockBuilder('Cake\Http\Response')
            ->setMethods(['notModified'])
            ->getMock();
        $response->etag('something', true);
        $response->modified('2012-01-01 00:00:00');
        $response->expects($this->once())->method('notModified');
        $this->assertTrue($response->checkNotModified(new ServerRequest));
    }

    /**
     * Test checkNotModified method
     *
     * @return void
     */
    public function testCheckNotModifiedByEtagAndTimeMismatch()
    {
        $_SERVER['HTTP_IF_NONE_MATCH'] = 'W/"something", "other"';
        $_SERVER['HTTP_IF_MODIFIED_SINCE'] = '2012-01-01 00:00:00';
        $response = $this->getMockBuilder('Cake\Http\Response')
            ->setMethods(['notModified'])
            ->getMock();
        $response->etag('something', true);
        $response->modified('2012-01-01 00:00:01');
        $response->expects($this->never())->method('notModified');
        $this->assertFalse($response->checkNotModified(new ServerRequest));
    }

    /**
     * Test checkNotModified method
     *
     * @return void
     */
    public function testCheckNotModifiedByEtagMismatch()
    {
        $_SERVER['HTTP_IF_NONE_MATCH'] = 'W/"something-else", "other"';
        $_SERVER['HTTP_IF_MODIFIED_SINCE'] = '2012-01-01 00:00:00';
        $response = $this->getMockBuilder('Cake\Http\Response')
            ->setMethods(['notModified'])
            ->getMock();
        $response->etag('something', true);
        $response->modified('2012-01-01 00:00:00');
        $response->expects($this->never())->method('notModified');
        $this->assertFalse($response->checkNotModified(new ServerRequest));
    }

    /**
     * Test checkNotModified method
     *
     * @return void
     */
    public function testCheckNotModifiedByTime()
    {
        $_SERVER['HTTP_IF_MODIFIED_SINCE'] = '2012-01-01 00:00:00';
        $response = $this->getMockBuilder('Cake\Http\Response')
            ->setMethods(['notModified'])
            ->getMock();
        $response->modified('2012-01-01 00:00:00');
        $response->expects($this->once())->method('notModified');
        $this->assertTrue($response->checkNotModified(new ServerRequest));
    }

    /**
     * Test checkNotModified method
     *
     * @return void
     */
    public function testCheckNotModifiedNoHints()
    {
        $_SERVER['HTTP_IF_NONE_MATCH'] = 'W/"something", "other"';
        $_SERVER['HTTP_IF_MODIFIED_SINCE'] = '2012-01-01 00:00:00';
        $response = $this->getMockBuilder('Cake\Http\Response')
            ->setMethods(['notModified'])
            ->getMock();
        $response->expects($this->never())->method('notModified');
        $this->assertFalse($response->checkNotModified(new ServerRequest));
    }

    /**
     * Test cookie setting
     *
     * @return void
     */
    public function testCookieSettings()
    {
        $response = new Response();
        $cookie = [
            'name' => 'CakeTestCookie[Testing]'
        ];
        $response->cookie($cookie);
        $expected = [
            'name' => 'CakeTestCookie[Testing]',
            'value' => '',
            'expire' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => false,
            'httpOnly' => false
        ];
        $result = $response->cookie('CakeTestCookie[Testing]');
        $this->assertEquals($expected, $result);

        $cookie = [
            'name' => 'CakeTestCookie[Testing2]',
            'value' => '[a,b,c]',
            'expire' => 1000,
            'path' => '/test',
            'secure' => true
        ];
        $response->cookie($cookie);
        $expected = [
            'CakeTestCookie[Testing]' => [
                'name' => 'CakeTestCookie[Testing]',
                'value' => '',
                'expire' => 0,
                'path' => '/',
                'domain' => '',
                'secure' => false,
                'httpOnly' => false
            ],
            'CakeTestCookie[Testing2]' => [
                'name' => 'CakeTestCookie[Testing2]',
                'value' => '[a,b,c]',
                'expire' => 1000,
                'path' => '/test',
                'domain' => '',
                'secure' => true,
                'httpOnly' => false
            ]
        ];

        $result = $response->cookie();
        $this->assertEquals($expected, $result);

        $cookie = $expected['CakeTestCookie[Testing]'];
        $cookie['value'] = 'test';
        $response->cookie($cookie);
        $expected = [
            'CakeTestCookie[Testing]' => [
                'name' => 'CakeTestCookie[Testing]',
                'value' => 'test',
                'expire' => 0,
                'path' => '/',
                'domain' => '',
                'secure' => false,
                'httpOnly' => false
            ],
            'CakeTestCookie[Testing2]' => [
                'name' => 'CakeTestCookie[Testing2]',
                'value' => '[a,b,c]',
                'expire' => 1000,
                'path' => '/test',
                'domain' => '',
                'secure' => true,
                'httpOnly' => false
            ]
        ];

        $result = $response->cookie();
        $this->assertEquals($expected, $result);
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
            'secure' => true
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
            'httpOnly' => false
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
                'httpOnly' => false
            ],
            'test2' => [
                'name' => 'test2',
                'value' => 'b',
                'expire' => null,
                'path' => '/test',
                'domain' => '',
                'secure' => true,
                'httpOnly' => false
            ]
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
                'httpOnly' => true
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
     * Test CORS
     *
     * @dataProvider corsData
     * @param Request $request
     * @param string $origin
     * @param string|array $domains
     * @param string|array $methods
     * @param string|array $headers
     * @param string|bool $expectedOrigin
     * @param string|bool $expectedMethods
     * @param string|bool $expectedHeaders
     * @return void
     */
    public function testCors($request, $origin, $domains, $methods, $headers, $expectedOrigin, $expectedMethods = false, $expectedHeaders = false)
    {
        $request->env('HTTP_ORIGIN', $origin);
        $response = new Response();

        $result = $response->cors($request, $domains, $methods, $headers);
        $this->assertInstanceOf('Cake\Network\CorsBuilder', $result);

        $headers = $response->header();
        if ($expectedOrigin) {
            $this->assertArrayHasKey('Access-Control-Allow-Origin', $headers);
            $this->assertEquals($expectedOrigin, $headers['Access-Control-Allow-Origin']);
        }
        if ($expectedMethods) {
            $this->assertArrayHasKey('Access-Control-Allow-Methods', $headers);
            $this->assertEquals($expectedMethods, $headers['Access-Control-Allow-Methods']);
        }
        if ($expectedHeaders) {
            $this->assertArrayHasKey('Access-Control-Allow-Headers', $headers);
            $this->assertEquals($expectedHeaders, $headers['Access-Control-Allow-Headers']);
        }
        unset($_SERVER['HTTP_ORIGIN']);
    }

    /**
     * Feed for testCors
     *
     * @return array
     */
    public function corsData()
    {
        $fooRequest = new ServerRequest();

        $secureRequest = function () {
            $secureRequest = $this->getMockBuilder('Cake\Http\ServerRequest')
                ->setMethods(['is'])
                ->getMock();
            $secureRequest->expects($this->any())
                ->method('is')
                ->with('ssl')
                ->will($this->returnValue(true));

            return $secureRequest;
        };

        return [
            [$fooRequest, null, '*', '', '', false, false],
            [$fooRequest, 'http://www.foo.com', '*', '', '', '*', false],
            [$fooRequest, 'http://www.foo.com', 'www.foo.com', '', '', 'http://www.foo.com', false],
            [$fooRequest, 'http://www.foo.com', '*.foo.com', '', '', 'http://www.foo.com', false],
            [$fooRequest, 'http://www.foo.com', 'http://*.foo.com', '', '', 'http://www.foo.com', false],
            [$fooRequest, 'http://www.foo.com', 'https://www.foo.com', '', '', false, false],
            [$fooRequest, 'http://www.foo.com', 'https://*.foo.com', '', '', false, false],
            [$fooRequest, 'http://www.foo.com', ['*.bar.com', '*.foo.com'], '', '', 'http://www.foo.com', false],

            [$fooRequest, 'http://not-foo.com', '*.foo.com', '', '', false, false],
            [$fooRequest, 'http://bad.academy', '*.acad.my', '', '', false, false],
            [$fooRequest, 'http://www.foo.com.at.bad.com', '*.foo.com', '', '', false, false],
            [$fooRequest, 'https://www.foo.com', '*.foo.com', '', '', false, false],

            [$secureRequest(), 'https://www.bar.com', 'www.bar.com', '', '', 'https://www.bar.com', false],
            [$secureRequest(), 'https://www.bar.com', 'http://www.bar.com', '', '', false, false],
            [$secureRequest(), 'https://www.bar.com', '*.bar.com', '', '', 'https://www.bar.com', false],
            [$secureRequest(), 'http://www.bar.com', '*.bar.com', '', '', false, false],

            [$fooRequest, 'http://www.foo.com', '*', 'GET', '', '*', 'GET'],
            [$fooRequest, 'http://www.foo.com', '*.foo.com', 'GET', '', 'http://www.foo.com', 'GET'],
            [$fooRequest, 'http://www.foo.com', '*.foo.com', ['GET', 'POST'], '', 'http://www.foo.com', 'GET, POST'],

            [$fooRequest, 'http://www.foo.com', '*', '', 'X-CakePHP', '*', false, 'X-CakePHP'],
            [$fooRequest, 'http://www.foo.com', '*', '', ['X-CakePHP', 'X-MyApp'], '*', false, 'X-CakePHP, X-MyApp'],
            [$fooRequest, 'http://www.foo.com', '*', ['GET', 'OPTIONS'], ['X-CakePHP', 'X-MyApp'], '*', 'GET, OPTIONS', 'X-CakePHP, X-MyApp'],
        ];
    }

    /**
     * testFileNotFound
     *
     * @return void
     */
    public function testFileNotFound()
    {
        $this->expectException(\Cake\Network\Exception\NotFoundException::class);
        $response = new Response();
        $response->file('/some/missing/folder/file.jpg');
    }

    /**
     * test withFile() not found
     *
     * @return void
     */
    public function testWithFileNotFound()
    {
        $this->expectException(\Cake\Network\Exception\NotFoundException::class);
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
     * test invalid file paths.
     *
     * @dataProvider invalidFileProvider
     * @return void
     */
    public function testFileInvalidPath($path, $expectedMessage)
    {
        $response = new Response();
        try {
            $response->file($path);
        } catch (NotFoundException $e) {
            $this->assertContains($expectedMessage, $e->getMessage());
        }
    }

    /**
     * test withFile and invalid paths
     *
     * @dataProvider invalidFileProvider
     * @return void
     */
    public function testWithFileInvalidPath($path, $expectedMessage)
    {
        $response = new Response();
        try {
            $response->withFile($path);
        } catch (NotFoundException $e) {
            $this->assertContains($expectedMessage, $e->getMessage());
        }
    }

    /**
     * testFile method
     *
     * @return void
     */
    public function testFile()
    {
        $response = $this->getMockBuilder('Cake\Http\Response')
            ->setMethods([
                'header',
                'type',
                '_sendHeader',
                '_setContentType',
                '_isActive',
            ])
            ->getMock();

        $response->expects($this->exactly(1))
            ->method('type')
            ->with('css')
            ->will($this->returnArgument(0));

        $response->expects($this->at(1))
            ->method('header')
            ->with('Accept-Ranges', 'bytes');

        $response->expects($this->exactly(1))
            ->method('_isActive')
            ->will($this->returnValue(true));

        $response->file(TEST_APP . 'vendor/css/test_asset.css');

        ob_start();
        $result = $response->send();
        $this->assertTrue($result !== false);
        $output = ob_get_clean();

        $expected = '/* this is the test asset css file */';
        $this->assertEquals($expected, trim($output));
        $this->assertEquals($expected, trim($response->getBody()->getContents()));
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
     * testFileWithDownloadAndName
     *
     * @return void
     */
    public function testFileWithDownloadAndName()
    {
        $response = $this->getMockBuilder('Cake\Http\Response')
            ->setMethods([
                'header',
                'type',
                'download',
                '_sendHeader',
                '_setContentType',
                '_isActive',
            ])
            ->getMock();

        $response->expects($this->exactly(1))
            ->method('type')
            ->with('css')
            ->will($this->returnArgument(0));

        $response->expects($this->once())
            ->method('download')
            ->with('something_special.css');

        $response->expects($this->at(2))
            ->method('header')
            ->with('Content-Transfer-Encoding', 'binary');

        $response->expects($this->at(3))
            ->method('header')
            ->with('Accept-Ranges', 'bytes');

        $response->expects($this->exactly(1))
            ->method('_isActive')
            ->will($this->returnValue(true));

        $response->file(
            TEST_APP . 'vendor' . DS . 'css' . DS . 'test_asset.css',
            [
                'name' => 'something_special.css',
                'download' => true,
            ]
        );

        ob_start();
        $result = $response->send();
        $output = ob_get_clean();
        $this->assertEquals("/* this is the test asset css file */\n", $output);
        $this->assertNotSame(false, $result);
    }

    /**
     * testFileWithUnknownFileTypeGeneric method
     *
     * @return void
     */
    public function testFileWithUnknownFileTypeGeneric()
    {
        $currentUserAgent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null;
        $_SERVER['HTTP_USER_AGENT'] = 'Some generic browser';

        $response = $this->getMockBuilder('Cake\Http\Response')
            ->setMethods([
                'header',
                'type',
                'download',
                '_sendHeader',
                '_setContentType',
                '_isActive',
            ])
            ->getMock();

        $response->expects($this->exactly(1))
            ->method('type')
            ->with('ini')
            ->will($this->returnValue(false));

        $response->expects($this->once())
            ->method('download')
            ->with('no_section.ini');

        $response->expects($this->at(2))
            ->method('header')
            ->with('Content-Transfer-Encoding', 'binary');

        $response->expects($this->at(3))
            ->method('header')
            ->with('Accept-Ranges', 'bytes');

        $response->expects($this->exactly(1))
            ->method('_isActive')
            ->will($this->returnValue(true));

        $response->file(CONFIG . 'no_section.ini');

        ob_start();
        $result = $response->send();
        $output = ob_get_clean();
        $this->assertEquals("some_key = some_value\nbool_key = 1\n", $output);
        $this->assertNotSame(false, $result);
        if ($currentUserAgent !== null) {
            $_SERVER['HTTP_USER_AGENT'] = $currentUserAgent;
        }
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
     * testFileWithUnknownFileTypeOpera method
     *
     * @return void
     */
    public function testFileWithUnknownFileTypeOpera()
    {
        $currentUserAgent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null;
        $_SERVER['HTTP_USER_AGENT'] = 'Opera/9.80 (Windows NT 6.0; U; en) Presto/2.8.99 Version/11.10';

        $response = $this->getMockBuilder('Cake\Http\Response')
            ->setMethods([
                'header',
                'type',
                'download',
                '_sendHeader',
                '_setContentType',
                '_isActive',
            ])
            ->getMock();

        $response->expects($this->at(0))
            ->method('type')
            ->with('ini')
            ->will($this->returnValue(false));

        $response->expects($this->at(1))
            ->method('type')
            ->with('application/octet-stream')
            ->will($this->returnValue(false));

        $response->expects($this->once())
            ->method('download')
            ->with('no_section.ini');

        $response->expects($this->at(3))
            ->method('header')
            ->with('Content-Transfer-Encoding', 'binary');

        $response->expects($this->at(4))
            ->method('header')
            ->with('Accept-Ranges', 'bytes');

        $response->expects($this->exactly(1))
            ->method('_isActive')
            ->will($this->returnValue(true));

        $response->file(CONFIG . 'no_section.ini');

        ob_start();
        $result = $response->send();
        $output = ob_get_clean();
        $this->assertEquals("some_key = some_value\nbool_key = 1\n", $output);
        $this->assertNotSame(false, $result);
        if ($currentUserAgent !== null) {
            $_SERVER['HTTP_USER_AGENT'] = $currentUserAgent;
        }
    }

    /**
     * test withFile() + opera
     *
     * @return void
     */
    public function testWithFileUnknownFileTypeOpera()
    {
        $currentUserAgent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null;
        $_SERVER['HTTP_USER_AGENT'] = 'Opera/9.80 (Windows NT 6.0; U; en) Presto/2.8.99 Version/11.10';
        $response = new Response();

        $new = $response->withFile(CONFIG . 'no_section.ini');
        $this->assertEquals('application/octet-stream', $new->getHeaderLine('Content-Type'));
        $this->assertEquals(
            'attachment; filename="no_section.ini"',
            $new->getHeaderLine('Content-Disposition')
        );

        $_SERVER['HTTP_USER_AGENT'] = $currentUserAgent;
    }

    /**
     * testFileWithUnknownFileTypeIE method
     *
     * @return void
     */
    public function testFileWithUnknownFileTypeIE()
    {
        $currentUserAgent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null;
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (compatible; MSIE 8.0; Windows NT 5.2; Trident/4.0; Media Center PC 4.0; SLCC1; .NET CLR 3.0.04320)';

        $response = $this->getMockBuilder('Cake\Http\Response')
            ->setMethods([
                'header',
                'type',
                'download',
                '_sendHeader',
                '_setContentType',
                '_isActive',
            ])
            ->getMock();

        $response->expects($this->at(0))
            ->method('type')
            ->with('ini')
            ->will($this->returnValue(false));

        $response->expects($this->at(1))
            ->method('type')
            ->with('application/force-download')
            ->will($this->returnValue(false));

        $response->expects($this->once())
            ->method('download')
            ->with('config.ini');

        $response->expects($this->at(3))
            ->method('header')
            ->with('Content-Transfer-Encoding', 'binary');

        $response->expects($this->at(4))
            ->method('header')
            ->with('Accept-Ranges', 'bytes');

        $response->expects($this->exactly(1))
            ->method('_isActive')
            ->will($this->returnValue(true));

        $response->file(CONFIG . 'no_section.ini', [
            'name' => 'config.ini'
        ]);

        ob_start();
        $result = $response->send();
        $output = ob_get_clean();
        $this->assertEquals("some_key = some_value\nbool_key = 1\n", $output);
        $this->assertNotSame(false, $result);
        if ($currentUserAgent !== null) {
            $_SERVER['HTTP_USER_AGENT'] = $currentUserAgent;
        }
    }

    /**
     * test withFile() + old IE
     *
     * @return void
     */
    public function testWithFileUnknownFileTypeOldIe()
    {
        $currentUserAgent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null;
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (compatible; MSIE 8.0; Windows NT 5.2; Trident/4.0; Media Center PC 4.0; SLCC1; .NET CLR 3.0.04320)';
        $response = new Response();

        $new = $response->withFile(CONFIG . 'no_section.ini');
        $this->assertEquals('application/force-download', $new->getHeaderLine('Content-Type'));

        $_SERVER['HTTP_USER_AGENT'] = $currentUserAgent;
    }

    /**
     * testFileWithUnknownFileNoDownload method
     *
     * @return void
     */
    public function testFileWithUnknownFileNoDownload()
    {
        $currentUserAgent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null;
        $_SERVER['HTTP_USER_AGENT'] = 'Some generic browser';

        $response = $this->getMockBuilder('Cake\Http\Response')
            ->setMethods([
                'header',
                'type',
                'download',
                '_sendHeader',
                '_setContentType',
                '_isActive',
            ])
            ->getMock();

        $response->expects($this->exactly(1))
            ->method('type')
            ->with('ini')
            ->will($this->returnValue(false));

        $response->expects($this->at(1))
            ->method('header')
            ->with('Accept-Ranges', 'bytes');

        $response->expects($this->never())
            ->method('download');

        $response->file(CONFIG . 'no_section.ini', [
            'download' => false
        ]);

        if ($currentUserAgent !== null) {
            $_SERVER['HTTP_USER_AGENT'] = $currentUserAgent;
        }
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
            'download' => false
        ]);
        $this->assertEquals(
            'text/html; charset=UTF-8',
            $new->getHeaderLine('Content-Type')
        );
        $this->assertFalse($new->hasHeader('Content-Disposition'));
        $this->assertFalse($new->hasHeader('Content-Transfer-Encoding'));
    }

    /**
     * test getFile method
     *
     * @return void
     */
    public function testGetFile()
    {
        $response = new Response();
        $this->assertNull($response->getFile(), 'No file to get');

        $response->file(TEST_APP . 'vendor/css/test_asset.css');
        $file = $response->getFile();
        $this->assertInstanceOf('Cake\Filesystem\File', $file, 'Should get a file');
        $this->assertPathEquals(TEST_APP . 'vendor' . DS . 'css' . DS . 'test_asset.css', $file->path);
    }

    /**
     * testConnectionAbortedOnBuffering method
     *
     * @return void
     */
    public function testConnectionAbortedOnBuffering()
    {
        $response = $this->getMockBuilder('Cake\Http\Response')
            ->setMethods([
                'header',
                'type',
                'download',
                '_sendHeader',
                '_setContentType',
                '_isActive',
            ])
            ->getMock();

        $response->expects($this->any())
            ->method('type')
            ->with('css')
            ->will($this->returnArgument(0));

        $response->expects($this->at(0))
            ->method('_isActive')
            ->will($this->returnValue(false));

        $response->file(TEST_APP . 'vendor/css/test_asset.css');

        $result = $response->send();
        $this->assertNull($result);
    }

    /**
     * Test downloading files with UPPERCASE extensions.
     *
     * @return void
     */
    public function testFileUpperExtension()
    {
        $response = $this->getMockBuilder('Cake\Http\Response')
            ->setMethods([
                'header',
                'type',
                'download',
                '_sendHeader',
                '_setContentType',
                '_isActive',
            ])
            ->getMock();

        $response->expects($this->any())
            ->method('type')
            ->with('jpg')
            ->will($this->returnArgument(0));

        $response->expects($this->at(0))
            ->method('_isActive')
            ->will($this->returnValue(true));

        $response->file(TEST_APP . 'vendor/img/test_2.JPG');
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
     * Test downloading files with extension not explicitly set.
     *
     * @return void
     */
    public function testFileExtensionNotSet()
    {
        $response = $this->getMockBuilder('Cake\Http\Response')
            ->setMethods([
                'header',
                'type',
                'download',
                '_sendHeader',
                '_setContentType',
                '_isActive',
            ])
            ->getMock();

        $response->expects($this->any())
            ->method('type')
            ->with('jpg')
            ->will($this->returnArgument(0));

        $response->expects($this->at(0))
            ->method('_isActive')
            ->will($this->returnValue(true));

        $response->file(TEST_APP . 'vendor/img/test_2.JPG');
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
                'bytes=-25', 25, 'bytes 13-37/38'
            ],

            [
                'bytes=0-', 38, 'bytes 0-37/38'
            ],

            [
                'bytes=10-', 28, 'bytes 10-37/38'
            ],

            [
                'bytes=10-20', 11, 'bytes 10-20/38'
            ],

            // Spaced out
            [
                'bytes = 10 - 20', 11, 'bytes 10-20/38'
            ],
        ];
    }

    /**
     * Test the various range offset types.
     *
     * @dataProvider rangeProvider
     * @return void
     */
    public function testFileRangeOffsets($range, $length, $offsetResponse)
    {
        $_SERVER['HTTP_RANGE'] = $range;
        $response = $this->getMockBuilder('Cake\Http\Response')
            ->setMethods([
                'header',
                'type',
                '_sendHeader',
                '_setContentType',
                '_isActive',
            ])
            ->getMock();

        $response->expects($this->at(1))
            ->method('header')
            ->with('Content-Disposition', 'attachment; filename="test_asset.css"');

        $response->expects($this->at(2))
            ->method('header')
            ->with('Content-Transfer-Encoding', 'binary');

        $response->expects($this->at(3))
            ->method('header')
            ->with('Accept-Ranges', 'bytes');

        $response->expects($this->at(4))
            ->method('header')
            ->with([
                'Content-Length' => $length,
                'Content-Range' => $offsetResponse,
            ]);

        $response->expects($this->any())
            ->method('_isActive')
            ->will($this->returnValue(true));

        $response->file(
            TEST_APP . 'vendor' . DS . 'css' . DS . 'test_asset.css',
            ['download' => true]
        );

        ob_start();
        $result = $response->send();
        ob_get_clean();
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
     * Test fetching ranges from a file.
     *
     * @return void
     */
    public function testFileRange()
    {
        $_SERVER['HTTP_RANGE'] = 'bytes=8-25';
        $response = $this->getMockBuilder('Cake\Http\Response')
            ->setMethods([
                'header',
                'type',
                '_sendHeader',
                '_setContentType',
                '_isActive',
            ])
            ->getMock();

        $response->expects($this->exactly(1))
            ->method('type')
            ->with('css')
            ->will($this->returnArgument(0));

        $response->expects($this->at(1))
            ->method('header')
            ->with('Content-Disposition', 'attachment; filename="test_asset.css"');

        $response->expects($this->at(2))
            ->method('header')
            ->with('Content-Transfer-Encoding', 'binary');

        $response->expects($this->at(3))
            ->method('header')
            ->with('Accept-Ranges', 'bytes');

        $response->expects($this->at(4))
            ->method('header')
            ->with([
                'Content-Length' => 18,
                'Content-Range' => 'bytes 8-25/38',
            ]);

        $response->expects($this->any())
            ->method('_isActive')
            ->will($this->returnValue(true));

        $response->file(
            TEST_APP . 'vendor' . DS . 'css' . DS . 'test_asset.css',
            ['download' => true]
        );

        ob_start();
        $result = $response->send();
        $output = ob_get_clean();
        $this->assertEquals(206, $response->statusCode());
        $this->assertEquals('is the test asset ', $output);
        $this->assertNotSame(false, $result);
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
                'bytes=0,38'
            ],

            // malformed punctuation
            [
                'bytes: 0 - 38'
            ],
        ];
    }

    /**
     * Test invalid file ranges.
     *
     * @dataProvider invalidFileRangeProvider
     * @return void
     */
    public function testFileRangeInvalid($range)
    {
        $_SERVER['HTTP_RANGE'] = $range;
        $response = $this->getMockBuilder('Cake\Http\Response')
            ->setMethods([
                '_sendHeader',
                '_isActive',
            ])
            ->getMock();

        $response->file(
            TEST_APP . 'vendor' . DS . 'css' . DS . 'test_asset.css',
            ['download' => true]
        );

        $expected = [
            'Content-Type' => 'text/css; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="test_asset.css"',
            'Content-Transfer-Encoding' => 'binary',
            'Accept-Ranges' => 'bytes',
            'Content-Range' => 'bytes 0-37/38',
            'Content-Length' => 38,
        ];
        $this->assertEquals($expected, $response->header());
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
     * Test reversed file ranges.
     *
     * @return void
     */
    public function testFileRangeReversed()
    {
        $_SERVER['HTTP_RANGE'] = 'bytes=30-2';
        $response = $this->getMockBuilder('Cake\Http\Response')
            ->setMethods([
                'header',
                'type',
                '_sendHeader',
                '_isActive',
            ])
            ->getMock();

        $response->expects($this->at(1))
            ->method('header')
            ->with('Content-Disposition', 'attachment; filename="test_asset.css"');

        $response->expects($this->at(2))
            ->method('header')
            ->with('Content-Transfer-Encoding', 'binary');

        $response->expects($this->at(3))
            ->method('header')
            ->with('Accept-Ranges', 'bytes');

        $response->expects($this->at(4))
            ->method('header')
            ->with([
                'Content-Range' => 'bytes 0-37/38',
            ]);

        $response->file(
            TEST_APP . 'vendor' . DS . 'css' . DS . 'test_asset.css',
            ['download' => true]
        );

        $this->assertEquals(416, $response->statusCode());
        $result = $response->send();
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
     * testFileRangeOffsetsNoDownload method
     *
     * @dataProvider rangeProvider
     * @return void
     */
    public function testFileRangeOffsetsNoDownload($range, $length, $offsetResponse)
    {
        $_SERVER['HTTP_RANGE'] = $range;
        $response = $this->getMockBuilder('Cake\Http\Response')
            ->setMethods([
                'header',
                'type',
                '_sendHeader',
                '_isActive',
            ])
            ->getMock();

        $response->expects($this->at(1))
            ->method('header')
            ->with('Accept-Ranges', 'bytes');

        $response->expects($this->at(2))
            ->method('header')
            ->with([
                'Content-Length' => $length,
                'Content-Range' => $offsetResponse,
            ]);

        $response->expects($this->any())
            ->method('_isActive')
            ->will($this->returnValue(true));

        $response->file(
            TEST_APP . 'vendor' . DS . 'css' . DS . 'test_asset.css',
            ['download' => false]
        );

        ob_start();
        $result = $response->send();
        ob_get_clean();
    }

    /**
     * testFileRangeNoDownload method
     *
     * @return void
     */
    public function testFileRangeNoDownload()
    {
        $_SERVER['HTTP_RANGE'] = 'bytes=8-25';
        $response = $this->getMockBuilder('Cake\Http\Response')
            ->setMethods([
                'header',
                'type',
                '_sendHeader',
                '_isActive',
            ])
            ->getMock();

        $response->expects($this->exactly(1))
            ->method('type')
            ->with('css')
            ->will($this->returnArgument(0));

        $response->expects($this->at(1))
            ->method('header')
            ->with('Accept-Ranges', 'bytes');

        $response->expects($this->at(2))
            ->method('header')
            ->with([
                'Content-Length' => 18,
                'Content-Range' => 'bytes 8-25/38',
            ]);

        $response->expects($this->any())
            ->method('_isActive')
            ->will($this->returnValue(true));

        $response->file(
            TEST_APP . 'vendor' . DS . 'css' . DS . 'test_asset.css',
            ['download' => false]
        );

        ob_start();
        $result = $response->send();
        $output = ob_get_clean();
        $this->assertEquals(206, $response->statusCode());
        $this->assertEquals('is the test asset ', $output);
        $this->assertNotSame(false, $result);
    }

    /**
     * testFileRangeInvalidNoDownload method
     *
     * @return void
     */
    public function testFileRangeInvalidNoDownload()
    {
        $_SERVER['HTTP_RANGE'] = 'bytes=30-2';
        $response = $this->getMockBuilder('Cake\Http\Response')
            ->setMethods([
                'header',
                'type',
                '_sendHeader',
                '_isActive',
            ])
            ->getMock();

        $response->expects($this->at(1))
            ->method('header')
            ->with('Accept-Ranges', 'bytes');

        $response->expects($this->at(2))
            ->method('header')
            ->with([
                'Content-Range' => 'bytes 0-37/38',
            ]);

        $response->file(
            TEST_APP . 'vendor' . DS . 'css' . DS . 'test_asset.css',
            ['download' => false]
        );

        $this->assertEquals(416, $response->statusCode());
        $result = $response->send();
    }

    /**
     * Test the location method.
     *
     * @return void
     */
    public function testLocation()
    {
        $response = new Response();
        $this->assertNull($response->location(), 'No header should be set.');
        $this->assertNull($response->location('http://example.org'), 'Setting a location should return null');
        $this->assertEquals('http://example.org', $response->location(), 'Reading a location should return the value.');
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
            'Accept' => ['application/json']
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
            'Accept' => ['application/json']
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
            'Accept' => ['application/json']
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
                'Content-Type' => ['text/html; charset=UTF-8']
            ],
            'file' => null,
            'fileRange' => [],
            'cookies' => new CookieCollection(),
            'cacheDirectives' => [],
            'body' => 'Foo'
        ];
        $this->assertEquals($expected, $result);
    }
}
