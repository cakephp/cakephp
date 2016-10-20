<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         2.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Network;

use Cake\Network\Request;
use Cake\Network\Response;
use Cake\TestSuite\TestCase;

/**
 * ResponseTest
 */
class ResponseTest extends TestCase
{

    /**
     * Setup for tests
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        include_once __DIR__ . DS . 'mocks.php';
    }

    /**
     * Cleanup after tests
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();
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
        $this->assertEquals(203, $response->statusCode());

        $options = [
            'body' => 'This is the body',
            'charset' => 'my-custom-charset',
            'type' => 'mp3',
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
     * Tests the statusCode method
     *
     * @expectedException \InvalidArgumentException
     * @return void
     */
    public function testStatusCode()
    {
        $response = new Response();
        $this->assertEquals(200, $response->statusCode());
        $response->statusCode(404);
        $this->assertEquals(404, $response->statusCode());
        $this->assertEquals(500, $response->statusCode(500));

        //Throws exception
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
     * Tests the header method
     *
     * @return void
     */
    public function testHeader()
    {
        $response = new Response();
        $headers = [];
        $this->assertEquals($headers, $response->header());

        $response->header('location', 'http://example.com');
        $headers += ['location' => 'http://example.com'];
        $this->assertEquals($headers, $response->header());

        //Headers with the same name are overwritten
        $response->header('location', 'http://example2.com');
        $headers = ['location' => 'http://example2.com'];
        $this->assertEquals($headers, $response->header());

        $response->header(['www-authenticate' => 'Negotiate']);
        $headers += ['www-authenticate' => 'Negotiate'];
        $this->assertEquals($headers, $response->header());

        $response->header(['www-authenticate' => 'Not-Negotiate']);
        $headers['www-authenticate'] = 'Not-Negotiate';
        $this->assertEquals($headers, $response->header());

        $response->header(['age' => 12, 'allow' => 'GET, HEAD']);
        $headers += ['age' => 12, 'allow' => 'GET, HEAD'];
        $this->assertEquals($headers, $response->header());

        // String headers are allowed
        $response->header('content-language: da');
        $headers += ['content-language' => 'da'];
        $this->assertEquals($headers, $response->header());

        $response->header('content-language: da');
        $headers += ['content-language' => 'da'];
        $this->assertEquals($headers, $response->header());

        $response->header(['content-encoding: gzip', 'vary: *', 'pragma' => 'no-cache']);
        $headers += ['content-encoding' => 'gzip', 'vary' => '*', 'pragma' => 'no-cache'];
        $this->assertEquals($headers, $response->header());

        $response->header('access-control-allow-origin', ['domain1', 'domain2']);
        $headers += ['access-control-allow-origin' => ['domain1', 'domain2']];
        $this->assertEquals($headers, $response->header());
    }

    /**
     * Tests the send method
     *
     * @return void
     */
    public function testSend()
    {
        $response = $this->getMockBuilder('Cake\Network\Response')
            ->setMethods(['_sendHeader', '_sendContent', '_setCookies'])
            ->getMock();
        $response->header([
            'content-language' => 'es',
            'www-authenticate' => 'Negotiate',
            'access-control-allow-origin' => ['domain1', 'domain2'],
        ]);
        $response->body('the response body');
        $response->expects($this->once())->method('_sendContent')->with('the response body');
        $response->expects($this->at(0))->method('_setCookies');
        $response->expects($this->at(1))
            ->method('_sendHeader')->with('HTTP/1.1 200 OK');
        $response->expects($this->at(2))
            ->method('_sendHeader')->with('content-language', 'es');
        $response->expects($this->at(3))
            ->method('_sendHeader')->with('www-authenticate', 'Negotiate');
        $response->expects($this->at(4))
            ->method('_sendHeader')->with('access-control-allow-origin', 'domain1');
        $response->expects($this->at(5))
            ->method('_sendHeader')->with('access-control-allow-origin', 'domain2');
        $response->expects($this->at(6))
            ->method('_sendHeader')->with('content-type', 'text/html; charset=UTF-8');
        $response->send();
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
        $response = $this->getMockBuilder('Cake\Network\Response')
            ->setMethods(['_sendHeader', '_sendContent', '_setCookies'])
            ->getMock();
        $response->type($original);
        $response->body('the response body');
        $response->expects($this->once())->method('_sendContent')->with('the response body');
        $response->expects($this->at(0))->method('_setCookies');
        $response->expects($this->at(1))
            ->method('_sendHeader')->with('HTTP/1.1 200 OK');
        $response->expects($this->at(2))
            ->method('_sendHeader')->with('content-type', $expected);
        $response->send();
    }

    /**
     * Tests the send method and changing the content type to JS without adding the charset
     *
     * @return void
     */
    public function testSendChangingContentTypeWithoutCharset()
    {
        $response = $this->getMockBuilder('Cake\Network\Response')
            ->setMethods(['_sendHeader', '_sendContent', '_setCookies'])
            ->getMock();
        $response->type('js');
        $response->charset('');

        $response->body('var $foo = "bar";');
        $response->expects($this->once())->method('_sendContent')->with('var $foo = "bar";');
        $response->expects($this->at(0))->method('_setCookies');
        $response->expects($this->at(1))
            ->method('_sendHeader')->with('HTTP/1.1 200 OK');
        $response->expects($this->at(2))
            ->method('_sendHeader')->with('content-type', 'application/javascript');
        $response->send();
    }

    /**
     * Tests the send method and changing the content type
     *
     * @return void
     */
    public function testSendWithLocation()
    {
        $response = $this->getMockBuilder('Cake\Network\Response')
            ->setMethods(['_sendHeader', '_sendContent', '_setCookies'])
            ->getMock();
        $response->header('location', 'http://www.example.com');
        $response->expects($this->at(0))->method('_setCookies');
        $response->expects($this->at(1))
            ->method('_sendHeader')->with('HTTP/1.1 302 Found');
        $response->expects($this->at(2))
            ->method('_sendHeader')->with('location', 'http://www.example.com');
        $response->expects($this->at(3))
            ->method('_sendHeader')->with('content-type', 'text/html; charset=UTF-8');
        $response->send();
    }

    /**
     * Tests the send method and changing the content type
     *
     * @return void
     */
    public function testSendWithCallableBody()
    {
        $response = $this->getMockBuilder('Cake\Network\Response')
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
        $response = $this->getMockBuilder('Cake\Network\Response')
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
     * Tests the disableCache method
     *
     * @return void
     */
    public function testDisableCache()
    {
        $response = new Response();
        $expected = [
            'expires' => 'Mon, 26 Jul 1997 05:00:00 GMT',
            'last-modified' => gmdate("D, d M Y H:i:s") . " GMT",
            'cache-control' => 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0'
        ];
        $response->disableCache();
        $this->assertEquals($expected, $response->header());
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
            'date' => gmdate("D, j M Y G:i:s ", $since) . 'GMT',
            'last-modified' => gmdate("D, j M Y H:i:s ", $since) . 'GMT',
            'expires' => $time->format('D, j M Y H:i:s') . ' GMT',
            'cache-control' => 'public, max-age=' . ($time->format('U') - time())
        ];
        $response->cache($since);
        $this->assertEquals($expected, $response->header());

        $response = new Response();
        $since = time();
        $time = '+5 day';
        $expected = [
            'date' => gmdate("D, j M Y G:i:s ", $since) . 'GMT',
            'last-modified' => gmdate("D, j M Y H:i:s ", $since) . 'GMT',
            'expires' => gmdate("D, j M Y H:i:s", strtotime($time)) . " GMT",
            'cache-control' => 'public, max-age=' . (strtotime($time) - time())
        ];
        $response->cache($since, $time);
        $this->assertEquals($expected, $response->header());

        $response = new Response();
        $since = time();
        $time = time();
        $expected = [
            'date' => gmdate("D, j M Y G:i:s ", $since) . 'GMT',
            'last-modified' => gmdate("D, j M Y H:i:s ", $since) . 'GMT',
            'expires' => gmdate("D, j M Y H:i:s", $time) . " GMT",
            'cache-control' => 'public, max-age=0'
        ];
        $response->cache($since, $time);
        $this->assertEquals($expected, $response->header());
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
        if (ini_get("zlib.output_compression") === '1' || !extension_loaded("zlib")) {
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
     * @expectedException \InvalidArgumentException
     * @return void
     */
    public function testHttpCodes()
    {
        $response = new Response();
        $result = $response->httpCodes();
        $this->assertEquals(65, count($result));

        $result = $response->httpCodes(100);
        $expected = [100 => 'Continue'];
        $this->assertEquals($expected, $result);

        $codes = [
            381 => 'Unicorn Moved',
            555 => 'Unexpected Minotaur'
        ];

        $result = $response->httpCodes($codes);
        $this->assertTrue($result);
        $this->assertEquals(67, count($response->httpCodes()));

        $result = $response->httpCodes(381);
        $expected = [381 => 'Unicorn Moved'];
        $this->assertEquals($expected, $result);

        $codes = [404 => 'Sorry Bro'];
        $result = $response->httpCodes($codes);
        $this->assertTrue($result);
        $this->assertEquals(67, count($response->httpCodes()));

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
            'content-disposition' => 'attachment; filename="myfile.mp3"'
        ];
        $response->download('myfile.mp3');
        $this->assertEquals($expected, $response->header());
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

        if (!extension_loaded("zlib")) {
            $this->markTestSkipped('Skipping further tests for outputCompressed as zlib extension is not loaded');
        }

        $this->skipIf(defined('HHVM_VERSION'), 'HHVM does not implement ob_gzhandler');

        if (ini_get("zlib.output_compression") !== '1') {
            ob_start('ob_gzhandler');
        }
        $_SERVER['HTTP_ACCEPT_ENCODING'] = 'gzip';
        $result = $response->outputCompressed();
        $this->assertTrue($result);

        $_SERVER['HTTP_ACCEPT_ENCODING'] = '';
        $result = $response->outputCompressed();
        $this->assertFalse($result);
        if (ini_get("zlib.output_compression") !== '1') {
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
        $response = $this->getMockBuilder('Cake\Network\Response')
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
        $response = $this->getMockBuilder('Cake\Network\Response')
            ->setMethods(['_sendHeader', '_sendContent'])
            ->getMock();
        $response->length(100);
        $this->assertEquals(100, $response->length());
        $response->expects($this->at(1))
            ->method('_sendHeader')->with('content-length', 100);
        $response->send();
    }

    /**
     * Tests that the response body is unset if the status code is 304 or 204
     *
     * @return void
     */
    public function testUnmodifiedContent()
    {
        $response = $this->getMockBuilder('Cake\Network\Response')
            ->setMethods(['_sendHeader', '_sendContent'])
            ->getMock();
        $response->body('This is a body');
        $response->statusCode(204);
        $response->expects($this->once())
            ->method('_sendContent')->with('');
        $response->send();
        $this->assertFalse(array_key_exists('content-type', $response->header()));

        $response = $this->getMockBuilder('Cake\Network\Response')
            ->setMethods(['_sendHeader', '_sendContent'])
            ->getMock();
        $response->body('This is a body');
        $response->statusCode(304);
        $response->expects($this->once())
            ->method('_sendContent')->with('');
        $response->send();

        $response = $this->getMockBuilder('Cake\Network\Response')
            ->setMethods(['_sendHeader', '_sendContent'])
            ->getMock();
        $response->body('This is a body');
        $response->statusCode(200);
        $response->expects($this->once())
            ->method('_sendContent')->with('This is a body');
        $response->send();
    }

    /**
     * Tests setting the expiration date
     *
     * @return void
     */
    public function testExpires()
    {
        $response = $this->getMockBuilder('Cake\Network\Response')
            ->setMethods(['_sendHeader', '_sendContent'])
            ->getMock();
        $now = new \DateTime('now', new \DateTimeZone('America/Los_Angeles'));
        $response->expires($now);
        $now->setTimeZone(new \DateTimeZone('UTC'));
        $this->assertEquals($now->format('D, j M Y H:i:s') . ' GMT', $response->expires());
        $response->expects($this->at(1))
            ->method('_sendHeader')->with('expires', $now->format('D, j M Y H:i:s') . ' GMT');
        $response->send();

        $response = $this->getMockBuilder('Cake\Network\Response')
            ->setMethods(['_sendHeader', '_sendContent'])
            ->getMock();
        $now = time();
        $response->expires($now);
        $this->assertEquals(gmdate('D, j M Y H:i:s', $now) . ' GMT', $response->expires());
        $response->expects($this->at(1))
            ->method('_sendHeader')->with('expires', gmdate('D, j M Y H:i:s', $now) . ' GMT');
        $response->send();

        $response = $this->getMockBuilder('Cake\Network\Response')
            ->setMethods(['_sendHeader', '_sendContent'])
            ->getMock();
        $time = new \DateTime('+1 day', new \DateTimeZone('UTC'));
        $response->expires('+1 day');
        $this->assertEquals($time->format('D, j M Y H:i:s') . ' GMT', $response->expires());
        $response->expects($this->at(1))
            ->method('_sendHeader')->with('expires', $time->format('D, j M Y H:i:s') . ' GMT');
        $response->send();
    }

    /**
     * Tests setting the modification date
     *
     * @return void
     */
    public function testModified()
    {
        $response = $this->getMockBuilder('Cake\Network\Response')
            ->setMethods(['_sendHeader', '_sendContent'])
            ->getMock();
        $now = new \DateTime('now', new \DateTimeZone('America/Los_Angeles'));
        $response->modified($now);
        $now->setTimeZone(new \DateTimeZone('UTC'));
        $this->assertEquals($now->format('D, j M Y H:i:s') . ' GMT', $response->modified());
        $response->expects($this->at(1))
            ->method('_sendHeader')->with('last-modified', $now->format('D, j M Y H:i:s') . ' GMT');
        $response->send();

        $response = $this->getMockBuilder('Cake\Network\Response')
            ->setMethods(['_sendHeader', '_sendContent'])
            ->getMock();
        $now = time();
        $response->modified($now);
        $this->assertEquals(gmdate('D, j M Y H:i:s', $now) . ' GMT', $response->modified());
        $response->expects($this->at(1))
            ->method('_sendHeader')->with('last-modified', gmdate('D, j M Y H:i:s', $now) . ' GMT');
        $response->send();

        $response = $this->getMockBuilder('Cake\Network\Response')
            ->setMethods(['_sendHeader', '_sendContent'])
            ->getMock();
        $time = new \DateTime('+1 day', new \DateTimeZone('UTC'));
        $response->modified('+1 day');
        $this->assertEquals($time->format('D, j M Y H:i:s') . ' GMT', $response->modified());
        $response->expects($this->at(1))
            ->method('_sendHeader')->with('last-modified', $time->format('D, j M Y H:i:s') . ' GMT');
        $response->send();
    }

    /**
     * Tests setting of public/private Cache-Control directives
     *
     * @return void
     */
    public function testSharable()
    {
        $response = $this->getMockBuilder('Cake\Network\Response')
            ->setMethods(['_sendHeader', '_sendContent'])
            ->getMock();
        $this->assertNull($response->sharable());
        $response->sharable(true);
        $headers = $response->header();
        $this->assertEquals('public', $headers['cache-control']);
        $response->expects($this->at(1))
            ->method('_sendHeader')->with('cache-control', 'public');
        $response->send();

        $response = $this->getMockBuilder('Cake\Network\Response')
            ->setMethods(['_sendHeader', '_sendContent'])
            ->getMock();
        $response->sharable(false);
        $headers = $response->header();
        $this->assertEquals('private', $headers['cache-control']);
        $response->expects($this->at(1))
            ->method('_sendHeader')->with('cache-control', 'private');
        $response->send();

        $response = $this->getMockBuilder('Cake\Network\Response')
            ->setMethods(['_sendHeader', '_sendContent'])
            ->getMock();
        $response->sharable(true);
        $headers = $response->header();
        $this->assertEquals('public', $headers['cache-control']);
        $response->sharable(false);
        $headers = $response->header();
        $this->assertEquals('private', $headers['cache-control']);
        $response->expects($this->at(1))
            ->method('_sendHeader')->with('cache-control', 'private');
        $response->send();
        $this->assertFalse($response->sharable());
        $response->sharable(true);
        $this->assertTrue($response->sharable());

        $response = $this->getMockBuilder('Cake\Network\Response')
            ->setMethods(['_sendHeader'])
            ->getMock();
        $response->sharable(true, 3600);
        $headers = $response->header();
        $this->assertEquals('public, max-age=3600', $headers['cache-control']);

        $response = $this->getMockBuilder('Cake\Network\Response')
            ->setMethods(['_sendHeader'])
            ->getMock();
        $response->sharable(false, 3600);
        $headers = $response->header();
        $this->assertEquals('private, max-age=3600', $headers['cache-control']);
        $response->send();
    }

    /**
     * Tests setting of max-age Cache-Control directive
     *
     * @return void
     */
    public function testMaxAge()
    {
        $response = $this->getMockBuilder('Cake\Network\Response')
            ->setMethods(['_sendHeader', '_sendContent'])
            ->getMock();
        $this->assertNull($response->maxAge());
        $response->maxAge(3600);
        $this->assertEquals(3600, $response->maxAge());
        $headers = $response->header();
        $this->assertEquals('max-age=3600', $headers['cache-control']);
        $response->expects($this->at(1))
            ->method('_sendHeader')->with('cache-control', 'max-age=3600');
        $response->send();

        $response = $this->getMockBuilder('Cake\Network\Response')
            ->setMethods(['_sendHeader', '_sendContent'])
            ->getMock();
        $response->maxAge(3600);
        $response->sharable(false);
        $headers = $response->header();
        $this->assertEquals('max-age=3600, private', $headers['cache-control']);
        $response->expects($this->at(1))
            ->method('_sendHeader')->with('cache-control', 'max-age=3600, private');
        $response->send();
    }

    /**
     * Tests setting of s-maxage Cache-Control directive
     *
     * @return void
     */
    public function testSharedMaxAge()
    {
        $response = $this->getMockBuilder('Cake\Network\Response')
            ->setMethods(['_sendHeader', '_sendContent'])
            ->getMock();
        $this->assertNull($response->maxAge());
        $response->sharedMaxAge(3600);
        $this->assertEquals(3600, $response->sharedMaxAge());
        $headers = $response->header();
        $this->assertEquals('s-maxage=3600', $headers['cache-control']);
        $response->expects($this->at(1))
            ->method('_sendHeader')->with('cache-control', 's-maxage=3600');
        $response->send();

        $response = $this->getMockBuilder('Cake\Network\Response')
            ->setMethods(['_sendHeader', '_sendContent'])
            ->getMock();
        $response->sharedMaxAge(3600);
        $response->sharable(true);
        $headers = $response->header();
        $this->assertEquals('s-maxage=3600, public', $headers['cache-control']);
        $response->expects($this->at(1))
            ->method('_sendHeader')->with('cache-control', 's-maxage=3600, public');
        $response->send();
    }

    /**
     * Tests setting of must-revalidate Cache-Control directive
     *
     * @return void
     */
    public function testMustRevalidate()
    {
        $response = $this->getMockBuilder('Cake\Network\Response')
            ->setMethods(['_sendHeader', '_sendContent'])
            ->getMock();
        $this->assertFalse($response->mustRevalidate());
        $response->mustRevalidate(true);
        $this->assertTrue($response->mustRevalidate());
        $headers = $response->header();
        $this->assertEquals('must-revalidate', $headers['cache-control']);
        $response->expects($this->at(1))
            ->method('_sendHeader')->with('cache-control', 'must-revalidate');
        $response->send();
        $response->mustRevalidate(false);
        $this->assertFalse($response->mustRevalidate());

        $response = $this->getMockBuilder('Cake\Network\Response')
            ->setMethods(['_sendHeader', '_sendContent'])
            ->getMock();
        $response->sharedMaxAge(3600);
        $response->mustRevalidate(true);
        $headers = $response->header();
        $this->assertEquals('s-maxage=3600, must-revalidate', $headers['cache-control']);
        $response->expects($this->at(1))
            ->method('_sendHeader')->with('cache-control', 's-maxage=3600, must-revalidate');
        $response->send();
    }

    /**
     * Tests getting/setting the Vary header
     *
     * @return void
     */
    public function testVary()
    {
        $response = $this->getMockBuilder('Cake\Network\Response')
            ->setMethods(['_sendHeader', '_sendContent'])
            ->getMock();
        $response->vary('Accept-encoding');
        $this->assertEquals(['Accept-encoding'], $response->vary());
        $response->expects($this->at(1))
            ->method('_sendHeader')->with('vary', 'Accept-encoding');
        $response->send();

        $response = $this->getMockBuilder('Cake\Network\Response')
            ->setMethods(['_sendHeader', '_sendContent'])
            ->getMock();
        $response->vary(['Accept-language', 'Accept-encoding']);
        $response->expects($this->at(1))
            ->method('_sendHeader')->with('vary', 'Accept-language, Accept-encoding');
        $response->send();
        $this->assertEquals(['Accept-language', 'Accept-encoding'], $response->vary());
    }

    /**
     * Tests getting/setting the Etag header
     *
     * @return void
     */
    public function testEtag()
    {
        $response = $this->getMockBuilder('Cake\Network\Response')
            ->setMethods(['_sendHeader', '_sendContent'])
            ->getMock();
        $response->etag('something');
        $this->assertEquals('"something"', $response->etag());
        $response->expects($this->at(1))
            ->method('_sendHeader')->with('Etag', '"something"');
        $response->send();

        $response = $this->getMockBuilder('Cake\Network\Response')
            ->setMethods(['_sendHeader', '_sendContent'])
            ->getMock();
        $response->etag('something', true);
        $this->assertEquals('W/"something"', $response->etag());
        $response->expects($this->at(1))
            ->method('_sendHeader')->with('Etag', 'W/"something"');
        $response->send();
    }

    /**
     * Tests that the response is able to be marked as not modified
     *
     * @return void
     */
    public function testNotModified()
    {
        $response = $this->getMockBuilder('Cake\Network\Response')
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
     * Test checkNotModified method
     *
     * @return void
     */
    public function testCheckNotModifiedByEtagStar()
    {
        $_SERVER['HTTP_IF_NONE_MATCH'] = '*';
        $response = $this->getMockBuilder('Cake\Network\Response')
            ->setMethods(['notModified'])
            ->getMock();
        $response->etag('something');
        $response->expects($this->once())->method('notModified');
        $response->checkNotModified(new Request);
    }

    /**
     * Test checkNotModified method
     *
     * @return void
     */
    public function testCheckNotModifiedByEtagExact()
    {
        $_SERVER['HTTP_IF_NONE_MATCH'] = 'W/"something", "other"';
        $response = $this->getMockBuilder('Cake\Network\Response')
            ->setMethods(['notModified'])
            ->getMock();
        $response->etag('something', true);
        $response->expects($this->once())->method('notModified');
        $this->assertTrue($response->checkNotModified(new Request));
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
        $response = $this->getMockBuilder('Cake\Network\Response')
            ->setMethods(['notModified'])
            ->getMock();
        $response->etag('something', true);
        $response->modified('2012-01-01 00:00:00');
        $response->expects($this->once())->method('notModified');
        $this->assertTrue($response->checkNotModified(new Request));
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
        $response = $this->getMockBuilder('Cake\Network\Response')
            ->setMethods(['notModified'])
            ->getMock();
        $response->etag('something', true);
        $response->modified('2012-01-01 00:00:01');
        $response->expects($this->never())->method('notModified');
        $this->assertFalse($response->checkNotModified(new Request));
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
        $response = $this->getMockBuilder('Cake\Network\Response')
            ->setMethods(['notModified'])
            ->getMock();
        $response->etag('something', true);
        $response->modified('2012-01-01 00:00:00');
        $response->expects($this->never())->method('notModified');
        $this->assertFalse($response->checkNotModified(new Request));
    }

    /**
     * Test checkNotModified method
     *
     * @return void
     */
    public function testCheckNotModifiedByTime()
    {
        $_SERVER['HTTP_IF_MODIFIED_SINCE'] = '2012-01-01 00:00:00';
        $response = $this->getMockBuilder('Cake\Network\Response')
            ->setMethods(['notModified'])
            ->getMock();
        $response->modified('2012-01-01 00:00:00');
        $response->expects($this->once())->method('notModified');
        $this->assertTrue($response->checkNotModified(new Request));
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
        $response = $this->getMockBuilder('Cake\Network\Response')
            ->setMethods(['notModified'])
            ->getMock();
        $response->expects($this->never())->method('notModified');
        $this->assertFalse($response->checkNotModified(new Request));
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
            'httpOnly' => false];
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
            $this->assertArrayHasKey('access-control-allow-origin', $headers);
            $this->assertEquals($expectedOrigin, $headers['access-control-allow-origin']);
        }
        if ($expectedMethods) {
            $this->assertArrayHasKey('access-control-allow-methods', $headers);
            $this->assertEquals($expectedMethods, $headers['access-control-allow-methods']);
        }
        if ($expectedHeaders) {
            $this->assertArrayHasKey('access-control-allow-headers', $headers);
            $this->assertEquals($expectedHeaders, $headers['access-control-allow-headers']);
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
        $fooRequest = new Request();

        $secureRequest = function () {
            $secureRequest = $this->getMockBuilder('Cake\Network\Request')
                ->setMethods(['is'])
                ->getMock();
            $secureRequest->expects($this->any())
                ->method('is')
                ->with('ssl')
                ->will($this->returnValue(true));

            return $secureRequest;
        };

        return [
            // [$fooRequest, null, '*', '', '', false, false],
            // [$fooRequest, 'http://www.foo.com', '*', '', '', '*', false],
            // [$fooRequest, 'http://www.foo.com', 'www.foo.com', '', '', 'http://www.foo.com', false],
            // [$fooRequest, 'http://www.foo.com', '*.foo.com', '', '', 'http://www.foo.com', false],
            // [$fooRequest, 'http://www.foo.com', 'http://*.foo.com', '', '', 'http://www.foo.com', false],
            // [$fooRequest, 'http://www.foo.com', 'https://www.foo.com', '', '', false, false],
            // [$fooRequest, 'http://www.foo.com', 'https://*.foo.com', '', '', false, false],
            // [$fooRequest, 'http://www.foo.com', ['*.bar.com', '*.foo.com'], '', '', 'http://www.foo.com', false],

            // [$fooRequest, 'http://not-foo.com', '*.foo.com', '', '', false, false],
            // [$fooRequest, 'http://bad.academy', '*.acad.my', '', '', false, false],
            // [$fooRequest, 'http://www.foo.com.at.bad.com', '*.foo.com', '', '', false, false],
            // [$fooRequest, 'https://www.foo.com', '*.foo.com', '', '', false, false],

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
     * @expectedException \Cake\Network\Exception\NotFoundException
     * @return void
     */
    public function testFileNotFound()
    {
        $response = new Response();
        $response->file('/some/missing/folder/file.jpg');
    }

    /**
     * test file with ../
     *
     * @expectedException \Cake\Network\Exception\NotFoundException
     * @expectedExceptionMessage The requested file contains `..` and will not be read.
     * @return void
     */
    public function testFileWithForwardSlashPathTraversal()
    {
        $response = new Response();
        $response->file('my/../cat.gif');
    }

    /**
     * test file with ..\
     *
     * @expectedException \Cake\Network\Exception\NotFoundException
     * @expectedExceptionMessage The requested file contains `..` and will not be read.
     * @return void
     */
    public function testFileWithBackwardSlashPathTraversal()
    {
        $response = new Response();
        $response->file('my\..\cat.gif');
    }

    /**
     * test file with ..
     *
     * @expectedException \Cake\Network\Exception\NotFoundException
     * @expectedExceptionMessage my/ca..t.gif was not found or not readable
     * @return void
     */
    public function testFileWithDotsInTheFilename()
    {
        $response = new Response();
        $response->file('my/ca..t.gif');
    }

    /**
     * test file with .. in a path fragment
     *
     * @expectedException \Cake\Network\Exception\NotFoundException
     * @expectedExceptionMessage my/ca..t/image.gif was not found or not readable
     * @return void
     */
    public function testFileWithDotsInAPathFragment()
    {
        $response = new Response();
        $response->file('my/ca..t/image.gif');
    }

    /**
     * testFile method
     *
     * @return void
     */
    public function testFile()
    {
        $response = $this->getMockBuilder('Cake\Network\Response')
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
            ->with('accept-ranges', 'bytes');

        $response->expects($this->exactly(1))
            ->method('_isActive')
            ->will($this->returnValue(true));

        $response->file(TEST_APP . 'vendor/css/test_asset.css');

        ob_start();
        $result = $response->send();
        $output = ob_get_clean();
        $this->assertEquals("/* this is the test asset css file */", trim($output));
        $this->assertTrue($result !== false);
    }

    /**
     * testFileWithDownloadAndName
     *
     * @return void
     */
    public function testFileWithDownloadAndName()
    {
        $response = $this->getMockBuilder('Cake\Network\Response')
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
            ->with('content-transfer-encoding', 'binary');

        $response->expects($this->at(3))
            ->method('header')
            ->with('accept-ranges', 'bytes');

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

        $response = $this->getMockBuilder('Cake\Network\Response')
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
            ->with('content-transfer-encoding', 'binary');

        $response->expects($this->at(3))
            ->method('header')
            ->with('accept-ranges', 'bytes');

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
     * testFileWithUnknownFileTypeOpera method
     *
     * @return void
     */
    public function testFileWithUnknownFileTypeOpera()
    {
        $currentUserAgent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null;
        $_SERVER['HTTP_USER_AGENT'] = 'Opera/9.80 (Windows NT 6.0; U; en) Presto/2.8.99 Version/11.10';

        $response = $this->getMockBuilder('Cake\Network\Response')
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
            ->with('content-transfer-encoding', 'binary');

        $response->expects($this->at(4))
            ->method('header')
            ->with('accept-ranges', 'bytes');

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
     * testFileWithUnknownFileTypeIE method
     *
     * @return void
     */
    public function testFileWithUnknownFileTypeIE()
    {
        $currentUserAgent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null;
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (compatible; MSIE 8.0; Windows NT 5.2; Trident/4.0; Media Center PC 4.0; SLCC1; .NET CLR 3.0.04320)';

        $response = $this->getMockBuilder('Cake\Network\Response')
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
            ->with('content-transfer-encoding', 'binary');

        $response->expects($this->at(4))
            ->method('header')
            ->with('accept-ranges', 'bytes');

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
     * testFileWithUnknownFileNoDownload method
     *
     * @return void
     */
    public function testFileWithUnknownFileNoDownload()
    {
        $currentUserAgent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null;
        $_SERVER['HTTP_USER_AGENT'] = 'Some generic browser';

        $response = $this->getMockBuilder('Cake\Network\Response')
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
            ->with('accept-ranges', 'bytes');

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
        $response = $this->getMockBuilder('Cake\Network\Response')
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
        $response = $this->getMockBuilder('Cake\Network\Response')
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
     * Test downloading files with extension not explicitly set.
     *
     * @return void
     */
    public function testFileExtensionNotSet()
    {
        $response = $this->getMockBuilder('Cake\Network\Response')
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
        $response = $this->getMockBuilder('Cake\Network\Response')
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
            ->with('content-disposition', 'attachment; filename="test_asset.css"');

        $response->expects($this->at(2))
            ->method('header')
            ->with('content-transfer-encoding', 'binary');

        $response->expects($this->at(3))
            ->method('header')
            ->with('accept-ranges', 'bytes');

        $response->expects($this->at(4))
            ->method('header')
            ->with([
                'content-length' => $length,
                'content-range' => $offsetResponse,
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
     * Test fetching ranges from a file.
     *
     * @return void
     */
    public function testFileRange()
    {
        $_SERVER['HTTP_RANGE'] = 'bytes=8-25';
        $response = $this->getMockBuilder('Cake\Network\Response')
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
            ->with('content-disposition', 'attachment; filename="test_asset.css"');

        $response->expects($this->at(2))
            ->method('header')
            ->with('content-transfer-encoding', 'binary');

        $response->expects($this->at(3))
            ->method('header')
            ->with('accept-ranges', 'bytes');

        $response->expects($this->at(4))
            ->method('header')
            ->with([
                'content-length' => 18,
                'content-range' => 'bytes 8-25/38',
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
        $this->assertEquals("is the test asset ", $output);
        $this->assertNotSame(false, $result);
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
        $response = $this->getMockBuilder('Cake\Network\Response')
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
            'content-disposition' => 'attachment; filename="test_asset.css"',
            'content-transfer-encoding' => 'binary',
            'accept-ranges' => 'bytes',
            'content-range' => 'bytes 0-37/38',
            'content-length' => 38,
        ];
        $this->assertEquals($expected, $response->header());
    }

    /**
     * Test reversed file ranges.
     *
     * @return void
     */
    public function testFileRangeReversed()
    {
        $_SERVER['HTTP_RANGE'] = 'bytes=30-2';
        $response = $this->getMockBuilder('Cake\Network\Response')
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
            ->with('content-disposition', 'attachment; filename="test_asset.css"');

        $response->expects($this->at(2))
            ->method('header')
            ->with('content-transfer-encoding', 'binary');

        $response->expects($this->at(3))
            ->method('header')
            ->with('accept-ranges', 'bytes');

        $response->expects($this->at(4))
            ->method('header')
            ->with([
                'content-range' => 'bytes 0-37/38',
            ]);

        $response->file(
            TEST_APP . 'vendor' . DS . 'css' . DS . 'test_asset.css',
            ['download' => true]
        );

        $this->assertEquals(416, $response->statusCode());
        $result = $response->send();
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
        $response = $this->getMockBuilder('Cake\Network\Response')
            ->setMethods([
                'header',
                'type',
                '_sendHeader',
                '_isActive',
            ])
            ->getMock();

        $response->expects($this->at(1))
            ->method('header')
            ->with('accept-ranges', 'bytes');

        $response->expects($this->at(2))
            ->method('header')
            ->with([
                'content-length' => $length,
                'content-range' => $offsetResponse,
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
        $response = $this->getMockBuilder('Cake\Network\Response')
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
            ->with('accept-ranges', 'bytes');

        $response->expects($this->at(2))
            ->method('header')
            ->with([
                'content-length' => 18,
                'content-range' => 'bytes 8-25/38',
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
        $this->assertEquals("is the test asset ", $output);
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
        $response = $this->getMockBuilder('Cake\Network\Response')
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
            ->with('accept-ranges', 'bytes');

        $response->expects($this->at(2))
            ->method('header')
            ->with([
                'content-range' => 'bytes 0-37/38',
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
        $reasonPhrase = $response->getReasonPhrase();
        $this->assertNull($reasonPhrase);

        $response = $response->withStatus(404);
        $reasonPhrase = $response->getReasonPhrase();
        $this->assertEquals('Not Found', $reasonPhrase);
    }
}
